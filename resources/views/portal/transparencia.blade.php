<x-portal-layout>
    {{-- Cabeçalho --}}
    <div class="bg-gradient-to-br from-indigo-800 via-indigo-700 to-purple-800 text-white px-4">
        <div class="max-w-6xl mx-auto py-12 text-center">
            <span class="inline-block text-xs font-semibold uppercase tracking-wider text-indigo-200 bg-white/10 px-3 py-1 rounded-full mb-4">
                Transparência
            </span>
            <h1 class="text-3xl font-bold tracking-tight">Parcerias Celebradas</h1>
            <p class="text-indigo-200 mt-2 max-w-2xl mx-auto text-sm">
                Repasses de recursos municipais ao Terceiro Setor, nos termos da Lei Federal nº 13.019/2014.
                Consulta livre, sem necessidade de cadastro.
            </p>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg mx-auto">
                <div class="bg-white/10 rounded-xl px-5 py-4">
                    <p class="text-2xl font-bold">{{ number_format($totais['parcerias'], 0, ',', '.') }}</p>
                    <p class="text-xs text-indigo-200 mt-0.5">parcerias celebradas</p>
                </div>
                <div class="bg-white/10 rounded-xl px-5 py-4">
                    <p class="text-2xl font-bold">R$ {{ number_format($totais['repassado'], 2, ',', '.') }}</p>
                    <p class="text-xs text-indigo-200 mt-0.5">valor total pactuado</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Filtros --}}
        @php $temFiltro = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(); @endphp
        <form method="GET" action="{{ route('transparencia') }}"
              class="bg-white rounded-xl border border-gray-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div class="lg:col-span-2">
                <label for="busca" class="block text-xs font-medium text-gray-500 mb-1">Pesquisar</label>
                <input type="text" name="busca" id="busca" value="{{ $filtros['busca'] ?? '' }}"
                       placeholder="OSC, número do termo ou objeto"
                       class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="tipo" class="block text-xs font-medium text-gray-500 mb-1">Instrumento</label>
                <select name="tipo" id="tipo"
                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Instrumento::TIPOS as $key => $label)
                        <option value="{{ $key }}" {{ ($filtros['tipo'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="exercicio" class="block text-xs font-medium text-gray-500 mb-1">Exercício</label>
                <select name="exercicio" id="exercicio"
                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todos</option>
                    @foreach($exercicios as $ano)
                        <option value="{{ $ano }}" {{ (string) ($filtros['exercicio'] ?? '') === (string) $ano ? 'selected' : '' }}>{{ $ano }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex items-center gap-3">
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    Filtrar
                </button>
                @if($temFiltro)
                    <a href="{{ route('transparencia') }}" class="text-sm text-gray-500 hover:text-gray-800">Limpar filtros</a>
                @endif
            </div>
        </form>

        {{-- Lista --}}
        <div class="space-y-3">
            @forelse($instrumentos as $inst)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                    {{ \App\Models\Instrumento::TIPOS[$inst->tipo] ?? $inst->tipo }}
                                </span>
                                @php $cor = \App\Models\Instrumento::STATUS_COLORS[$inst->status] ?? 'gray'; @endphp
                                <span class="text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 px-2 py-0.5 rounded-full">
                                    {{ \App\Models\Instrumento::STATUS[$inst->status] ?? $inst->status }}
                                </span>
                                @if($inst->numero)
                                    <span class="text-xs text-gray-400 font-mono">nº {{ $inst->numero }}</span>
                                @endif
                            </div>
                            <h2 class="text-base font-semibold text-gray-900 mt-2">
                                {{ $inst->proposta->osc->name ?? 'OSC não identificada' }}
                            </h2>
                            @if($inst->objeto)
                                <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($inst->objeto, 180) }}</p>
                            @endif
                            @if($inst->proposta?->chamamento?->programa?->orgao)
                                <p class="text-xs text-gray-400 mt-1.5">
                                    {{ $inst->proposta->chamamento->programa->orgao->name }}
                                </p>
                            @endif
                        </div>
                        <div class="sm:text-right shrink-0">
                            <p class="text-lg font-bold text-gray-900">
                                R$ {{ number_format($inst->valor_repasse, 2, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-400">valor do repasse</p>
                            @if($inst->data_inicio)
                                <p class="text-xs text-gray-500 mt-2">
                                    Vigência: {{ $inst->data_inicio->format('d/m/Y') }}
                                    @if($inst->data_fim) a {{ $inst->data_fim->format('d/m/Y') }} @endif
                                </p>
                            @endif
                            @if($inst->publicado_doe && $inst->data_publicacao_doe)
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Publicado no DOE em {{ $inst->data_publicacao_doe->format('d/m/Y') }}
                                    @if($inst->numero_doe) · nº {{ $inst->numero_doe }} @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-200 px-6 py-14 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 flex items-center justify-center">
                        <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-lg font-semibold text-gray-900">
                        {{ $temFiltro ? 'Nenhuma parceria encontrada' : 'Nenhuma parceria celebrada ainda' }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                        {{ $temFiltro
                            ? 'Ajuste os filtros para ampliar a busca.'
                            : 'Assim que um termo for assinado, ele aparecerá aqui com o valor e a vigência.' }}
                    </p>
                    <a href="{{ route('portal.index') }}" class="text-indigo-600 hover:underline text-sm mt-4 inline-block">
                        Ver chamamentos abertos →
                    </a>
                </div>
            @endforelse
        </div>

        @if($instrumentos->hasPages())
            <div class="mt-6">{{ $instrumentos->links() }}</div>
        @endif
    </div>
</x-portal-layout>
