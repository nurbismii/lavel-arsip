@php
    $size = $size ?? 'md';
    $withText = $withText ?? true;
    $subtitle = $subtitle ?? null;
    $align = $align ?? 'start';

    $sizeClass = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $alignClass = $align === 'center' ? 'brand-logo--center' : '';
@endphp

<span class="brand-logo {{ $alignClass }}" aria-label="V-Ops">
    <span class="brand-logo-mark brand-logo-mark--{{ $sizeClass }}" aria-hidden="true">
        <svg class="brand-logo-svg" viewBox="0 0 72 72" focusable="false">
            <g class="brand-logo-gear brand-logo-gear--main">
                <g stroke="#f8fafc" stroke-width="4.8" stroke-linecap="round">
                    <line x1="30" y1="13" x2="30" y2="18" />
                    <line x1="30" y1="56" x2="30" y2="61" />
                    <line x1="6" y1="37" x2="11" y2="37" />
                    <line x1="49" y1="37" x2="54" y2="37" />
                    <line x1="13" y1="20" x2="17" y2="24" />
                    <line x1="43" y1="50" x2="47" y2="54" />
                    <line x1="13" y1="54" x2="17" y2="50" />
                    <line x1="43" y1="24" x2="47" y2="20" />
                </g>
                <circle cx="30" cy="37" r="17" fill="#f8fafc" />
                <circle cx="30" cy="37" r="8.2" fill="#1d4ed8" />
            </g>

            <g class="brand-logo-gear brand-logo-gear--upper">
                <g stroke="#bfdbfe" stroke-width="3.4" stroke-linecap="round">
                    <line x1="50" y1="7" x2="50" y2="10" />
                    <line x1="50" y1="32" x2="50" y2="35" />
                    <line x1="36" y1="21" x2="39" y2="21" />
                    <line x1="61" y1="21" x2="64" y2="21" />
                    <line x1="40" y1="11" x2="42" y2="13" />
                    <line x1="58" y1="29" x2="60" y2="31" />
                    <line x1="40" y1="31" x2="42" y2="29" />
                    <line x1="58" y1="13" x2="60" y2="11" />
                </g>
                <circle cx="50" cy="21" r="10.4" fill="#dbeafe" />
                <circle cx="50" cy="21" r="5" fill="#0f766e" />
            </g>

            <g class="brand-logo-gear brand-logo-gear--lower">
                <g stroke="#bbf7d0" stroke-width="3.1" stroke-linecap="round">
                    <line x1="50" y1="38" x2="50" y2="41" />
                    <line x1="50" y1="59" x2="50" y2="62" />
                    <line x1="38" y1="50" x2="41" y2="50" />
                    <line x1="59" y1="50" x2="62" y2="50" />
                    <line x1="42" y1="42" x2="44" y2="44" />
                    <line x1="56" y1="56" x2="58" y2="58" />
                    <line x1="42" y1="58" x2="44" y2="56" />
                    <line x1="56" y1="44" x2="58" y2="42" />
                </g>
                <circle cx="50" cy="50" r="8.7" fill="#dcfce7" />
                <circle cx="50" cy="50" r="4.1" fill="#16a34a" />
            </g>
        </svg>
    </span>

    @if($withText)
        <span class="brand-logo-copy">
            <span class="brand-logo-title">V-Ops</span>
            @if($subtitle)
                <span class="brand-logo-subtitle">{{ $subtitle }}</span>
            @endif
        </span>
    @endif
</span>
