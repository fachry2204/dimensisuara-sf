@props(['name' => 'arrow'])
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.6', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
@switch($name)
@case('music') <path d="M9 18V5l11-2v13M9 8l11-2"/><ellipse cx="6" cy="18" rx="3" ry="3"/><ellipse cx="17" cy="16" rx="3" ry="3"/> @break
@case('globe') <circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6.5h14M5 17.5h14"/> @break
@case('chart') <path d="M5 20v-6M12 20V4M19 20V9" stroke-width="3"/> @break
@case('file') <path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/> @break
@case('data') <ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0"/> @break
@case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/> @break
@case('pin') <path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
@case('chat') <path d="M21 11.5a9 9 0 0 1-13.5 8L3 21l1.5-4.5A9 9 0 1 1 21 11.5Z"/><path d="M8 7c-2 4 2 8 6 9l2-2-3-2-1 1-2-2 1-1-2-3Z"/> @break
@case('chevron') <path d="m9 5 7 7-7 7"/> @break
@default <path d="M4 12h15m-6-6 6 6-6 6"/>
@endswitch
</svg>
