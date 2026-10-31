@php
    $channelLabel = $channel === 'email' ? 'Email' : 'WhatsApp';
    $configuration = $notificationConfigurations[$channel];
    $template = $configuration['templates'][$notificationEvent];
    $hasOld = old('section') === 'notifications-'.$channel;
    $field = fn ($key,$fallback) => $hasOld ? old($key,$fallback) : $fallback;
    $history = $notificationHistory->get($channel, collect());
    $deliveryLabels = ['pending'=>'Menunggu worker','processing'=>'Sedang dikirim','sent'=>'Diterima penyedia','failed'=>'Perlu diperiksa','skipped'=>'Dilewati'];
@endphp
<div class="card mb-4"><div class="card-body p-4">
    <h4 class="d-flex align-items-center gap-2"><span class="material-icons-outlined" aria-hidden="true">{{ $channel === 'email' ? 'mail_outline' : 'chat' }}</span>Notifikasi {{ $channelLabel }}</h4>
    <p class="text-secondary">Atur pesan otomatis untuk pemilik akun ketika status pengguna atau rilis berubah. Pilih status, sesuaikan pesan, lalu lihat pratinjau sebelum menyimpan.</p>
    @if(!$notificationsReady)<div class="alert alert-warning">Pencatatan dan pengiriman status belum aktif. Jalankan migrasi fitur notifikasi pada hosting. Template tetap dapat disiapkan dan disimpan.</div>@endif
    @if($channel === 'email' && !$settings['smtp_enabled'])<div class="alert alert-info">SMTP belum aktif. Atur koneksi SMTP pada <strong>Pengaturan Umum</strong> agar email dapat dikirim.</div>@endif
    @if($channel === 'whatsapp' && !$settings['has_whatsapp_token'])<div class="alert alert-info">Isi token device Fonnte pada <strong>Pengaturan Umum</strong> sebelum mengaktifkan pengiriman WhatsApp.</div>@endif
    <form method="GET" action="{{ route('admin.system-settings.index') }}" class="mb-4">
        <input type="hidden" name="tab" value="notifications-{{ $channel }}">
        <label class="form-label" for="notification-event-{{ $channel }}">Status yang ingin diatur</label>
        <div class="d-flex flex-wrap gap-2"><select class="form-select" style="max-width:420px" id="notification-event-{{ $channel }}" name="notification_event">
            @foreach(collect($notificationEvents)->groupBy('group', true) as $group=>$events)<optgroup label="{{ $group }}">@foreach($events as $key=>$event)<option value="{{ $key }}" @selected($notificationEvent === $key)>{{ $event['label'] }}</option>@endforeach</optgroup>@endforeach
        </select><button class="btn btn-outline-primary" type="submit">Buka Template</button></div>
        <div class="form-text">Simpan perubahan sebelum membuka template status lain.</div>
    </form>
    <div class="row g-4">
        <div class="col-xl-6">
            <form method="POST" action="{{ route('admin.system-settings.notifications.save',$channel) }}">
                @csrf<input type="hidden" name="section" value="notifications-{{ $channel }}"><input type="hidden" name="event" value="{{ $notificationEvent }}">
                <input type="hidden" name="channel_enabled" value="0"><input type="hidden" name="event_enabled" value="0">
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="notification-channel-{{ $channel }}" name="channel_enabled" value="1" @checked($field('channel_enabled',$configuration['enabled']))><label class="form-check-label" for="notification-channel-{{ $channel }}">Aktifkan notifikasi {{ $channelLabel }}</label></div>
                <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="notification-enabled-{{ $channel }}" name="event_enabled" value="1" @checked($field('event_enabled',$template['enabled']))><label class="form-check-label" for="notification-enabled-{{ $channel }}">Kirim saat {{ $notificationEvents[$notificationEvent]['label'] }}</label></div>
                @if($channel === 'email')
                    <div class="mb-3"><label class="form-label" for="notification-subject-{{ $channel }}">Subjek Email</label><input class="form-control" id="notification-subject-{{ $channel }}" name="subject" value="{{ $field('subject',$template['subject']) }}" maxlength="180" required></div>
                @else<input type="hidden" name="subject" value="{{ $field('subject',$template['subject']) }}">@endif
                <div class="mb-3"><label class="form-label" for="notification-heading-{{ $channel }}">Judul Pesan</label><input class="form-control" id="notification-heading-{{ $channel }}" name="heading" value="{{ $field('heading',$template['heading']) }}" maxlength="180" required></div>
                <div class="mb-3"><label class="form-label" for="notification-body-{{ $channel }}">Isi Pesan</label><textarea class="form-control" id="notification-body-{{ $channel }}" name="body" rows="11" maxlength="8000" required>{{ $field('body',$template['body']) }}</textarea><div class="form-text">Tulis teks biasa. Baris baru dipertahankan; placeholder akan diisi sesuai data penerima dan rilis.</div></div>
                <div class="mb-3"><label class="form-label" for="notification-button-{{ $channel }}">{{ $channel === 'email' ? 'Teks Tombol' : 'Label Tautan' }}</label><input class="form-control" id="notification-button-{{ $channel }}" name="button" value="{{ $field('button',$template['button']) }}" maxlength="100" required></div>
                <div class="mb-3"><label class="form-label" for="notification-footer-{{ $channel }}">Footer Semua Notifikasi {{ $channelLabel }}</label><textarea class="form-control" id="notification-footer-{{ $channel }}" name="footer" rows="3" maxlength="1000" required>{{ $field('footer',$configuration['footer']) }}</textarea></div>
                @if($channel === 'email')<div class="mb-3"><label class="form-label" for="notification-accent-{{ $channel }}">Warna Aksen Email</label><input class="form-control form-control-color" type="color" id="notification-accent-{{ $channel }}" name="accent" value="{{ $field('accent',$configuration['accent']) }}"></div>@else<input type="hidden" name="accent" value="{{ $configuration['accent'] }}">@endif
                <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit">Simpan Notifikasi {{ $channelLabel }}</button><button class="btn btn-outline-primary" type="submit" formaction="{{ route('admin.system-settings.notifications.preview',$channel) }}" formtarget="notification-preview-{{ $channel }}">Pratinjau Perubahan</button></div>
            </form>
        </div>
        <div class="col-xl-6">
            <div class="d-flex align-items-center justify-content-between mb-2"><h6 class="mb-0">Pratinjau {{ $channelLabel }}</h6><small class="text-secondary">Data contoh · Tidak dikirim</small></div>
            <iframe name="notification-preview-{{ $channel }}" title="Pratinjau notifikasi {{ $channelLabel }}" src="{{ route('admin.system-settings.notifications.preview', ['channel'=>$channel,'event'=>$notificationEvent]) }}" sandbox="allow-same-origin" loading="lazy" style="display:block;width:100%;height:670px;border:1px solid var(--bs-border-color);border-radius:12px;background:#f3f5f9"></iframe>
            <details class="mt-3" open><summary>Placeholder yang tersedia</summary><div class="d-flex flex-wrap gap-2 mt-2">@foreach(\App\Services\StatusNotificationTemplates::TOKENS as $token)<code>{{ '{'.$token.'}' }}</code>@endforeach</div><p class="form-text mt-2">Gunakan nama placeholder persis seperti di atas. {tautan} selalu menuju halaman akun atau rilis terkait. Informasi rilis hanya tersedia pada status rilis.</p></details>
        </div>
    </div>
