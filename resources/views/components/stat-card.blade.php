@props([
    'label' => '',
    'value' => 0,
    'sub' => null,
    'color' => 'brand',
    'href' => null,
])

@php
    $accents = [
        'brand'   => 'bg-brand-50 text-brand-600',
        'accent'  => 'bg-accent-50 text-accent-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'violet'  => 'bg-violet-50 text-violet-600',
        'slate'   => 'bg-slate-100 text-slate-600',
        'sky'     => 'bg-sky-50 text-sky-600',
    ];
    $accent = $accents[$color] ?? $accents['brand'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="group block bg-white rounded-xl border border-gray-200 p-5 hover:border-brand-300 hover:shadow-sm transition">
    <div class="flex items-start justify-between">
        <span class="w-10 h-10 rounded-lg {{ $accent }} flex items-center justify-center">
            <span class="w-2.5 h-2.5 rounded-full bg-current"></span>
        </span>
        @if($href)
            <svg class="w-4 h-4 text-gray-300 group-hover:text-brand-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </div>
    <p class="mt-4 text-3xl font-bold text-gray-900 leading-none">{{ $value }}</p>
    <p class="mt-1.5 text-sm font-medium text-gray-700">{{ $label }}</p>
    @if($sub)
        <p class="text-xs text-gray-400 mt-0.5">{{ $sub }}</p>
    @endif
</{{ $tag }}>
