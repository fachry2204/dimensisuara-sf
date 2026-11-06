<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationHealth
{
    public function record(string $component): void
    {
        if (!in_array($component, ['scheduler', 'worker'], true) || !Schema::hasTable('system_settings')) return;
        // Use a fresh reader: queue workers keep their service container alive.
        $settings = new SystemSettings;
        $key = 'notification_health_'.$component.'_at';
        $last = $settings->get($key);
        if ($last && Carbon::parse($last)->gt(now()->subMinute())) return;
        $settings->save([$key => now()->toIso8601String()]);
    }

    public function observesNotificationQueue(string $connection, ?string $queues): bool
    {
        $expected = config('queue.connections.status-notifications');
        $actual = config('queue.connections.'.$connection);
        if (($actual['driver'] ?? null) !== 'database') return false;
        return ($actual['connection'] ?? config('database.default')) === ($expected['connection'] ?? config('database.default'))
            && ($actual['table'] ?? 'jobs') === $expected['table']
            && in_array($expected['queue'], explode(',', $queues ?: ($actual['queue'] ?? 'default')), true);
    }

    public function snapshot(): array
    {
        $settings = new SystemSettings;
        $result = [];
        foreach (['scheduler', 'worker'] as $component) {
            $last = $settings->get('notification_health_'.$component.'_at');
            $date = $last ? Carbon::parse($last) : null;
            $state = !$date ? 'unknown' : ($date->betweenIncluded(now()->subMinutes(3), now()->addMinute()) ? 'active' : 'stale');
            $result[$component] = ['state' => $state, 'last_at' => $date?->timezone(config('app.timezone'))->format('d M Y H:i:s T')];
        }
        $result['channels'] = [];
        $result['deliveries_ready'] = Schema::hasTable('status_notification_deliveries');
        foreach (['email', 'whatsapp'] as $channel) {
            $rows = $result['deliveries_ready']
                ? DB::table('status_notification_deliveries')->where('channel', $channel) : null;
            $lastSent = $rows ? (clone $rows)->where('status', 'sent')->max('sent_at') : null;
            $result['channels'][$channel] = [
                'pending' => $rows ? (clone $rows)->where('status', 'pending')->count() : 0,
                'processing' => $rows ? (clone $rows)->where('status', 'processing')->count() : 0,
                'failed' => $rows ? (clone $rows)->where('status', 'failed')->count() : 0,
                'last_sent' => $lastSent ? Carbon::parse($lastSent)->timezone(config('app.timezone'))->format('d M Y H:i:s T') : null,
                'test_at' => $settings->get('gateway_test_'.$channel.'_at'),
                'test_status' => $settings->get('gateway_test_'.$channel.'_status'),
            ];
        }
        return $result;
    }
}
