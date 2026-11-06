@extends('layouts.app')
@section('title', 'Riwayat Notifikasi '.$channelLabel)
@section('header')
    <div class="automation-header"><div><h1>Automation</h1><p>Pantau pekerjaan otomatis dan riwayat pengiriman notifikasi.</p></div></div>
@endsection
@section('content')
    @include('admin.automation.tabs')
    <section class="card automation-panel"><div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><h5 class="mb-0">Riwayat Notifikasi {{ $channelLabel }}</h5><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.automation.index',['tab'=>$channel]) }}">Perbarui Riwayat</a></div>
        <p class="text-secondary">Pesan dikirim melalui worker setelah perubahan status berhasil disimpan. Status diterima penyedia belum memastikan pesan dibaca penerima.</p>
        @if(!$notificationsReady)
            <div class="alert alert-warning">Riwayat belum tersedia. Jalankan migrasi fitur notifikasi pada hosting.</div>
        @else
            @php($deliveryLabels = ['pending'=>'Menunggu worker','processing'=>'Sedang dikirim','sent'=>'Diterima penyedia','failed'=>'Perlu diperiksa','skipped'=>'Dilewati'])
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Status Pemicu</th><th>User</th><th>Pengiriman</th><th>Waktu</th><th>Keterangan / Aksi</th></tr></thead><tbody>
                @forelse($history as $delivery)
                    <tr><td>{{ $notificationEvents[$delivery->event]['label'] ?? $delivery->event }}</td><td>#{{ $delivery->user_id }}</td><td>{{ $deliveryLabels[$delivery->status] ?? $delivery->status }}</td><td class="text-nowrap">{{ $delivery->updated_at->format('d-m-Y H:i') }}</td><td>{{ $delivery->error ?: '—' }}
                        @if(in_array($delivery->status,['failed','skipped'],true))
                            <form class="mt-2" method="POST" action="{{ route('admin.automation.notifications.retry',$delivery->id) }}">@csrf
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="notification-check-{{ $delivery->id }}" name="provider_checked" value="1" required><label class="form-check-label" for="notification-check-{{ $delivery->id }}">Sudah memeriksa penyedia dan memastikan pesan belum terkirim.</label></div>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Jadwalkan Ulang</button>
                            </form>
                        @endif
                    </td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary">Belum ada notifikasi status pada kanal ini.</td></tr>
                @endforelse
            </tbody></table></div>
            {{ $history->links('pagination::bootstrap-5') }}
        @endif
        <a class="btn btn-outline-primary btn-sm mt-3" href="{{ route('admin.system-settings.index', ['tab'=>'notifications-'.$channel]) }}">Pengaturan Notifikasi {{ $channelLabel }}</a>
    </div></section>
@endsection
