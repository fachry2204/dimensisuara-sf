<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileController extends Controller
{
    public function access(File $file, Request $request)
    {
        $this->ensureAuthorized($file, $request);

        return $this->serve($file, $request);
    }

    public function signee($token, File $file, Request $request)
    {
        $this->verifyHash($file, $request);

        $signee = $request->signee;

        if (! $signee || ! optional($signee->publishing)->profileid
            || $signee->publishing->profileid !== $file->profileid) {
            abort(403, 'Unauthorized');
        }

        return $this->serve($file, $request);
    }

    protected function ensureAuthorized(File $file, Request $request): void
    {
        $this->verifyHash($file, $request);

        $user = current_user();
        $isOwner = $user && $user->profile && (int) $user->profile->id === (int) $file->profileid;

        if (! (Gate::allows('admin') || $isOwner)) {
            abort(403, 'Unauthorized');
        }

        // Release audio masters are download-gated for admin viewers who lack the
        // download permission (e.g. Partner). Owners keep access to their own files.
        if (! $isOwner
            && in_array($file->type, ['full', 'trimmed'], true)
            && ! ($user && $user->can('download-release-files'))) {
            abort(403, 'Unauthorized');
        }
    }

    protected function verifyHash(File $file, Request $request): void
    {
        if (! hash_equals(sha1($file->id.'|'.$file->updated_at), (string) $request->hash)) {
            abort(403, 'Unauthorized');
        }
    }

    protected function serve(File $file, Request $request)
    {
        $disposition = $request->boolean('dl')
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $headers = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($file->disk === 'rclone') return app(\App\Services\RcloneDrive::class)->response($file, $request);

        $identifier = (string) $file->identifier;

        if ($identifier === '') {
            return $this->serveBackup($file, $headers);
        }

        // Storage::disk('local')->path() does not strip '..' — guard before any FS call.
        if (str_contains($identifier, '..')
            || str_starts_with($identifier, '/')
            || str_starts_with($identifier, '\\')) {
            abort(404, 'File not found');
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($identifier)) {
            abort(404, 'File not found');
        }

        if ($file->disk === 'local') {
            return response()
                ->file($disk->path($identifier), $headers)
                ->setContentDisposition($disposition, $file->original_name);
        }

        return $disk->response($identifier, $file->original_name, $headers, $disposition);
    }

    protected function serveBackup(File $file, array $headers)
    {
        $identifier = (string) $file->backup_identifier;

        if ($identifier === ''
            || str_contains($identifier, '..')
            || str_starts_with($identifier, '/')
            || str_starts_with($identifier, '\\')) {
            abort(404, 'File not found');
        }

        $disk = Storage::disk('ftp');

        if (! $disk->exists($identifier)) {
            abort(404, 'File not found');
        }

        $stream = $disk->readStream($identifier);

        if (! is_resource($stream)) {
            abort(404, 'File not found');
        }

        if ($file->size) {
            $headers['Content-Length'] = (string) $file->size;
        }

        $filename = $file->original_name ?: 'download';
        $headers['Content-Disposition'] = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $filename
        );

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}
