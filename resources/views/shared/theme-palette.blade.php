@php
    $paletteWeights = [50 => 12, 100 => 22, 200 => 36, 300 => 52, 400 => 70, 500 => 86];
    $paletteDarkWeights = [700 => 88, 800 => 72, 900 => 56, 950 => 38];
    $paletteProperties = ['text' => 'color', 'bg' => 'background-color', 'border' => 'border-color', 'outline' => 'outline-color', 'ring' => '--tw-ring-color', 'shadow' => '--tw-shadow-color'];
    $paletteClasses = preg_split('/\s+/', trim($legacyClasses));
@endphp
<style>
    :root {
        --{{ $prefix }}-primary: {{ $primary }};
        --{{ $prefix }}-accent: {{ $accent }};
        @if(isset($surface))--{{ $prefix }}-surface: {{ $surface }};@endif
        @foreach(['primary' => $primary, 'accent' => $accent] as $name => $color)
        --{{ $prefix }}-{{ $name }}-50: color-mix(in srgb, {{ $color }} 12%, white);
        @foreach($paletteWeights as $shade => $weight)
        --{{ $prefix }}-{{ $name }}-{{ $shade }}: color-mix(in srgb, {{ $color }} {{ $weight }}%, white);
        @endforeach
        --{{ $prefix }}-{{ $name }}-600: {{ $color }};
        @foreach($paletteDarkWeights as $shade => $weight)
        --{{ $prefix }}-{{ $name }}-{{ $shade }}: color-mix(in srgb, {{ $color }} {{ $weight }}%, black);
        @endforeach
        @endforeach
    }
    .btn-primary, .btn-product, .btn-product-pinned, .bg-primary { background-color: var(--{{ $prefix }}-primary) !important; }
    .btn-primary:hover, .btn-product:hover, .btn-product-pinned:hover { background-color: var(--{{ $prefix }}-primary-700) !important; }
    .text-primary, .provisioning-tab-active, .provisioning-tab:hover { color: var(--{{ $prefix }}-primary) !important; }
    .input-text:focus { border-color: var(--{{ $prefix }}-primary) !important; --tw-ring-color: var(--{{ $prefix }}-primary) !important; }
    @foreach($paletteClasses as $class)
        @php
            preg_match('/(?<utility>text|bg|border|outline|ring|shadow|from|via|to)-(?<family>blue|indigo|cyan|violet)-(?<shade>\d+)(?:\/(?<opacity>\d+))?$/', $class, $match);
            $variable = $families[$match['family']].'-'.$match['shade'];
            $value = ! empty($match['opacity']) ? 'color-mix(in srgb, var(--'.$variable.') '.$match['opacity'].'%, transparent)' : 'var(--'.$variable.')';
            $selector = '[class~="'.$class.'"]';
            $selector = str_contains($class, 'dark:') ? '.dark '.$selector : $selector;
            $selector = str_contains($class, 'group-hover:') ? '.group:hover '.$selector : $selector;
            $selector = str_contains($class, 'peer-checked:') ? '.peer:checked ~ '.$selector : $selector;
            $selector .= str_contains($class, 'hover:') && ! str_contains($class, 'group-hover:') ? ':hover' : '';
            $selector .= str_contains($class, 'focus:') ? ':focus' : '';
        @endphp
        @if(isset($paletteProperties[$match['utility']]))
    {!! $selector !!} { {{ $paletteProperties[$match['utility']] }}: {{ $value }} !important; }
        @elseif($match['utility'] === 'from')
    {!! $selector !!} { --tw-gradient-from: {{ $value }} var(--tw-gradient-from-position) !important; --tw-gradient-to: transparent var(--tw-gradient-to-position) !important; }
        @elseif($match['utility'] === 'via')
    {!! $selector !!} { --tw-gradient-stops: var(--tw-gradient-from), {{ $value }} var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
        @elseif($match['utility'] === 'to')
    {!! $selector !!} { --tw-gradient-to: {{ $value }} var(--tw-gradient-to-position) !important; }
        @endif
    @endforeach
</style>
