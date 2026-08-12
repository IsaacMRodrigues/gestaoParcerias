@props([
    'icone' => 'lista',
    'acao' => null,
    'acaoLabel' => null,
])

@php
    $icones = [
        'lista'     => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'busca'     => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        'pasta'     => 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 6v3.776',
        'pessoas'   => 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 007.36 16.14M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'dinheiro'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    $path = $icones[$icone] ?? $icones['lista'];
@endphp

<div class="flex flex-col items-center justify-center text-center py-4">
    <span class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        </svg>
    </span>
    <p class="text-sm font-medium text-gray-600">{{ $slot }}</p>
    @if($acao)
        <a href="{{ $acao }}"
           class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-700
                  bg-brand-50 border border-brand-200 rounded-lg hover:bg-brand-100 transition">
            {{ $acaoLabel ?? 'Cadastrar' }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif
</div>
