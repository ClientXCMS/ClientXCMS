@php
    $a11yAsset = 'resources/global/css/a11y.css';
    $manifestPath = public_path('build/manifest.json');
    $manifestHasA11yAsset = false;

    if (is_file($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $manifestHasA11yAsset = is_array($manifest) && array_key_exists($a11yAsset, $manifest);
    }
@endphp

@if (Vite::isRunningHot() || $manifestHasA11yAsset)
    @vite($a11yAsset)
@endif
