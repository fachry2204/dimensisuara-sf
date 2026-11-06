@foreach(\App\Services\ReportUsage::LABELS as $usageLabel)
    <th>{{ $usageLabel }}</th>
@endforeach
