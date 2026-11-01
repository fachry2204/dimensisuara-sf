@if($report->status === 'pending_calculation')
<section class="card mb-4" aria-label="Calculation Manual">
    <div class="card-body">
        <h5>Calculation Manual</h5>
        <p>Jika automation atau worker belum berjalan, hitung pendapatan laporan ini langsung dari halaman ini. Pastikan kurs EUR ke IDR sudah benar sebelum memulai.</p>
        <p id="report-calculation-progress" role="status" aria-live="polite">Laporan menunggu perhitungan pendapatan.</p>
        @can('upload-reporting')
        <button id="calculate-report-manual" type="button" class="btn btn-primary">Calculation Manual</button>
        @endcan
        <a href="{{ route('admin.releases.reporting.show',$report->id) }}" class="btn btn-outline-primary">Perbarui Progres</a>
        <p class="form-text mb-0 mt-2">Maksimal 250 baris per tahap. Biarkan halaman terbuka sampai selesai. Jika terputus, klik kembali untuk melanjutkan. Hasil yang sudah dihitung dilewati; ringkasan diperbarui setelah seluruh baris selesai.</p>
    </div>
</section>
@can('upload-reporting')
@push('script')
<script>
document.getElementById('calculate-report-manual').addEventListener('click', async function () {
    const button = this;
    const progress = document.getElementById('report-calculation-progress');
    button.disabled = true;
    button.textContent = 'Menghitung…';
    try {
        let done = false;
        while (!done) {
            const response = await fetch(@json(route('admin.releases.reporting.calculate',$report->id)), {
                method: 'POST', headers: {'Accept':'application/json', 'X-CSRF-TOKEN':@json(csrf_token())}
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Perhitungan gagal.');
            progress.textContent = `${result.processed} dari ${result.total} baris dihitung. ${result.remaining} baris tersisa.`;
            done = result.done;
        }
        progress.textContent = 'Calculation selesai. Ringkasan pendapatan sudah diperbarui. Membuka hasil…';
        window.location.reload();
    } catch (error) {
        progress.textContent = `${error.message} Klik Calculation Manual untuk melanjutkan.`;
        button.disabled = false;
        button.textContent = 'Calculation Manual';
    }
});
</script>
@endpush
@endcan
@endif
