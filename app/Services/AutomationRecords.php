<?php

namespace App\Services;

use App\Database\RelationalFields;
use App\Models\StatusNotificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutomationRecords
{
    public function descriptor(string $table, object $row): array
    {
        try {
            RelationalFields::hydratePayload($table, $row);
            $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
            // Inspect queued metadata without instantiating the serialized job classes.
            $command = isset($payload['data']['command']) && is_string($payload['data']['command'])
                ? @unserialize($payload['data']['command'], ['allowed_classes'=>false]) : null;
            $properties = is_object($command) ? (array) $command : [];
            return [
                'name'=>(string) ($payload['displayName'] ?? $payload['data']['commandName'] ?? 'Job tanpa nama'),
                'batch_id'=>is_scalar($properties['batchId'] ?? null) ? $properties['batchId'] : null,
                'delivery_id'=>($properties['__PHP_Incomplete_Class_Name'] ?? null) === \App\Jobs\SendStatusNotification::class ? ($properties['deliveryId'] ?? null) : null,
            ];
        } catch (\Throwable) {
            return ['name'=>'Payload tidak dapat dibaca', 'batch_id'=>null, 'delivery_id'=>null];
        }
    }

    public function forgetNotification(string $channel, int $id): void
    {
        DB::transaction(function () use ($channel, $id) {
            $delivery = StatusNotificationDelivery::where('channel', $channel)->lockForUpdate()->findOrFail($id);
            abort_if($delivery->status === 'processing', 409, 'Notifikasi sedang dikirim dan belum dapat dihapus.');
            $this->forgetRow('status_notification_deliveries', $id);
        });
    }

    public function forgetJob(string $kind, string $id): void
    {
        $table = ['job'=>'jobs', 'failed-job'=>'failed_jobs', 'batch'=>'job_batches'][$kind] ?? null;
        abort_unless($table, 404);
        abort_if($kind !== 'batch' && !ctype_digit($id), 404);
        DB::transaction(function () use ($kind, $table, $id) {
            $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($kind === 'batch') {
                // Reserve locks prevent the worker from claiming these jobs during deletion.
                $members = [];
                foreach (['jobs', 'failed_jobs'] as $memberTable) {
                    foreach (DB::table($memberTable)->orderBy('id')->lockForUpdate()->get() as $member) {
                        $descriptor = $this->descriptor($memberTable, $member);
                        if ((string) ($descriptor['batch_id'] ?? '') !== (string) $id) continue;
                        abort_if($memberTable === 'jobs' && $member->reserved_at !== null, 409, 'Batch memiliki job yang sedang diproses. Tunggu worker selesai sebelum menghapus.');
                        $members[] = [$memberTable, $member->id, $descriptor];
                    }
                }
                foreach ($members as [$memberTable, $memberId, $descriptor]) {
                    if ($memberTable === 'jobs') $this->skipDelivery($descriptor['delivery_id']);
                    $this->forgetRow($memberTable, $memberId);
                }
            } else {
                $descriptor = $this->descriptor($table, $row);
                abort_if($descriptor['batch_id'] && DB::table('job_batches')->where('id', $descriptor['batch_id'])->exists(), 409, 'Job ini bagian dari batch. Hapus melalui baris batch agar data antrean tetap konsisten.');
                if ($kind === 'job') {
                    abort_if($row->reserved_at !== null, 409, 'Job sedang diproses dan belum dapat dihapus.');
                    $this->skipDelivery($descriptor['delivery_id']);
                }
            }
            $this->forgetRow($table, $id);
        });
    }

    private function skipDelivery($id): void
    {
        if ($id && Schema::hasTable('status_notification_deliveries')) {
            StatusNotificationDelivery::whereKey($id)->where('status', 'pending')->update([
                'status'=>'skipped', 'error'=>'Job pengiriman dihapus manual dari Job Monitor.',
            ]);
        }
    }

    private function forgetRow(string $table, $id): void
    {
        // Also clean relational payload rows on installations with foreign keys disabled.
        if (RelationalFields::available($table)) {
            DB::table(RelationalFields::MAP[$table]['table'])->where('owner_id', $id)->delete();
        }
        DB::table($table)->where('id', $id)->delete();
    }
}
