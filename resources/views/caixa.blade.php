<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                Caixa de Entrada
                <span class="text-sm font-normal text-gray-500 ml-1">— {{ $setor }}</span>
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Tudo o que está parado esperando o seu setor, nos três trâmites.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @if($caixa->vazia())
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-14">
                    <x-empty-state icone="pasta">
                        Nada esperando por você agora — sua caixa está vazia.
                    </x-empty-state>
                </div>
            @else
                {{-- Resumo: quantos itens em cada trâmite --}}
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm text-gray-600">
                        <strong class="text-gray-900">{{ $caixa->total() }}</strong>
                        {{ $caixa->total() === 1 ? 'item aguardando' : 'itens aguardando' }}:
                    </span>
                    @foreach($caixa->porTramite() as $tramite => $quantos)
                        <span class="px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-700 ring-1 ring-slate-200 rounded-md">
                            {{ $quantos }} em {{ $tramite }}
                        </span>
                    @endforeach
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
                    @foreach($caixa->itens as $item)
                        <a href="{{ $item['url'] }}"
                           class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">

                            <span class="px-2 py-0.5 mt-0.5 shrink-0 text-[11px] font-bold uppercase tracking-wide
                                         bg-slate-100 text-slate-600 ring-1 ring-slate-200 rounded">
                                {{ $item['tramite'] }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">
                                    {{ $item['titulo'] }}
                                </span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $item['subtitulo'] }}</span>

                                {{-- Recebimento pendente trava tudo o mais dentro do
                                     processo, então ganha destaque de pendência. --}}
                                @if($item['aguardaRecebimento'])
                                    <span class="inline-block mt-1.5 px-2 py-0.5 text-[11px] font-semibold
                                                 bg-accent-50 text-accent-800 ring-1 ring-accent-200 rounded">
                                        Aguardando o registro de recebimento
                                    </span>
                                @endif
                            </span>

                            <span class="shrink-0 text-right">
                                <span class="block text-xs text-gray-400 whitespace-nowrap">
                                    {{ $item['desde']?->diffForHumans() }}
                                </span>
                                <span class="block text-sm font-semibold text-brand-700 mt-1">Abrir →</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
