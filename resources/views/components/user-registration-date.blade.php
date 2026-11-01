@props(['registeredAt' => null])
<div {{ $attributes->class(['user-registration-date']) }}>
    <span>Tanggal Daftar:</span>
    @if($registeredAt)
        <time datetime="{{ $registeredAt->toIso8601String() }}">{{ $registeredAt->copy()->timezone(config('app.timezone'))->locale('id')->translatedFormat('d F Y, H:i') }}</time>
    @else
        <span>Belum tersedia</span>
    @endif
</div>
