@php
    // A tela serve tanto à equipe da Administração quanto à OSC da parceria:
    // o layout muda, o conteúdo é o mesmo.
    $ehOsc  = !auth()->user()->temAcessoInterno();
    $layout = $ehOsc ? 'portal-layout' : 'app-layout';

    $etapaAtual = (int) $proposta->celebracao_etapa;
    $concluida  = $proposta->celebracaoConcluida();
    $souDoSetor = auth()->user()->setor === $proposta->celebracao_setor
        && ($proposta->celebracao_setor !== 'osc'
            || (auth()->user()->osc && auth()->user()->osc->id === $proposta->osc_id));
    $pendencias = $concluida ? [] : $proposta->pendenciasCelebracao();
    $setorLabel = fn ($s) => \App\Models\Proposta::SETORES_CELEBRACAO[$s] ?? $s;
@endphp

<x-dynamic-component :component="$layout">
    @unless($ehOsc)
        <x-slot name="header">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
                    &rsaquo; Celebração
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
                    Celebração da Parceria
                    <span class="text-sm font-normal text-gray-500 ml-1">— {{ $proposta->osc->name }}</span>
                </h2>
            </div>
        </x-slot>
    @endunless

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @if($ehOsc)
                <div>
                    <p class="text-sm text-indigo-600">
                        <a href="{{ route('portal.minhas-propostas') }}" class="hover:underline">← Minhas Propostas</a>
                    </p>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">Celebração da Parceria</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $proposta->titulo }}</p>
                </div>
            @endif

            {{-- Identificação --}}
            <div class="bg-white shadow rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">OSC</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $proposta->osc->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Chamamento</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $proposta->chamamento->numero ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Valor solicitado</dt>
                        <dd class="text-gray-800 mt-0.5">
                            {{ $proposta->valor_solicitado ? 'R$ ' . number_format($proposta->valor_solicitado, 2, ',', '.') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Situação</dt>
                        <dd class="mt-0.5">
                            @if($concluida)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Concluída</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full">
                                    Com {{ $setorLabel($proposta->celebracao_setor) }}
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if($proposta->objeto)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Objeto</p>
                        <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $proposta->objeto }}</p>
                    </div>
                @endif
            </div>

            {{-- Trâmite --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Trâmite da Celebração</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Do plano de trabalho ao empenho global, com as análises e assinaturas de cada setor.
                        </p>
                    </div>
                    @if($concluida)
                        <span class="px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full whitespace-nowrap">
                            Concluída em {{ $proposta->celebracao_concluida_em->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>

                <x-tramite-trilha
                    :etapas="\App\Models\Proposta::ETAPAS_CELEBRACAO"
                    :atual="$etapaAtual"
                    :concluido="$concluida"
                    :labels="\App\Models\Proposta::SETORES_CELEBRACAO" />

                @unless($concluida)
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

                    @if($souDoSetor)
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                            @if($proposta->ultimaEtapaCelebracao())
                                <form action="{{ route('celebracao.concluir', $proposta) }}" method="POST"
                                      data-confirm="Concluir a Celebração? A parceria estará apta a iniciar a execução.">
                                    @csrf
                                    <button type="submit" @disabled($pendencias)
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Concluir Celebração
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('celebracao.avancar', $proposta) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <textarea name="parecer" rows="2" placeholder="Observação (opcional)"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                    <button type="submit" @disabled($pendencias)
                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Encaminhar para
                                        {{ $setorLabel(\App\Models\Proposta::ETAPAS_CELEBRACAO[$etapaAtual + 1]['setor']) }}
                                    </button>
                                </form>
                            @endif

                            @if($etapaAtual > 0 && !$ehOsc)
                                <form action="{{ route('celebracao.devolver', $proposta) }}" method="POST"
                                      class="space-y-2 pt-2 border-t border-gray-100">
                                    @csrf
                                    <textarea name="parecer" rows="2" required placeholder="Motivo da devolução (obrigatório)"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"></textarea>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-red-700 border border-red-300 rounded-md hover:bg-red-50">
                                        Devolver para {{ $setorLabel($proposta->setorAnteriorCelebracao()) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                            A Celebração está com <strong>{{ $setorLabel($proposta->celebracao_setor) }}</strong>.
                            Só esse setor pode movimentá-la.
                        </p>
                    @endif
                @endunless

                @if($proposta->celebracaoTramitacoes->isNotEmpty())
                    <details class="mt-4 pt-4 border-t border-gray-100">
                        <summary class="text-xs text-indigo-600 cursor-pointer hover:underline">
                            Histórico de movimentações ({{ $proposta->celebracaoTramitacoes->count() }})
                        </summary>
                        <ul class="mt-2 space-y-2">
                            @foreach($proposta->celebracaoTramitacoes as $mov)
                                <li class="text-xs text-gray-600 border-l-2 pl-3
                                    {{ $mov->status === 'devolvido' ? 'border-red-300' : 'border-gray-200' }}">
                                    <span class="font-medium">
                                        {{ \App\Models\CelebracaoTramitacao::STATUS[$mov->status] ?? $mov->status }}
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

            {{-- Progresso --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold text-gray-800">Progresso da Documentação</h3>
                    <span class="text-sm text-gray-500">{{ $progresso['ok'] }}/{{ $progresso['total'] }} obrigatórias</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-green-500 h-2.5 rounded-full transition-all" style="width: {{ $progresso['percent'] }}%"></div>
                </div>
            </div>

            {{-- Checklist --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Documentos da Celebração</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Cada documento é liberado ao setor responsável na etapa correspondente do trâmite.
                    </p>
                </div>
                @include('pecas._checklist', ['pecas' => $pecas])
            </div>
        </div>
    </div>
</x-dynamic-component>
