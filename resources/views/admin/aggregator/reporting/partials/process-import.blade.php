@if($report->status === 'pending_import' || ($report->status === 'failed' && $pendingRows > 0))
<section class="card mb-4" aria-label="Proses impor laporan">
    <div class="card-body">
        <h5>Proses Impor Laporan</h5>
        <p>Data laporan sudah tersimpan. Proses berikutnya mencocokkan ISRC dengan lagu dan pemiliknya. Setelah selesai, tinjau hasilnya sebelum finalisasi dan perhitungan pendapatan.</p>
        <p id="report-import-progress" role="status" aria-live="polite">{{ number_format($pendingRows) }} baris masih Pending.</p>
        @can('upload-reporting')
        <button id="process-report-import" type="button" class="btn btn-primary">Proses Laporan</button>
        @endcan
        <a href="{{ route('admin.releases.reporting.show',$report->id) }}" class="btn btn-outline-primary">Perbarui Progres</a>
        <p class="form-text mb-0 mt-2">Tombol memproses maksimal 250 baris per tahap tanpa worker antrean. Biarkan halaman terbuka sampai selesai. Jika terputus, klik kembali untuk melanjutkan baris yang belum diproses.</p>
    </div>
</section>
@can('upload-reporting')
@push('script')
<script>
document.getElementById('process-report-import').addEventListener('click', async function() {
    const button = this;
    const progress = document.getElementById('report-import-progress');
    button.disabled = true;
    button.textContent = 'Memproses Laporan…';
    try {
        let done = false;
        while (!done) {
            const response = await fetch(@json(route('admin.releases.reporting.process',$report->id)), {
                method: 'POST', headers: {'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Pemrosesan gagal.');
            progress.textContent = `${result.processed} dari ${result.total} baris diproses. ${result.remaining} baris tersisa.`;
            done = result.done;
        }
        progress.textContent += ' Selesai. Membuka hasil untuk ditinjau…';
        window.location.reload();
    } catch (error) {
        progress.textContent = `${error.message} Klik Proses Laporan untuk melanjutkan.`;
        button.disabled = false;
        button.textContent = 'Proses Laporan';
    }
});
</script>
@endpush
@endcan
@endif
