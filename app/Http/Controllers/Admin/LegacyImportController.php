<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunLegacyImport;
use App\Models\LegacyImportRun;
use App\Services\Legacy\ImportPaths;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LegacyImportController extends Controller
{
    public function store(Request $request, ImportPaths $paths)
    {
        $request->validate([
            'sql' => ['nullable', 'file', 'max:'.config('legacy-import.max_sql_kb')],
            'server_sql' => ['nullable', 'string', 'max:1000'],
            'media_root' => ['nullable', 'string', 'max:1000'],
        ]);
        if (!$request->hasFile('sql') && !$request->filled('server_sql')) {
            throw ValidationException::withMessages(['sql' => 'Pilih SQL atau isi nama file SQL di folder impor server.']);
        }
        if ($request->hasFile('sql') && $request->filled('server_sql')) {
            throw ValidationException::withMessages(['sql' => 'Gunakan salah satu sumber SQL.']);
        }
        $uploadedPath = null;
        try {
            $root = $request->filled('media_root') ? $paths->mediaRoot($request->string('media_root')->toString()) : null;
            $inbox = config('legacy-import.inbox');
            if (!is_dir($inbox) && !mkdir($inbox, 0700, true) && !is_dir($inbox)) {
                throw new RuntimeException('Folder impor privat tidak dapat dibuat.');
            }
            if ($request->hasFile('sql')) {
                if (strtolower($request->file('sql')->getClientOriginalExtension()) !== 'sql') {
                    throw new RuntimeException('Gunakan file .sql tanpa kompresi.');
                }
                $file = $request->file('sql')->move($inbox, bin2hex(random_bytes(16)).'.sql');
                $path = $uploadedPath = $file->getPathname();
                @chmod($path, 0600);
            } else {
                $path = $paths->inside($inbox.DIRECTORY_SEPARATOR.$request->input('server_sql'), $inbox);
            }
            if (!is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'sql') {
                throw new RuntimeException('Sumber harus berupa file .sql.');
            }
            if (filesize($path) > config('legacy-import.max_sql_kb') * 1024) {
                throw new RuntimeException('SQL melebihi batas 100 MB. Pecah ekspor per katalog sebelum mencoba ulang.');
            }
            $run = LegacyImportRun::create(['admin_id' => $request->user()->id, 'sql_path' => $path,
                'sql_sha256' => hash_file('sha256', $path), 'media_root' => $root]);
            RunLegacyImport::dispatch($run->id, 'trial');
        } catch (RuntimeException $e) {
            if ($uploadedPath && !isset($run)) @unlink($uploadedPath);
            throw ValidationException::withMessages(['sql' => $e->getMessage()]);
        }
        return $this->back($run->id);
    }

    public function action(Request $request, int $run, string $action)
    {
        abort_unless(in_array($action, ['apply', 'trial', 'media'], true), 404);
        $request->validate(['media_root' => ['nullable', 'string', 'max:1000']]);
        if ($action === 'apply') $request->validate(['backup_confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $run, $action) {
            $item = LegacyImportRun::lockForUpdate()->findOrFail($run);
            $allowed = match ($action) {
                'apply' => ['trial_ready'],
                'trial' => ['trial_failed', 'apply_failed'],
                'media' => ['imported', 'media_failed', 'media_issues', 'complete'],
            };
            abort_unless(in_array($item->status, $allowed, true), 409, 'Tahap impor belum sesuai atau masih berjalan.');
            if ($action === 'media') {
                try {
                    $root = app(ImportPaths::class)->mediaRoot($request->input('media_root') ?: ($item->media_root ?? ''));
                } catch (RuntimeException $e) {
                    throw ValidationException::withMessages(['media_root' => $e->getMessage()]);
                }
                $item->media_root = $root;
                $item->media_cursor = 0;
                $item->media_processed = 0;
            }
            $item->status = $action.'_queued';
            $item->error = null;
            $item->finished_at = null;
            $item->save();
            RunLegacyImport::dispatch($item->id, $action);
        });
        return $this->back($run);
    }

    private function back(int $id)
    {
        return redirect()->route('admin.system-settings.index', ['tab' => 'legacy', 'import' => $id]);
    }
}
