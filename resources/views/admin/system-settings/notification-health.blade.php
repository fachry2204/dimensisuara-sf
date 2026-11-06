<section class="card mb-4" aria-label="Status otomatisasi pengiriman">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h5 class="mb-1">Status otomatisasi pengiriman</h5><p class="text-secondary mb-0">Aktivitas pada server aplikasi ini. Muat ulang untuk memperbarui status.</p></div>
            <a class="btn btn-outline-primary btn-sm" href="{{ request()->fullUrl() }}">Perbarui status</a>
        </div>
        <div class="row g-3">
            @foreach(['scheduler' => 'Scheduled Task / Scheduler', 'worker' => 'Worker antrean notifikasi'] as $component => $label)
                @php($healthItem = $notificationHealth[$component])
                <div class="col-md-6"><div class="border rounded-3 p-3 h-100">
                    <div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ $label }}</strong><span class="badge {{ $healthItem['state'] === 'active' ? 'bg-success' : ($healthItem['state'] === 'stale' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ['active' => 'Aktif', 'stale' => 'Tidak terdeteksi baru-baru ini', 'unknown' => 'Belum terdeteksi'][$healthItem['state']] }}</span></div>
                    <small class="d-block mt-2 text-secondary">Aktivitas terakhir: {{ $healthItem['last_at'] ?: 'Belum tercatat' }}</small>
                </div></div>
            @endforeach
        </div>
        <p class="form-text mt-3">Aktif berarti ada aktivitas dalam 3 menit terakhir. Belum terdeteksi berarti belum ada catatan sejak fitur dipasang; bukan bukti task mati. Status worker menunjukkan pemeriksaan antrean, bukan jaminan semua pesan berhasil dikirim.</p>
        @if(!$notificationHealth['deliveries_ready'])
            <p class="alert alert-warning">Tabel riwayat notifikasi belum tersedia. Jalankan migrasi aplikasi untuk melihat jumlah antrean dan riwayat pengiriman.</p>
        @else
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Saluran</th><th>Menunggu</th><th>Diproses</th><th>Gagal</th><th>Pengiriman terakhir tercatat</th></tr></thead><tbody>
            @foreach(['email' => 'Email', 'whatsapp' => 'WhatsApp'] as $channel => $label)
                <tr><td>{{ $label }}</td><td>{{ $notificationHealth['channels'][$channel]['pending'] }}</td><td>{{ $notificationHealth['channels'][$channel]['processing'] }}</td><td>{{ $notificationHealth['channels'][$channel]['failed'] }}</td><td>{{ $notificationHealth['channels'][$channel]['last_sent'] ?: 'Belum ada' }}</td></tr>
            @endforeach
        </tbody></table></div>
        @endif
        @include('admin.system-settings.scheduler-help')
    </div>
</section>
