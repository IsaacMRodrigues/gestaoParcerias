<x-portal-layout>
    {{-- Cabeçalho. O padding acompanha o do conteúdo abaixo (px-4 sm:px-6
         lg:px-8), senão título e cards de filtro ficam desalinhados. --}}
    <div class="bg-gradient-to-b from-brand-50/70 to-gray-50 border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-accent-800 bg-accent-100 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-500"></span>
                Transparência
            </span>
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 mt-4">Parcerias Celebradas</h1>
            <p class="text-gray-600 mt-3 max-w-2xl">
                Repasses de recursos municipais ao Terceiro Setor, nos termos da Lei Federal nº 13.019/2014.
                Consulta livre, sem necessidade de cadastro.
            </p>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
                <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm flex items-center gap-4">
                    <span class="w-11 h-11 shrink-0 rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 flex items-center justify-center">
                        <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-3xl font-bold text-gray-900 leading-none tracking-tight">
                            {{ number_format($totais['parcerias'], 0, ',', '.') }}
                        </span>
                        <span class="block text-sm text-gray-600 mt-1.5">parcerias celebradas</span>
                    </span>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm flex items-center gap-4">
                    <span class="w-11 h-11 shrink-0 rounded-xl bg-accent-50 text-accent-700 ring-1 ring-accent-100 flex items-center justify-center">
                        <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-3xl font-bold text-gray-900 leading-none tracking-tight">
                            R$ {{ number_format($totais['repassado'], 2, ',', '.') }}
                        </span>
                        <span class="block text-sm text-gray-600 mt-1.5">valor total pactuado</span>
                    </span>
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
                <label for="busca" class="block text-sm font-semibold text-gray-800 mb-1.5">Pesquisar</label>
                <input type="text" name="busca" id="busca" value="{{ $filtros['busca'] ?? '' }}"
                       placeholder="OSC, número do termo ou objeto"
                       class="block w-full border-gray-300 rounded-lg shadow-sm text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label for="tipo" class="block text-sm font-semibold text-gray-800 mb-1.5">Instrumento</label>
                <select name="tipo" id="tipo"
                        class="block w-full border-gray-300 rounded-lg shadow-sm text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Instrumento::TIPOS as $key => $label)
                        <option value="{{ $key }}" {{ ($filtros['tipo'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="exercicio" class="block text-sm font-semibold text-gray-800 mb-1.5">Exercício</label>
                <select name="exercicio" id="exercicio"
                        class="block w-full border-gray-300 rounded-lg shadow-sm text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Todos</option>
                    @foreach($exercicios as $ano)
                        <option value="{{ $ano }}" {{ (string) ($filtros['exercicio'] ?? '') === (string) $ano ? 'selected' : '' }}>{{ $ano }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex items-center gap-3">
                <button type="submit"
                        class="btn btn-primary">
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
                                <span class="text-xs font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded">
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
                    <div class="w-14 h-14 mx-auto rounded-full bg-brand-50 flex items-center justify-center">
                        <svg class="w-7 h-7 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
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
                    <a href="{{ route('portal.index') }}" class="text-brand-600 hover:underline text-sm mt-4 inline-block">
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
