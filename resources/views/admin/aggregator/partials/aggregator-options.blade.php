@php($aggregatorNames = app(\App\Services\SystemSettings::class)->aggregatorNames())
<option value="" @selected(!$selectedAggregator)>-- Unassigned --</option>
@foreach($aggregatorNames as $name)
    <option value="{{ $name }}" @selected($selectedAggregator === $name)>{{ $name }}</option>
@endforeach
@if(!empty($legacyAggregator) && !in_array($legacyAggregator, $aggregatorNames, true))
    <option value="{{ $legacyAggregator }}" @selected($selectedAggregator === $legacyAggregator)>{{ $legacyAggregator }} (tidak aktif)</option>
@endif
