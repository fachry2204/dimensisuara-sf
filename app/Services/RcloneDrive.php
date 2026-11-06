<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;

class RcloneDrive
{
    public function process(array $arguments): Process
    {
        $settings = app(SystemSettings::class);
        $command = [$settings->get('drive_binary') ?: 'rclone'];
        if ($settings->get('drive_config')) array_push($command, '--config', $settings->get('drive_config'));
        return (new Process(array_merge($command, $arguments, ['--retries','2','--low-level-retries','2','--contimeout','20s','--timeout','120s'])))
            ->setTimeout(840);
    }

    public function run(array $arguments): string
    {
        try {
            $process = $this->process($arguments);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \App\Exceptions\DriveSetupException($this->failureMessage($process->getErrorOutput()));
            }
            return $process->getOutput();
        } catch (\App\Exceptions\DriveSetupException $exception) {
            throw $exception;
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) {
            throw new \App\Exceptions\DriveSetupException('Proses rclone melewati batas waktu. Periksa koneksi server ke Google Drive.');
        } catch (\Throwable $exception) {
            // Never expose rclone stderr: it may include tokens or server paths.
            throw new \App\Exceptions\DriveSetupException($this->failureMessage($exception->getMessage()));
        }
    }

    private function failureMessage(string $error): string
    {
        // Match diagnostics internally; only fixed messages can reach the browser or logs.
        return match (true) {
            (bool) preg_match('/invalid_grant|invalid_token|token.*(?:expired|revoked)|oauth.*(?:failed|error)|unauthorized|invalid_client/i', $error) => 'Otorisasi Google Drive ditolak atau token kedaluwarsa. Hubungkan ulang remote sebagai pengguna aplikasi dengan konfigurasi yang sama.',
            (bool) preg_match('/permission denied|access denied|operation not permitted|insufficient.*permission|forbidden/i', $error) => 'Akses ditolak. Periksa izin pengguna PHP pada binary, folder konfigurasi, rclone.conf, dan folder tujuan Drive. Konfigurasi juga harus dapat diperbarui saat token disegarkan.',
            (bool) preg_match('/quota|storageQuotaExceeded|rateLimitExceeded|too many requests/i', $error) => 'Kuota penyimpanan atau batas permintaan Google Drive tercapai. Periksa kuota lalu coba kembali.',
            (bool) preg_match('/no such host|could not resolve|name resolution|dial tcp|network is unreachable|connection refused|i\/o timeout|deadline exceeded|tls handshake|x509|certificate/i', $error) => 'Koneksi server ke Google Drive gagal. Periksa DNS, akses HTTPS keluar, sertifikat, dan waktu server.',
            (bool) preg_match('/proc_open|proc_get_status|proc_terminate|proc_close|executable.*not found|command not found|no such file or directory|failed to start/i', $error) => 'PHP tidak dapat menjalankan rclone. Periksa Lokasi rclone serta fungsi proses yang diizinkan pada PHP-FPM Plesk; tes SSH saja belum memastikan izin PHP web.',
            (bool) preg_match('/config.*(?:not found|missing)|didn.t find section|failed to (?:load|read|decrypt).*config|config.*password/i', $error) => 'Konfigurasi rclone atau remote tidak tersedia bagi PHP. Periksa Lokasi rclone.conf dan nama remote yang tersimpan.',
            (bool) preg_match('/unknown flag|unknown command/i', $error) => 'Versi rclone tidak mendukung perintah tes. Periksa versi binary yang dipakai PHP.',
            default => 'Rclone gagal menyelesaikan perintah. Periksa koneksi, konfigurasi, dan izin pengguna PHP.',
        };
    }

    private function connectionStep(string $stage, callable $callback): mixed
    {
        try { return $callback(); }
        catch (\Throwable $exception) {
            $detail = $exception instanceof \App\Exceptions\DriveSetupException
                ? $exception->getMessage()
                : 'Tahap ini gagal diselesaikan. Periksa konfigurasi dan izin pengguna PHP.';
            throw new \App\Exceptions\DriveSetupException('Tes koneksi gagal pada tahap '.$stage.'. '.$detail);
        }
    }

    public function root(): string
    {
        $settings = app(SystemSettings::class);
        $remote = $settings->get('drive_remote');
        $folder = trim($settings->get('drive_folder'), '/');
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/D', $remote) || preg_match('~(^|/)\.\.(/|$)|[\\\\:\x00-\x1f*?\[\]{}]~', $folder)) {
            throw new RuntimeException('Remote atau folder Google Drive tidak valid.');
        }
        return $remote.':'.$folder;
    }

    public function testConnection(): void
    {
        if (!function_exists('proc_open')) {
            throw new \App\Exceptions\DriveSetupException('PHP belum mengizinkan proc_open untuk menjalankan rclone. Minta pengelola hosting mengaktifkannya untuk aplikasi ini.');
        }
        $settings = app(SystemSettings::class);
        $binary = $settings->get('drive_binary') ?: 'rclone';
        if ((str_contains($binary, '/') || str_contains($binary, '\\')) && (!is_file($binary) || !is_executable($binary))) {
            throw new \App\Exceptions\DriveSetupException('Lokasi rclone tidak ditemukan atau tidak dapat dijalankan oleh PHP. Jalankan command -v rclone di SSH, lalu simpan path hasilnya pada Lokasi rclone.');
        }
        $configFile = $settings->get('drive_config');
        if ($configFile && (!is_file($configFile) || !is_readable($configFile))) {
            throw new \App\Exceptions\DriveSetupException('rclone.conf tidak ditemukan atau tidak terbaca oleh PHP. Jalankan rclone config file sebagai pengguna aplikasi, isi path hasilnya pada Lokasi rclone.conf, dan periksa izin folder/file.');
        }
        $remote = app(SystemSettings::class)->get('drive_remote');
        $remotes = $this->connectionStep('membaca daftar remote', fn () => $this->run(['listremotes','--long']));
        if (!preg_match('/^'.preg_quote($remote, '/').':\s+drive(?:\s|$)/m', $remotes)) {
            throw new \App\Exceptions\DriveSetupException('Remote tidak ditemukan pada konfigurasi PHP atau bukan tipe Google Drive. Isi Lokasi rclone.conf dengan path dari rclone config file dan pastikan Nama Remote sama dengan hasil rclone listremotes --long.');
        }
        $this->connectionStep('mengakses Google Drive', fn () => $this->run(['lsjson',$remote.':','--stat']));
        $root = $this->connectionStep('memeriksa remote dan folder tujuan', fn () => $this->root());
        $temporary = tempnam(sys_get_temp_dir(),'drive-probe-');
        if (!$temporary) throw new \App\Exceptions\DriveSetupException('Folder temporary PHP tidak dapat ditulis untuk membuat file uji.');
        $content = random_bytes(32);
        if (file_put_contents($temporary,$content) !== strlen($content)) {
            @unlink($temporary);
            throw new \App\Exceptions\DriveSetupException('File uji tidak dapat ditulis lengkap pada folder temporary PHP.');
        }
        $destination = rtrim($root,'/').'/.connection-test-'.bin2hex(random_bytes(12));
        try {
            $this->connectionStep('mengunggah file uji ke folder tujuan', fn () => $this->run(['copyto',$temporary,$destination,'--immutable']));
            $stat = $this->connectionStep('membaca ukuran dan checksum file uji', fn () => $this->stat($destination));
            if ((int) $stat['Size'] !== strlen($content) || strtolower($stat['Hashes']['MD5'] ?? '') !== md5($content)) {
                throw new \App\Exceptions\DriveSetupException('Tes verifikasi Google Drive gagal: ukuran atau checksum MD5 salinan tidak cocok. Pastikan kode aplikasi terbaru sudah terpasang dan cache PHP sudah diperbarui.');
            }
        } finally {
            @unlink($temporary);
            try { $this->run(['deletefile',$destination]); } catch (\Throwable) { /* Only our random probe may remain. */ }
        }
    }

    public function stat(string $remote): array
    {
        $this->validateRemote($remote);
        $stat = json_decode($this->run(['lsjson',$remote,'--stat','--hash','--hash-type','MD5']), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($stat) || ($stat['IsDir'] ?? true)) throw new RuntimeException('File Google Drive tidak ditemukan.');
        // Rclone versions differ in the casing of hash names such as md5 and MD5.
        if (isset($stat['Hashes']) && is_array($stat['Hashes'])) {
            $stat['Hashes'] = array_change_key_case($stat['Hashes'], CASE_UPPER);
        }
        return $stat;
    }

    public function validateRemote(string $remote): void
    {
        if (!preg_match('~^[a-zA-Z0-9][a-zA-Z0-9_-]*:[^:\x00-\x1f*?\[\]{}]+$~D', $remote)
            || preg_match('~(^|/)\.\.(/|$)~', $remote) || str_contains($remote, '\\')) {
            throw new RuntimeException('Lokasi file Google Drive tidak valid.');
        }
    }

    public function deleteFile(string $remote): void
    {
        $this->validateRemote($remote);
        try {
            $process = $this->process(['deletefile', $remote]);
            $process->run();
            // A previous deletion attempt may already have removed the exact file.
            if (!$process->isSuccessful() && !in_array($process->getExitCode(), [3, 4], true)) {
                throw new RuntimeException;
            }
        } catch (\Throwable) {
            throw new RuntimeException('File Google Drive belum dapat dihapus. Periksa koneksi dan izin rclone.');
        }
    }

    public function stream(string $remote, int $offset, int $count): void
    {
        @set_time_limit(0);
        $this->validateRemote($remote);
        $process = $this->process(['cat',$remote,'--offset',(string) $offset,'--count',(string) $count]);
        try {
            $process->run(function ($type, $bytes) use ($process) {
                if ($type === Process::OUT) {
                    if (connection_aborted()) throw new RuntimeException('Client disconnected.');
                    echo $bytes;
                    if (ob_get_level()) @ob_flush();
                    flush();
                }
                $process->clearOutput();
                $process->clearErrorOutput();
            });
        } finally {
            if ($process->isRunning()) $process->stop();
        }
    }

    public function response(File $file, Request $request, bool $publicCover = false)
    {
        try { $stat = $this->stat($file->identifier); }
        catch (\Throwable) { abort(503, 'File Google Drive sementara tidak tersedia.'); }
        $size = (int) $stat['Size'];
        $start = 0; $end = max(0,$size-1); $status = 200;
        if ($range = $request->header('Range')) {
            if (!$size || !preg_match('/^bytes=(\d*)-(\d*)$/D', $range, $match) || ($match[1] === '' && $match[2] === '')) {
                return response('',416,['Content-Range'=>'bytes */'.$size]);
            }
            if ($match[1] === '') $start = max(0,$size-(int) $match[2]);
            else { $start = (int) $match[1]; if ($match[2] !== '') $end = min($end,(int) $match[2]); }
            if ($start > $end || $start >= $size) return response('',416,['Content-Range'=>'bytes */'.$size]);
            $status = 206;
        }
        $length = $size ? $end-$start+1 : 0;
        $name = $file->original_name ?: 'download';
        $fallback = preg_replace('/[^a-zA-Z0-9._-]/','_', $name) ?: 'download';
        $headers = ['Content-Type'=>$file->mime_type ?: 'application/octet-stream', 'Content-Length'=>(string) $length,
            'Accept-Ranges'=>'bytes', 'X-Content-Type-Options'=>'nosniff', 'X-Accel-Buffering'=>'no',
            'Cache-Control'=>$publicCover ? 'public, max-age=3600' : 'private, no-store',
            'Content-Disposition'=>HeaderUtils::makeDisposition($request->boolean('dl') ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE, $name, $fallback)];
        if ($status === 206) $headers['Content-Range'] = 'bytes '.$start.'-'.$end.'/'.$size;
        return response()->stream(fn () => $this->stream($file->identifier,$start,$length),$status,$headers);
    }
}
