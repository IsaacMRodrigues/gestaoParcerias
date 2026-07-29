<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('programas.chamamentos.index', $chamamento->programa) }}" class="hover:underline">
                        {{ $chamamento->programa->sigla ?? $chamamento->programa->name }}
                    </a>
                    &rsaquo; Chamamentos
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
                    Seleção e Celebração
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        — {{ $chamamento->numero ? $chamamento->numero . ' · ' : '' }}{{ $chamamento->titulo }}
                    </span>
                </h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-indigo-50 text-indigo-700 rounded-full">
                {{ \App\Models\Peca::CATEGORIA_LABELS[$categoria] ?? $categoria }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Dados do Chamamento --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">
                            {{ $chamamento->numero ? $chamamento->numero . ' — ' : '' }}{{ $chamamento->titulo }}
                        </h3>
                        @if($chamamento->programa?->orgao)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $chamamento->programa->orgao->name }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                            {{ \App\Models\Chamamento::TIPOS[$chamamento->tipo] ?? $chamamento->tipo }}
                        </span>
                        @php $cor = \App\Models\Chamamento::STATUS_COLORS[$chamamento->status] ?? 'gray'; @endphp
                        <span class="px-2.5 py-1 text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 rounded-full">
                            {{ \App\Models\Chamamento::STATUS[$chamamento->status] ?? $chamamento->status }}
                        </span>
                    </div>
                </div>

                @if($chamamento->objeto)
                    <div class="mb-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Objeto</p>
                        <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $chamamento->objeto }}</p>
                    </div>
                @endif

                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Valor disponível</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $chamamento->valor_disponivel ? 'R$ ' . number_format($chamamento->valor_disponivel, 2, ',', '.') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Publicação</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $chamamento->data_publicacao?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Período de inscrição</dt>
                        <dd class="text-gray-800 mt-0.5">
                            @if($chamamento->data_inicio_inscricao && $chamamento->data_fim_inscricao)
                                {{ $chamamento->data_inicio_inscricao->format('d/m/Y') }} a {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Resultado</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $chamamento->data_resultado?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>

                @if($chamamento->requisitos)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Requisitos</p>
                        <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $chamamento->requisitos }}</p>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-4 text-sm">
                    @if($chamamento->processo)
                        <a href="{{ route('processos.show', $chamamento->processo) }}" class="text-indigo-600 hover:underline font-medium">
                            &larr; Processo de origem {{ $chamamento->processo->numero }}
                        </a>
                    @endif
                    <a href="{{ route('programas.chamamentos.edit', [$chamamento->programa, $chamamento]) }}" class="text-gray-600 hover:underline">
                        Editar dados
                    </a>
                    @if($chamamento->tipo === 'chamamento_publico')
                        <a href="{{ route('portal.chamamento', $chamamento) }}" target="_blank" class="text-gray-600 hover:underline">
                            Ver no portal público &rarr;
                        </a>
                    @endif
                </div>
            </div>

            {{-- Progresso --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold text-gray-800">Progresso da Documentação</h3>
                    <span class="text-sm text-gray-500">{{ $progresso['ok'] }}/{{ $progresso['total'] }} obrigatórias</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-green-500 h-2.5 rounded-full transition-all" style="width: {{ $progresso['percent'] }}%"></div>
                </div>
                @if($progresso['percent'] === 100)
                    <p class="text-sm text-green-700 mt-2">🟢 Documentação obrigatória completa.</p>
                @endif
            </div>

            {{-- Checklist --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Peças do Processo de Seleção</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        "Modelo padrão" usa editor rico com brasão e assinatura digital. Demais itens são arquivos anexados.
                    </p>
                </div>
                @include('pecas._checklist', ['pecas' => $pecas])
            </div>
        </div>
    </div>
</x-app-layout>
