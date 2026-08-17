@props([
    'href' => '#',
    'label' => '',
    'color' => 'slate',
])

@php
    // Atalhos são todos a mesma coisa — um caminho curto para outra tela —, então
    // ficam neutros e só ganham o verde da marca ao passar o mouse. Antes a
    // fileira inteira já vinha verde e virava um bloco só; pintá-los de cores
    // diferentes seria o erro oposto, inventando distinção onde não há.
    $cores = [
        'slate'  => ['bg-slate-100 text-slate-600', 'group-hover:bg-brand-600',  'hover:border-brand-400 hover:text-brand-800'],
        'brand'  => ['bg-brand-50 text-brand-700',  'group-hover:bg-brand-600',  'hover:border-brand-400 hover:text-brand-800'],
        'accent' => ['bg-accent-50 text-accent-700', 'group-hover:bg-accent-500', 'hover:border-accent-400 hover:text-accent-800'],
    ];
    [$chip, $chipHover, $cartaoHover] = $cores[$color] ?? $cores['slate'];
@endphp

<a href="{{ $href }}"
   class="group inline-flex items-center gap-2.5 pl-3 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white
          text-sm font-semibold text-gray-700 shadow-sm {{ $cartaoHover }}
          hover:shadow transition duration-200
          focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
    <span class="w-6 h-6 shrink-0 rounded-lg {{ $chip }} flex items-center justify-center
                 {{ $chipHover }} group-hover:text-white transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </span>
    {{ $label }}
</a>
