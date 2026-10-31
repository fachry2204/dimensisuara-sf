@php($primaryPalette = app(\App\Services\PrimaryColors::class)->palette())
<style id="system-primary-colors">
html[data-bs-theme], html[data-bs-theme] body {
    --sf-primary-start: {{ $primaryPalette['start'] }};
    --sf-primary-end: {{ $primaryPalette['end'] }};
    --sf-primary-background: {{ $primaryPalette['background'] }};
    --sf-primary-hover: {{ $primaryPalette['hover'] }};
    --sf-on-primary: {{ $primaryPalette['text'] }};
    --sf-primary-check: {{ $primaryPalette['check'] }};
}
html[data-bs-theme="light"], html[data-bs-theme="light"] body.sf-app {
    --sf-accent: {{ $primaryPalette['light_accent'] }} !important;
    --sf-accent-soft: {{ $primaryPalette['light_soft'] }} !important;
}
html[data-bs-theme="dark"], html[data-bs-theme="dark"] body.sf-app {
    --sf-accent: {{ $primaryPalette['dark_accent'] }} !important;
    --sf-accent-soft: {{ $primaryPalette['dark_soft'] }} !important;
}
</style>
<link href="{{ asset('build/css/primary-colors.css') }}?v={{ file_exists(public_path('build/css/primary-colors.css')) ? substr(hash_file('sha256', public_path('build/css/primary-colors.css')), 0, 12) : '1' }}" rel="stylesheet">
