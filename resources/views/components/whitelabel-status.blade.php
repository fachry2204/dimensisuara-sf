@props(['active' => false, 'showInactive' => false, 'stacked' => false])
@if($active || $showInactive)
<small @class(['fw-semibold whitelabel-status', 'd-block mt-1' => $stacked, 'ms-2' => !$stacked])>Whitelabel: {{ $active ? 'Aktif' : 'Nonaktif' }}</small>
@endif
