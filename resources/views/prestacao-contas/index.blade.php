{{-- Prestação de contas: a lista de quem olha. A OSC vê as suas; quem é do
     Município vê todas as que passam por ele. --}}
@php $layout = $oscId ? 'portal-layout' : 'app-layout'; @endphp
<x-dynamic-component :component="$layout">
    @if(! $oscId)
        <x-slot name="header">
            <h2 class="text-2xl font-bold text-gray-900">Prestação de Contas</h2>
            <p class="text-sm text-gray-500 mt-0.5">O que as organizações enviaram, na ordem do período prestado.</p>
        </x-slot>
    @endif

    <div class="{{ $oscId ? 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10' : 'py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' }} space-y-6">
        @if($oscId)
            <h1 class="text-2xl font-bold text-gray-900">Prestação de contas</h1>
        @endif
        <x-flash-message />

        {{-- O painel de cada parceria: a OSC lança ao longo da execução e gera a prestação de lá. --}}
        @php
            $paineis = $oscId ? $instrumentos
                : $instrumentos->filter(fn ($i) => $i->pendente_despesas + $i->pendente_lancamentos > 0);
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">{{ $oscId ? 'Painel de cada parceria' : 'Lançamentos a conferir' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $oscId
                        ? 'Lance despesas, extratos, evidências, folha e bens à medida que acontecem. A prestação de contas do período é gerada no painel, já montada.'
                        : 'Parcerias em que a OSC lançou despesas, extratos, evidências, folha ou bens que ainda não foram conferidos.' }}
                </p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($paineis as $i)
                    @php
                        $aConferir = $i->pendente_despesas + $i->pendente_lancamentos;
                        $devolvidos = $i->devolvido_despesas + $i->devolvido_lancamentos;
                    @endphp
                    <a href="{{ route('lancamentos.painel', $i) }}" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $i->numero }} — {{ \Illuminate\Support\Str::limit($i->objeto, 70) }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $i->proposta?->osc?->name }}</span>
                        </span>
                        <span class="shrink-0 flex items-center gap-2">
                            @if($devolvidos)<span class="px-2 py-0.5 text-[11px] font-semibold bg-red-50 text-red-700 ring-1 ring-red-200 rounded">{{ $devolvidos }} devolvido(s)</span>@endif
                            @if($aConferir)<span class="px-2 py-0.5 text-[11px] font-semibold bg-accent-50 text-accent-800 ring-1 ring-accent-200 rounded">{{ $aConferir }} a conferir</span>@endif
                            <span class="text-xs font-semibold text-brand-700">Abrir painel →</span>
                        </span>
                    </a>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-400">{{ $oscId ? 'Nenhuma parceria em execução.' : 'Nada a conferir.' }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
            @forelse($prestacoes as $pc)
                <a href="{{ route('prestacao-contas.show', $pc) }}" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $pc->rotulo() }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            {{ $pc->instrumento?->numero }} · {{ $pc->osc()?->name }}
                        </span>
                    </span>
                    <span class="shrink-0 text-right">
                        @if($pc->concluida())
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-brand-50 text-brand-800 ring-1 ring-brand-200 rounded">Encerrada</span>
                        @else
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-accent-50 text-accent-800 ring-1 ring-accent-200 rounded">
                                Etapa {{ $pc->etapa + 1 }} · {{ \App\Models\PrestacaoContas::SETORES[$pc->setor] ?? $pc->setor }}
                            </span>
                        @endif
                    </span>
                </a>
            @empty
                <div class="px-6 py-12"><x-empty-state icone="lista">Nenhuma prestação de contas.</x-empty-state></div>
            @endforelse
        </div>
    </div>
</x-dynamic-component>
