@extends('layouts.app')
@section('title','Status Upload Google Drive')
@section('header')
<div class="admin-list-header"><div><h1>Automation</h1><p>Pantau pemindahan file rilisan Published dan penataan folder Google Drive.</p></div></div>
@endsection
@section('content')
@include('admin.automation.tabs')
@php
    $labels=['all'=>'Total file','pending'=>'Belum diunggah / antre','uploading'=>'Sedang diproses','uploaded'=>'Sudah terverifikasi','failed'=>'Gagal'];
    $transferLabels=['pending'=>'Menunggu worker','copying'=>'Mengunggah','complete'=>'Selesai','cleanup_pending'=>'Drive terverifikasi; pembersihan lokal tertunda','relocating'=>'Menata folder Drive','remote_cleanup_pending'=>'Folder baru siap; pembersihan lokasi lama tertunda','relocation_failed'=>'Penataan folder gagal','failed'=>'Unggahan gagal'];
@endphp
<div class="drive-monitor-counts">
@foreach($labels as $key=>$label)<a href="{{ route('admin.automation.index',['tab'=>'drive','status'=>$key]) }}" @if($status===$key) aria-current="page" @endif><span>{{ $label }}</span><strong>{{ number_format($stats[$key],0,',','.') }}</strong></a>@endforeach
</div>
<section class="drive-monitor-panel">
    <div class="drive-monitor-heading"><h2>Status Upload Google Drive</h2><a class="btn btn-outline-primary" href="{{ route('admin.automation.index',['tab'=>'drive','status'=>$status]) }}">Perbarui status</a></div>
    <p>Struktur tujuan: <strong>Nama Client / Nama Artis / Judul Release</strong>. Nama folder client dan rilisan dilengkapi ID agar nama yang sama tidak tertukar. Audio bersama memakai satu salinan di folder rilisan Published utama.</p>
    <p>File lokal dihapus setelah ukuran dan checksum Drive cocok serta lokasi baru tersimpan. Status mengikuti proses terakhir yang dicatat worker.</p>
    <p>Proses berjalan bertahap melalui worker. Jadwal otomatis melanjutkan file berikutnya sampai selesai.</p>
    <p>Thumbnail tetap disimpan di server dan tidak termasuk antrean unggah Drive. Thumbnail lama di Drive dikembalikan melalui worker; statusnya dapat dilihat pada detail rilisan.</p>
    @if(!$layoutReady)<div class="alert alert-warning">Pembaruan riwayat penataan folder belum tersedia. Pasang migrasi Drive sebelum memulai pemindahan.</div>@endif
    @if(!$driveEnabled || !$connectionOk)<div class="alert alert-warning">Selesaikan Tes Koneksi dan aktifkan Google Drive pada pengaturan sistem.</div>@endif
    @if($other)<p>{{ $other }} file berada pada penyimpanan lain seperti FTP dan tidak termasuk pemindahan file server lokal.</p>@endif
    <div class="d-flex flex-wrap gap-2 mb-3"><form method="POST" action="{{ route('admin.automation.drive.start') }}">@csrf<button class="btn btn-primary" @disabled(!$layoutReady || !$driveEnabled || !$connectionOk)>Proses semua file Published</button></form><a class="btn btn-outline-primary" href="{{ route('admin.system-settings.index',['tab'=>'google-drive']) }}">Pengaturan Google Drive</a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Client / Artis / Rilisan utama</th><th>File</th><th>Lokasi</th><th>Status terakhir</th><th>Waktu verifikasi</th></tr></thead><tbody>
    @forelse($files as $file)
        <tr><td>@if($file->archive_context)<strong>{{ $file->archive_context['client'] }}</strong><br>{{ $file->archive_context['artist'] }}<br><a href="{{ route('admin.releases.show',$file->archive_context['release']->id) }}">{{ $file->archive_context['release']->title }}</a>@else Metadata belum tersedia @endif</td>
        <td>{{ $file->original_name ?: 'File #'.$file->id }}<br><small>{{ ['cover'=>'Cover','thumbnail'=>'Thumbnail','ipl'=>'IPL','full'=>'Audio penuh','trimmed'=>'Audio potongan'][$file->type] ?? $file->type }} · #{{ $file->id }}</small>@if($file->disk==='rclone')<br><small>Nama di Drive: {{ basename($file->identifier) }}</small>@endif</td>
        <td>{{ $file->disk==='rclone'?'Google Drive':'Server lokal' }}</td><td>{{ $transferLabels[$file->transfer_status] ?? ($file->disk==='local'?'Belum diunggah':'Belum terverifikasi') }}</td><td>{{ $file->verified_at ? \Carbon\Carbon::parse($file->verified_at)->format('d M Y H:i') : '-' }}</td></tr>
    @empty<tr><td colspan="5">Tidak ada file untuk status yang dipilih.</td></tr>@endforelse
    </tbody></table></div>
    {{ $files->links('pagination::bootstrap-5') }}
</section>
@endsection
@push('css')
<style>
html[data-bs-theme="dark"] body.sf-app .main-content .drive-monitor-panel .btn-outline-primary{color:var(--sf-text)!important}
.drive-monitor-counts{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:24px}.drive-monitor-counts a{display:block;min-height:100px;padding:18px;border:1px solid var(--sf-border);border-radius:14px;color:var(--sf-text);text-decoration:none}.drive-monitor-counts strong{display:block;font-size:1.7rem}.drive-monitor-counts a[aria-current="page"]{outline:2px solid var(--sf-accent)}.drive-monitor-counts a:hover,.drive-monitor-counts a:focus-visible{outline:3px solid var(--sf-accent);outline-offset:2px}.drive-monitor-panel{padding:24px;border:1px solid var(--sf-border);border-radius:16px;min-width:0}.drive-monitor-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}.drive-monitor-heading h2{font-size:1.4rem;margin:0}.drive-monitor-panel .btn{min-height:44px}.drive-monitor-panel table{min-width:850px}.drive-monitor-panel td{overflow-wrap:anywhere}.drive-monitor-panel small{color:var(--sf-muted)}html[data-bs-theme="light"] .drive-monitor-panel,html[data-bs-theme="light"] .drive-monitor-counts a{background:#fff}html[data-bs-theme="dark"] .drive-monitor-panel,html[data-bs-theme="dark"] .drive-monitor-counts a{background:#111e31}@media(max-width:900px){.drive-monitor-counts{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575px){.drive-monitor-panel{padding:16px}.drive-monitor-counts{grid-template-columns:1fr}}
</style>
@endpush
