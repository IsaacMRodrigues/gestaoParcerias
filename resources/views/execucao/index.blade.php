<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">4 · Execução</p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Parcerias em Execução</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            {{-- Filtros --}}
            @php $temFiltro = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(); @endphp
            <form method="GET" action="{{ route('execucao.index') }}"
                  class="bg-white shadow-sm border border-gray-200 rounded-xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label for="busca" class="block text-xs font-medium text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" id="busca" value="{{ $filtros['busca'] ?? '' }}"
                           placeholder="OSC, número do termo ou objeto"
                           class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Situação</label>
                    <select name="status" id="status"
                            class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todas</option>
                        @foreach(['vigente', 'assinado', 'encerrado'] as $s)
                            <option value="{{ $s }}" {{ ($filtros['status'] ?? '') === $s ? 'selected' : '' }}>
                                {{ \App\Models\Instrumento::STATUS[$s] ?? $s }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        Filtrar
                    </button>
                    @if($temFiltro)
                        <a href="{{ route('execucao.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Limpar filtros</a>
                    @endif
                    <span class="ml-auto text-xs text-gray-400">{{ $instrumentos->total() }} parceria(s)</span>
                </div>
            </form>

            {{-- Lista --}}
            <div class="space-y-3">
                @forelse($instrumentos as $inst)
                    @php
                        $repassado = (float) ($inst->total_repassado ?? 0);
                        $gasto     = (float) ($inst->total_gasto ?? 0);
                        $saldo     = $repassado - $gasto;
                        $pct       = $repassado > 0 ? min(100, (int) round($gasto / $repassado * 100)) : 0;
                        $cor       = \App\Models\Instrumento::STATUS_COLORS[$inst->status] ?? 'gray';
                    @endphp
                    <a href="{{ route('instrumentos.execucao', $inst) }}"
                       class="group block bg-white border border-gray-200 rounded-xl p-5 hover:border-brand-300 hover:shadow-sm transition">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 px-2 py-0.5 rounded-full">
                                        {{ \App\Models\Instrumento::STATUS[$inst->status] ?? $inst->status }}
                                    </span>
                                    @if($inst->numero)
                                        <span class="text-xs text-gray-400 font-mono">nº {{ $inst->numero }}</span>
                                    @endif
                                </div>
                                <p class="text-base font-semibold text-gray-900 mt-1.5 group-hover:text-brand-700 transition">
                                    {{ $inst->proposta->osc->name ?? 'OSC não identificada' }}
                                </p>
                                @if($inst->objeto)
                                    <p class="text-sm text-gray-500 mt-0.5">{{ \Illuminate\Support\Str::limit($inst->objeto, 120) }}</p>
                                @endif
                                @if($inst->data_inicio)
                                    <p class="text-xs text-gray-400 mt-1">
                                        Vigência: {{ $inst->data_inicio->format('d/m/Y') }}
                                        @if($inst->data_fim) a {{ $inst->data_fim->format('d/m/Y') }} @endif
                                    </p>
                                @endif
                            </div>

                            <div class="lg:w-80 shrink-0">
                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-gray-400">Repassado</p>
                                        <p class="font-semibold text-gray-900">R$ {{ number_format($repassado, 2, ',', '.') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">Gasto</p>
                                        <p class="font-semibold text-gray-900">R$ {{ number_format($gasto, 2, ',', '.') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">Saldo</p>
                                        <p class="font-semibold {{ $saldo < 0 ? 'text-red-600' : 'text-brand-700' }}">
                                            R$ {{ number_format($saldo, 2, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 mt-3">
                                    <div class="h-2 rounded-full transition-all {{ $saldo < 0 ? 'bg-red-500' : 'bg-brand-500' }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">{{ $pct }}% do repassado já executado</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 px-6 py-14 text-center">
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $temFiltro ? 'Nenhuma parceria encontrada' : 'Nenhuma parceria em execução' }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                            {{ $temFiltro
                                ? 'Ajuste os filtros para ampliar a busca.'
                                : 'A execução começa depois que o Termo é assinado, na etapa de Celebração.' }}
                        </p>
                        <a href="{{ route('instrumentos.index') }}" class="text-brand-700 hover:underline text-sm mt-4 inline-block">
                            Ver instrumentos →
                        </a>
                    </div>
                @endforelse
            </div>

            @if($instrumentos->hasPages())
                <div class="mt-6">{{ $instrumentos->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
