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
            if (!$process->isSuccessful()) throw new RuntimeException;
            return $process->getOutput();
        } catch (\Throwable) {
            // Never expose rclone stderr: it may include tokens or server paths.
            throw new RuntimeException('Rclone gagal. Periksa binary, remote, konfigurasi, izin PHP, koneksi, dan kuota Google Drive.');
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
        $remote = app(SystemSettings::class)->get('drive_remote');
        $remotes = $this->run(['listremotes','--long']);
        if (!preg_match('/^'.preg_quote($remote, '/').':\s+drive(?:\s|$)/m', $remotes)) {
            throw new RuntimeException('Remote tidak ditemukan atau bukan tipe Google Drive. Jalankan rclone config sebagai pengguna worker aplikasi.');
        }
        $this->run(['lsjson',$remote.':','--stat']);
        $temporary = tempnam(sys_get_temp_dir(),'drive-probe-');
        if (!$temporary) throw new RuntimeException('Folder temporary PHP tidak dapat ditulis.');
        $content = random_bytes(32);
        file_put_contents($temporary,$content);
        $destination = rtrim($this->root(),'/').'/.connection-test-'.bin2hex(random_bytes(12));
        try {
            $this->run(['copyto',$temporary,$destination,'--immutable']);
            $stat = $this->stat($destination);
            if ((int) $stat['Size'] !== strlen($content) || strtolower($stat['Hashes']['MD5'] ?? '') !== md5($content)) throw new RuntimeException('Tes verifikasi Google Drive gagal.');
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
        return $stat;
    }

    public function validateRemote(string $remote): void
    {
        if (!preg_match('~^[a-zA-Z0-9][a-zA-Z0-9_-]*:[^:\x00-\x1f*?\[\]{}]+$~D', $remote)
            || preg_match('~(^|/)\.\.(/|$)~', $remote) || str_contains($remote, '\\')) {
            throw new RuntimeException('Lokasi file Google Drive tidak valid.');
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
