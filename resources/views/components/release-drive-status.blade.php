@props(['storage'])
<section class="col-12 release-drive-panel" aria-label="Status file Google Drive">
    <div class="release-drive-heading">
        <h3>Status File Google Drive</h3>
        <a class="btn btn-outline-primary" href="{{ url()->current() }}">Perbarui status file</a>
    </div>
    <div class="release-drive-body">
        <p>Status berdasarkan catatan pemindahan aplikasi. Muat ulang untuk memperbarui progres.</p>
        <p class="release-drive-summary"><strong>{{ $storage['counts']['verified'] }} dari {{ $storage['counts']['files'] }} file terverifikasi di Drive</strong><span>{{ $storage['counts']['not_uploaded'] }} belum terverifikasi</span><span>{{ $storage['counts']['failed'] }} gagal</span></p>
        @if(!$storage['history_ready'])<p>Riwayat pemindahan Google Drive belum tersedia. Periksa pengaturan penyimpanan aplikasi.</p>@endif
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Jenis file</th><th>Nama file</th><th>Lokasi tercatat</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
                @foreach($storage['rows'] as $row)
                    <tr>
                        <td><strong>{{ $row['type'] }}</strong><small>{{ $row['title'] }}</small></td>
                        <td>{{ $row['name'] }}@if($row['file_id'])<small>ID file #{{ $row['file_id'] }}</small>@endif</td>
                        <td>{{ $row['location'] }}</td>
                        <td><span class="release-drive-status release-drive-status--{{ $row['tone'] }}">{{ $row['status'] }}</span></td>
                        <td>{{ $row['detail'] }}
                            @if($row['verified_at'])<small>Terverifikasi: {{ \Carbon\Carbon::parse($row['verified_at'])->format('d M Y H:i') }}</small>
                            @elseif($row['updated_at'])<small>Proses terakhir: {{ \Carbon\Carbon::parse($row['updated_at'])->format('d M Y H:i') }}</small>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
</section>
