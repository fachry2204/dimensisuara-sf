<?php

namespace App\Services;

use App\Jobs\SendStatusNotification;
use App\Models\Release;
use App\Models\StatusNotificationDelivery;
use App\Models\User\Profile;
use App\Models\User\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StatusNotificationDispatcher
{
    public function profile(Profile $profile, ?string $event = null): void
    {
        $event ??= 'user.'.$profile->status;
        if (!$this->supports($event) || !$profile->user) return;
        $this->capture($profile->user,$profile,$event);
    }

    public function release(Release $release): void
    {
        $event = 'release.'.$release->status;
        if (!$this->supports($event) || !$release->profile?->user) return;
        $this->capture($release->profile->user,$release->profile,$event,$release);
    }

    private function supports(string $event): bool
    {
        return Schema::hasTable('status_notification_deliveries') && isset(app(StatusNotificationTemplates::class)->events()[$event]);
    }

    private function capture(User $user, Profile $profile, string $event, ?Release $release = null): void
    {
        if ($user instanceof \App\Models\User\Admin) return;
        $templates = app(StatusNotificationTemplates::class);
        $source = $release ?? $profile;
        $previous = ($release ? 'release.' : 'user.').($source->getRawOriginal('status') ?? '');
        $artists = $release?->primaryartists ?? [];
        if (is_string($artists)) $artists = json_decode($artists,true) ?: [];
        $date = $release?->finalreleaseddate ?: ($release?->releaseon ?: $release?->releaseddate);
        $isrc = $release ? $release->tracks()->pluck('tracks.isrc')
            ->map(fn ($code) => trim((string) $code))->filter()->unique()->join(', ') : '';
        $context = ['nama'=>$user->name,'aplikasi'=>app_name(),'status'=>$templates->events()[$event]['status'],
            'status_sebelumnya'=>$templates->events()[$previous]['status'] ?? '—',
            'judul_rilis'=>$release?->title ?? '', 'artis'=>collect($artists)->map(fn ($artist) => is_string($artist) ? $artist : data_get($artist,'name'))->filter()->join(', '),
            'upc'=>$release?->upc ?: 'Belum tersedia',
            'isrc'=>$isrc !== '' ? $isrc : 'Belum tersedia',
            'tanggal_rilis'=>$date ? \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : 'Belum dijadwalkan',
            'alasan'=>$source->reason ?: 'Tidak ada catatan tambahan.',
            'tautan'=>$release ? route('releases.show',$release->id) : route('profile')];
        $eventId = (string) Str::uuid();
        foreach (['email','whatsapp'] as $channel) {
            $config = $templates->configuration($channel);
            if (!$config['enabled'] || !$config['templates'][$event]['enabled']) continue;
            $recipient = $channel === 'email' ? $user->email : $profile->phone;
            $snapshot = $templates->render($channel,$event,$context,$config);
            $delivery = StatusNotificationDelivery::create(['event_id'=>$eventId,'event'=>$event,'channel'=>$channel,
                'user_id'=>$user->id,'release_id'=>$release?->id,
                'payload'=>['recipient'=>$recipient,'message'=>$snapshot]]);
            SendStatusNotification::dispatch($delivery->id)->afterCommit();
        }
    }
}
