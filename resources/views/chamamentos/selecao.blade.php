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

            {{-- Trâmite da Seleção (só Chamamento Público) --}}
            @if($chamamento->temTramiteSelecao())
                @php
                    $etapaAtual   = (int) $chamamento->selecao_etapa;
                    $meuSetor     = auth()->user()->setor;
                    $souDoSetor   = $meuSetor === $chamamento->selecao_setor;
                    $concluida    = $chamamento->selecaoConcluida();
                    $pendencias   = $concluida ? [] : $chamamento->pendenciasSelecao();
                @endphp
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Trâmite da Seleção</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Julgamento das propostas → publicações → homologação pelo Prefeito.
                            </p>
                        </div>
                        @if($concluida)
                            <span class="px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full whitespace-nowrap">
                                Encerrada em {{ $chamamento->selecao_concluida_em->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full whitespace-nowrap">
                                Com {{ \App\Models\Chamamento::SETORES_SELECAO[$chamamento->selecao_setor] ?? $chamamento->selecao_setor }}
                            </span>
                        @endif
                    </div>

                    {{-- Trilha das etapas --}}
                    <ol class="space-y-2">
                        @foreach(\App\Models\Chamamento::ETAPAS_SELECAO as $i => $etapa)
                            @php
                                $feita = $concluida || $i < $etapaAtual;
                                $agora = !$concluida && $i === $etapaAtual;
                            @endphp
                            <li class="flex items-start gap-3 text-sm">
                                <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full border text-[11px] font-bold flex items-center justify-center
                                    {{ $feita ? 'bg-green-100 border-green-300 text-green-700'
                                             : ($agora ? 'bg-indigo-100 border-indigo-300 text-indigo-700'
                                                       : 'bg-white border-gray-300 text-gray-400') }}">
                                    {{ $feita ? '✓' : $i + 1 }}
                                </span>
                                <span class="{{ $agora ? 'text-gray-900 font-medium' : ($feita ? 'text-gray-500' : 'text-gray-400') }}">
                                    <span class="text-xs font-semibold uppercase tracking-wide
                                        {{ $agora ? 'text-indigo-600' : 'text-gray-400' }}">
                                        {{ strtoupper($etapa['setor']) }}
                                    </span>
                                    — {{ $etapa['acao'] }}
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    @unless($concluida)
                        {{-- Pendências da etapa --}}
                        @if($pendencias)
                            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-md p-3">
                                <p class="text-xs font-semibold text-amber-800">Pendências desta etapa:</p>
                                <ul class="mt-1 text-xs text-amber-700 list-disc list-inside space-y-0.5">
                                    @foreach($pendencias as $p)
                                        <li>{{ $p }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Ações do setor que está com a Seleção --}}
                        @if($souDoSetor)
                            <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                                @if($chamamento->ultimaEtapaSelecao())
                                    <form action="{{ route('chamamentos.selecao.concluir', $chamamento) }}" method="POST"
                                          data-confirm="Encerrar a Seleção? O chamamento será homologado e seguirá para a Celebração.">
                                        @csrf
                                        <button type="submit" @disabled($pendencias)
                                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Encerrar Seleção (homologar)
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('chamamentos.selecao.avancar', $chamamento) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <textarea name="parecer" rows="2" placeholder="Observação (opcional)"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                        <button type="submit" @disabled($pendencias)
                                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Encaminhar para
                                            {{ \App\Models\Chamamento::SETORES_SELECAO[\App\Models\Chamamento::ETAPAS_SELECAO[$etapaAtual + 1]['setor']] }}
                                        </button>
                                    </form>
                                @endif

                                @if($etapaAtual > 0)
                                    <form action="{{ route('chamamentos.selecao.devolver', $chamamento) }}" method="POST"
                                          class="space-y-2 pt-2 border-t border-gray-100">
                                        @csrf
                                        <textarea name="parecer" rows="2" required placeholder="Motivo da devolução (obrigatório)"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"></textarea>
                                        <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-red-700 border border-red-300 rounded-md hover:bg-red-50">
                                            Devolver para
                                            {{ \App\Models\Chamamento::SETORES_SELECAO[$chamamento->setorAnteriorSelecao()] }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <p class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                                A Seleção está com
                                <strong>{{ \App\Models\Chamamento::SETORES_SELECAO[$chamamento->selecao_setor] ?? $chamamento->selecao_setor }}</strong>.
                                Só esse setor pode movimentá-la.
                            </p>
                        @endif
                    @endunless

                    {{-- Histórico --}}
                    @if($chamamento->selecaoTramitacoes->isNotEmpty())
                        <details class="mt-4 pt-4 border-t border-gray-100">
                            <summary class="text-xs text-indigo-600 cursor-pointer hover:underline">
                                Histórico de movimentações ({{ $chamamento->selecaoTramitacoes->count() }})
                            </summary>
                            <ul class="mt-2 space-y-2">
                                @foreach($chamamento->selecaoTramitacoes as $mov)
                                    <li class="text-xs text-gray-600 border-l-2 pl-3
                                        {{ $mov->status === 'devolvido' ? 'border-red-300' : 'border-gray-200' }}">
                                        <span class="font-medium">
                                            {{ \App\Models\SelecaoTramitacao::STATUS[$mov->status] ?? $mov->status }}
                                        </span>
                                        · {{ strtoupper($mov->de_setor) }} → {{ strtoupper($mov->para_setor) }}
                                        · {{ $mov->enviado_em?->format('d/m/Y H:i') }}
                                        @if($mov->remetente) · {{ $mov->remetente->name }} @endif
                                        @if($mov->parecer)
                                            <p class="text-gray-500 mt-0.5 whitespace-pre-line">{{ $mov->parecer }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endif

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
