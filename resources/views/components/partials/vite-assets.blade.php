@props(['entries' => []])

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite($entries)
@endif

