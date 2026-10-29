{{-- Barra lateral do portal para a OSC logada, no desenho da barra da área interna (layouts.sidebar).
     Espera: $navItens — itens com url, rotulo, ativo e icone (caminho SVG), ou rotulo + filhos (seção). --}}
@php
    $sec  = 'px-3 pt-5 pb-1.5 text-[12px] font-semibold uppercase tracking-wider text-gray-400';
    $link = 'relative flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition';
    $on   = '!text-white bg-brand-600 font-semibold shadow-sm hover:!bg-brand-600';
    $osc  = auth()->user()->oscVinculada();
@endphp

<aside class="fixed top-1.5 bottom-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col
              transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    <div class="px-6 py-5 border-b border-gray-200 shrink-0">
        <a href="{{ route('landing') }}"
           class="block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <x-marca class="h-12" />
            <span class="mt-3 block text-sm font-bold text-gray-900 leading-tight">Portal de Parcerias</span>
            <span class="block text-[12px] text-gray-500 truncate">{{ $osc?->name ?? 'Organização da Sociedade Civil' }}</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-2" data-a11y-menu>
        @foreach($navItens as $item)
            @if(isset($item['filhos']))
                <p class="{{ $sec }}">{{ $item['rotulo'] }}</p>
                @foreach($item['filhos'] as $filho)
                    <a href="{{ $filho['url'] }}" class="{{ $link }} {{ $filho['ativo'] ? $on : '' }}">
                        <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $filho['icone'] }}"/>
                        </svg>
                        {{ $filho['rotulo'] }}
                    </a>
                @endforeach
            @else
                @if(! empty($item['secao']))<p class="{{ $sec }}">{{ $item['secao'] }}</p>@endif
                <a href="{{ $item['url'] }}" class="{{ $link }} {{ $item['ativo'] ? $on : '' }}">
                    <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icone'] }}"/>
                    </svg>
                    {{ $item['rotulo'] }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-gray-200 px-4 py-3 text-[12px] text-gray-400 shrink-0 flex items-center justify-between">
        <span>PGP · {{ now()->year }}</span>
        <a href="{{ route('transparencia') }}" class="hover:text-brand-700 transition">Transparência</a>
    </div>
</aside>
