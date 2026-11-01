<section class="finance-pipeline" aria-label="Tahap pemrosesan laporan">
    @foreach(['pending_import'=>['Impor & cocokkan','Cocokkan ISRC dengan lagu dan akun.','upload_file'],'pending_review'=>['Tinjau laporan','Periksa hasil pencocokan lalu finalisasi.','fact_check'],'pending_rate'=>['Isi kurs','Tetapkan kurs sebelum perhitungan.','currency_exchange'],'pending_calculation'=>['Hitung pendapatan','Jalankan worker atau Calculation Manual.','calculate'],'completed'=>['Selesai','Pendapatan sudah dihitung.','task_alt'],'failed'=>['Perlu diperiksa','Buka detail untuk memeriksa kegagalan.','error_outline']] as $status=>$step)
    <button class="finance-stage" type="button" data-report-status="{{ $status }}"><span class="material-icons-outlined">{{ $step[2] }}</span><strong>{{ number_format($reportingSummary['statuses'][$status]??0) }}</strong><b>{{ $step[0] }}</b><small>{{ $step[1] }}</small></button>
    @endforeach
</section>
<p class="finance-explainer">Klik tahap untuk menyaring daftar file laporan. Urutan proses: upload → pencocokan ISRC → review & finalisasi → kurs → calculation. File yang sama tidak perlu di-upload ulang untuk melanjutkan proses.</p>