</div></div>
<div class="card"><div class="card-body p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><h5 class="mb-0">Riwayat Notifikasi {{ $channelLabel }}</h5><a class="btn btn-outline-primary" href="{{ route('admin.system-settings.index',['tab'=>'notifications-'.$channel,'notification_event'=>$notificationEvent]) }}">Perbarui Riwayat</a></div>
    <p class="text-secondary">Pesan dikirim melalui worker setelah perubahan status berhasil disimpan. Mengubah template tidak mengirim ulang riwayat lama. Status diterima penyedia belum memastikan pesan dibaca penerima.</p>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Status Pemicu</th><th>User</th><th>Pengiriman</th><th>Waktu</th><th>Keterangan / Aksi</th></tr></thead><tbody>
        @forelse($history as $delivery)<tr><td>{{ $notificationEvents[$delivery->event]['label'] ?? $delivery->event }}</td><td>#{{ $delivery->user_id }}</td><td>{{ $deliveryLabels[$delivery->status] ?? $delivery->status }}</td><td>{{ $delivery->updated_at->format('d-m-Y H:i') }}</td><td>{{ $delivery->error ?: '—' }}
            @if(in_array($delivery->status,['failed','skipped'],true))<form class="mt-2" method="POST" action="{{ route('admin.system-settings.notifications.retry',$delivery->id) }}">@csrf<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="notification-check-{{ $delivery->id }}" name="provider_checked" value="1" required><label class="form-check-label" for="notification-check-{{ $delivery->id }}">Sudah memeriksa penyedia dan memastikan pesan belum terkirim.</label></div><button class="btn btn-sm btn-outline-primary" type="submit">Jadwalkan Ulang</button></form>@endif
        </td></tr>@empty<tr><td colspan="5" class="text-center text-secondary">Belum ada notifikasi status pada kanal ini.</td></tr>@endforelse
    </tbody></table></div>
    <details class="mt-3"><summary>Aktivasi pengiriman di hosting</summary><p class="mt-2">Setelah deploy, jalankan migrasi khusus berikut. Ini menyiapkan tabel riwayat notifikasi tanpa menjalankan ulang migrasi awal database:</p><pre class="border rounded p-3 text-wrap"><code>php artisan migrate --path=database/migrations/2026_10_31_120000_create_status_notification_deliveries.php --force</code></pre><p>Worker antrean default dan jadwal Laravel yang sudah ada dapat memproses notifikasi. Jika worker belum aktif, jalankan:</p><pre class="border rounded p-3 text-wrap"><code>php artisan queue:work --queue=default --timeout=60 --tries=1</code></pre><p class="form-text">Pengiriman WhatsApp mengikuti device dan kuota Fonnte. Notifikasi hanya untuk perubahan status baru; impor database lama tidak memicu pengiriman massal.</p></details>
</div></div>
