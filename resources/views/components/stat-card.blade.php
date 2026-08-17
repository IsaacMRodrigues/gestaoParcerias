@props([
    'label' => '',
    'value' => 0,
    'sub' => null,
    'color' => 'brand',
    'href' => null,
    'icon' => 'default',
])

@php
    /**
     * Paleta da Prefeitura: verde, laranja e cinzas — só isso.
     *
     * Com duas matizes não dá para dar uma identidade a cada módulo, então a
     * cor aqui diz o ESTADO da métrica, que é informação melhor de qualquer
     * forma: laranja = trabalho em curso esperando alguém, verde = o que está
     * ativo e saudável, cinza = cadastro de referência, que só se consulta.
     *
     * A tentativa anterior de uma cor por módulo trouxe azul, roxo, rosa e
     * verde-azulado — bonito, mas fora da identidade da Prefeitura.
     */
    $accents = [
        'accent' => ['bg-accent-50 text-accent-700 ring-accent-100', 'hover:border-accent-300', 'group-hover:text-accent-600', 'border-t-accent-500'],
        'brand'  => ['bg-brand-50 text-brand-700 ring-brand-100',    'hover:border-brand-300',  'group-hover:text-brand-600',  'border-t-brand-600'],
        'slate'  => ['bg-slate-100 text-slate-600 ring-slate-200',   'hover:border-slate-400',  'group-hover:text-slate-600',  'border-t-slate-400'],
    ];
    // A faixa do topo é o que se enxerga de longe: dá para varrer a fileira e
    // separar "precisa de mim" de "está rodando" sem ler rótulo nenhum.
    [$accent, $hoverBorda, $hoverSeta, $faixa] = $accents[$color] ?? $accents['slate'];

    // Traço do ícone (heroicons, outline). 'default' vale para qualquer chave
    // não mapeada, para o card nunca ficar sem símbolo.
    $icones = [
        'processos'    => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'chamamentos'  => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'propostas'    => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'instrumentos' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'orgaos'       => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6',
        'oscs'         => 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 007.36 16.14M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'default'      => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
    ];
    $path = $icones[$icon] ?? $icones['default'];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="group block bg-white rounded-xl border border-gray-200 border-t-4 {{ $faixa }} p-5 transition duration-200
           @if($href) {{ $hoverBorda }} hover:shadow-md hover:-translate-y-0.5
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 @endif">
    <div class="flex items-start justify-between">
        <span class="w-11 h-11 rounded-xl ring-1 {{ $accent }} flex items-center justify-center
                     group-hover:scale-105 transition">
            <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
            </svg>
        </span>
        @if($href)
            <svg class="w-4 h-4 text-gray-300 {{ $hoverSeta }} group-hover:translate-x-0.5 transition"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </div>
    <p class="mt-4 text-4xl font-bold text-gray-900 leading-none tracking-tight">{{ $value }}</p>
    <p class="mt-2 text-sm font-semibold text-gray-800">{{ $label }}</p>
    @if($sub)
        <p class="text-xs text-gray-500 mt-0.5">{{ $sub }}</p>
    @endif
</{{ $tag }}>
