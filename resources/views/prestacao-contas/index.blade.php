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

        @if($oscId)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-900">Nova prestação de contas</h2>
                <p class="text-sm text-gray-500 mt-0.5 mb-4">
                    Escolha a parceria e o período. O que já foi lançado na execução — repasses e despesas —
                    entra sozinho no relatório; você completa o resto.
                </p>
                @if($instrumentos->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma parceria em execução para prestar contas.</p>
                @else
                    <form action="{{ route('prestacao-contas.store') }}" method="POST" class="grid sm:grid-cols-5 gap-3 items-end">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="instrumento_id" value="Parceria" />
                            <select id="instrumento_id" name="instrumento_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                @foreach($instrumentos as $i)
                                    <option value="{{ $i->id }}">{{ $i->numero }} — {{ \Illuminate\Support\Str::limit($i->objeto, 50) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tipo" value="Tipo" />
                            <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                @foreach(\App\Models\PrestacaoContas::TIPOS as $k => $r)<option value="{{ $k }}">{{ $r }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="periodo_inicio" value="Início do período" />
                            <x-text-input id="periodo_inicio" name="periodo_inicio" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="periodo_fim" value="Fim do período" />
                            <x-text-input id="periodo_fim" name="periodo_fim" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div class="sm:col-span-5">
                            <x-input-error :messages="$errors->all()" class="mb-2" />
                            <button class="btn btn-primary">Abrir prestação de contas</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif

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
