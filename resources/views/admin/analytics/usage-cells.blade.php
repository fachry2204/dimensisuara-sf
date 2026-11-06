@foreach(\App\Services\ReportUsage::LABELS as $usageMetric => $usageLabel)
    <td>{{ $number(data_get($usageRow, $usageMetric, 0)) }}</td>
@endforeach
