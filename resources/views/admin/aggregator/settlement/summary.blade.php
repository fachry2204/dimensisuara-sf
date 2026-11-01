<section class="finance-status-grid" aria-label="Status transaksi sesuai filter">
    <article class="finance-stat"><span class="material-icons-outlined">receipt_long</span><div><small>Total transaksi</small><strong id="transaction-count">Memuat…</strong><p>Seluruh status sesuai filter</p></div></article>
    <article class="finance-stat"><span class="material-icons-outlined">schedule</span><div><small>Menunggu transfer</small><strong id="transaction-pending-amount">Memuat…</strong><p><span id="transaction-pending-count">—</span> transaksi Pending</p></div></article>
    <article class="finance-stat"><span class="material-icons-outlined">task_alt</span><div><small>Sudah ditransfer</small><strong id="transaction-transferred-amount">Memuat…</strong><p><span id="transaction-transferred-count">—</span> transaksi Transferred</p></div></article>
    <article class="finance-stat"><span class="material-icons-outlined">forward</span><div><small>Dibawa ke periode berikutnya</small><strong id="transaction-carried-count">Memuat…</strong><p>Carried Over • belum ditransfer</p></div></article>
</section>
<section class="finance-money-grid" aria-label="Ringkasan pembagian pendapatan">
    <article class="finance-money"><small>Pendapatan dasar</small><strong id="sum-total">Memuat…</strong><p>Pendapatan sebelum pembagian</p></article>
    <article class="finance-money"><small>Bagian sistem</small><strong id="sum-aggregator">Memuat…</strong><p>Porsi aggregator dari pendapatan dasar</p></article>
    <article class="finance-money"><small>Bagian user sebelum pajak</small><strong id="sum-client">Memuat…</strong><p>Pendapatan dasar dikurangi bagian sistem</p></article>
</section>
<p class="finance-explainer">Ringkasan pembagian hanya menghitung status Pending dan Transferred sesuai filter, sehingga transaksi Carried Over tidak dihitung kembali. Nominal transfer memakai nilai Total transaksi setelah penyesuaian dan pajak. Pencarian tabel hanya menyaring baris, bukan ringkasan.</p>
