@props(['totals' => [], 'platforms' => null])
@php($usageLabels = \App\Services\ReportUsage::LABELS)
<section class="report-usage-panel mb-3" aria-label="Jenis penggunaan dalam laporan">
    <h2>Penggunaan berdasarkan Sales Type</h2>
    <p>Setiap kategori dihitung terpisah sesuai Sales Type pada laporan. VIDEO VIEW, CREATION, dan kategori iklan tidak dihitung sebagai STREAMING. Jenis yang belum dikenal masuk Unit lainnya. Total penghasilan mencakup seluruh kategori.</p>
    <dl class="report-usage-totals">
        @foreach($usageLabels as $metric => $label)
            <div><dt>{{ $label }}</dt><dd>{{ number_format((float) data_get($totals, $metric, 0), 0, ',', '.') }}</dd></div>
        @endforeach
    </dl>
    @if($platforms !== null)
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Platform</th>@foreach($usageLabels as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
            @forelse($platforms as $platform)
                <tr><td>{{ $platform->platform ?: 'Tidak diketahui' }}</td>@foreach($usageLabels as $metric => $label)<td>{{ number_format((float) data_get($platform, $metric, 0), 0, ',', '.') }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="7">Belum ada data penggunaan pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
    @endif
</section>
