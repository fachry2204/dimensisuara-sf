<div class="border-top mt-4 pt-4">
    <h6>Tes kirim {{ $channel === 'email' ? 'Email' : 'WhatsApp' }}</h6>
    <p class="form-text">Simpan konfigurasi di atas terlebih dahulu. Tes dikirim langsung melalui gateway tersimpan sehingga bisa diuji meskipun scheduler belum berjalan.</p>
    <form action="{{ route('admin.system-settings.gateway.test', $channel) }}" method="POST" class="row g-2 align-items-end" data-gateway-test>
        @csrf
        <div class="col-md-8"><label for="test-{{ $channel }}-recipient" class="form-label">{{ $channel === 'email' ? 'Email penerima tes' : 'Nomor WhatsApp penerima tes' }}</label><input id="test-{{ $channel }}-recipient" class="form-control" name="recipient" type="{{ $channel === 'email' ? 'email' : 'tel' }}" maxlength="{{ $channel === 'email' ? 255 : 16 }}" placeholder="{{ $channel === 'email' ? 'nama@example.com' : '6281234567890' }}" required></div>
        <div class="col-md-4"><button class="btn btn-outline-primary" type="submit">Kirim tes {{ $channel === 'email' ? 'Email' : 'WhatsApp' }}</button></div>
    </form>
    @if($notificationHealth['channels'][$channel]['test_at'])
        <small class="d-block mt-3 text-secondary">Tes terakhir: {{ $notificationHealth['channels'][$channel]['test_at'] }} — {{ $notificationHealth['channels'][$channel]['test_status'] === 'accepted' ? 'Diterima gateway; periksa penerima' : 'Gagal / belum dapat dipastikan' }}.</small>
    @endif
</div>
