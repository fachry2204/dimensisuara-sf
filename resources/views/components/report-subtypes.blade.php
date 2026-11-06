@props(['rows', 'exportUrl' => null, 'revenueLabel' => 'Estimasi penghasilan', 'calculated' => true])
<section class="report-usage-panel report-subtypes-panel mb-3" aria-label="Rincian Sales Sub Type">
    <div class="report-subtypes-heading">
        <h2>Rincian Sales Sub Type</h2>
        @if($exportUrl)<a class="btn btn-outline-primary" href="{{ $exportUrl }}">Ekspor Sales Sub Type (CSV)</a>@endif
    </div>
    <p>Rincian dikelompokkan per platform serta Sales Type. Sales Sub Type yang kosong atau belum tersimpan ditandai “-”. DEFAULT adalah subtipe tersendiri.</p>
    <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Platform</th><th>Sales Type</th><th>Sales Sub Type</th><th>Quantity</th><th>{{ $revenueLabel }}</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr><td>{{ $row->platform }}</td><td>{{ \App\Services\ReportUsage::display($row->sales_type) }}</td><td>{{ \App\Services\ReportUsage::display($row->sales_sub_type) }}</td><td>{{ number_format((float) $row->quantity,0,',','.') }}</td><td>{{ $calculated ? 'Rp '.number_format((float) $row->earnings,0,',','.') : 'Belum dihitung' }}</td></tr>
        @empty
            <tr><td colspan="5">Belum ada rincian Sales Sub Type pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
