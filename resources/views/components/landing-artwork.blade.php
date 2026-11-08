@props(['release', 'index' => 0])
@if($release->landing_artwork)
<img src="{{ $release->landing_artwork }}" alt="Cover {{ $release->title }}" loading="lazy" width="600" height="600">
@else
<div class="artwork-fallback artwork-{{ $index % 3 }}" role="img" aria-label="Cover belum tersedia untuk {{ $release->title }}">
    <span class="artwork-label">DIMENSI SUARA · CATALOGUE</span>
    <div class="vinyl"><i></i></div>
    <span @class(['artwork-title','artwork-title-long'=>mb_strlen($release->title)>36,'artwork-title-extra-long'=>mb_strlen($release->title)>80])>{{ $release->title }}</span>
    <span class="artwork-credit">{{ $release->landing_artist }}</span>
    <span class="artwork-unavailable">Cover belum tersedia</span>
</div>
@endif
