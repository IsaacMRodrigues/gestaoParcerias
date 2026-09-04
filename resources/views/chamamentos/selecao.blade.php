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
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
                    Seleção e Celebração
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        — {{ $chamamento->numero ? $chamamento->numero . ' · ' : '' }}{{ $chamamento->titulo }}
                    </span>
                </h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-brand-50 text-brand-700 rounded-full">
                {{ \App\Models\Peca::CATEGORIA_LABELS[$categoria] ?? $categoria }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Dados do Chamamento --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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
                        <x-selo-modalidade :tipo="$chamamento->tipo" />
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
                        <a href="{{ route('processos.show', $chamamento->processo) }}" class="text-brand-600 hover:underline font-medium">
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
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Trâmite da Seleção</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Julgamento das propostas → publicações → homologação pelo Prefeito.
                            </p>
                        </div>
                        @if($concluida)
                            <span class="px-2.5 py-1 text-xs font-medium bg-brand-100 text-brand-800 rounded-full whitespace-nowrap">
                                Encerrada em {{ $chamamento->selecao_concluida_em->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs font-medium bg-accent-100 text-accent-800 rounded-full whitespace-nowrap">
                                Com {{ \App\Models\Chamamento::SETORES_SELECAO[$chamamento->selecao_setor] ?? $chamamento->selecao_setor }}
                            </span>
                        @endif
                    </div>

                    {{-- Resultado do julgamento. Antes a tela dizia "segue para a
                         Celebração" e não havia como chegar lá: nenhuma proposta
                         ficava aprovada, e a Celebração exige isso. --}}
                    @if($concluida)
                        @if($vencedoras->isNotEmpty())
                            <div class="mb-4 bg-brand-50 border border-brand-200 rounded-lg p-3">
                                <p class="text-sm font-semibold text-brand-900">Adjudicado — seguir para a Celebração</p>
                                <div class="mt-2 space-y-1.5">
                                    @foreach($vencedoras as $proposta)
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <span class="text-sm text-gray-700">
                                                {{ $proposta->titulo }}
                                                <span class="text-gray-500">— {{ $proposta->osc?->name }}</span>
                                            </span>
                                            <a href="{{ route('celebracao.show', $proposta) }}" class="btn btn-primary btn-sm">
                                                Abrir Celebração →
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($emJulgamento->isNotEmpty() && $meuSetor === 'ug')
                            {{-- Encerrado antes de a adjudicação existir: o resultado
                                 nunca foi registrado, então a parceria não tem por onde
                                 seguir. Declarar aqui destrava. --}}
                            <form action="{{ route('chamamentos.selecao.adjudicar', $chamamento) }}" method="POST"
                                  data-confirm="Declarar as vencedoras? As demais propostas serão reprovadas."
                                  class="mb-4 bg-accent-50 border border-accent-200 rounded-lg p-3">
                                @csrf
                                <p class="text-sm font-semibold text-accent-900">Seleção encerrada sem vencedora declarada</p>
                                <p class="text-xs text-accent-800 mt-0.5 mb-2">
                                    A Celebração só abre para proposta aprovada. Marque quem venceu o julgamento
                                    — as demais serão reprovadas.
                                </p>
                                @foreach($emJulgamento as $proposta)
                                    <label class="flex items-start gap-2 text-sm text-gray-700 py-1">
                                        <input type="checkbox" name="vencedoras[]" value="{{ $proposta->id }}"
                                               class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <span>
                                            {{ $proposta->titulo }}
                                            <span class="text-gray-500">— {{ $proposta->osc?->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                                <x-input-error :messages="$errors->get('vencedoras')" class="mt-1" />
                                <button type="submit" class="btn btn-primary btn-sm mt-2">Declarar vencedora(s)</button>
                            </form>
                        @endif
                    @endif

                    {{-- Trilha das etapas --}}
                    <ol class="space-y-2">
                        @foreach(\App\Models\Chamamento::ETAPAS_SELECAO as $i => $etapa)
                            @php
                                $feita = $concluida || $i < $etapaAtual;
                                $agora = !$concluida && $i === $etapaAtual;
                            @endphp
                            <li class="flex items-start gap-3 text-sm">
                                <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full border text-[12px] font-bold flex items-center justify-center
                                    {{ $feita ? 'bg-brand-100 border-brand-300 text-brand-700'
                                             : ($agora ? 'bg-brand-100 border-brand-300 text-brand-700'
                                                       : 'bg-white border-gray-300 text-gray-400') }}">
                                    {{ $feita ? '✓' : $i + 1 }}
                                </span>
                                <span class="{{ $agora ? 'text-gray-900 font-medium' : ($feita ? 'text-gray-500' : 'text-gray-400') }}">
                                    <span class="text-xs font-semibold uppercase tracking-wide
                                        {{ $agora ? 'text-brand-600' : 'text-gray-400' }}">
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
                            <div class="mt-4 bg-accent-50 border border-accent-200 rounded-md p-3">
                                <p class="text-xs font-semibold text-accent-800">Pendências desta etapa:</p>
                                <ul class="mt-1 text-xs text-accent-700 list-disc list-inside space-y-0.5">
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
                                          data-confirm="Encerrar a Seleção? O chamamento será homologado e as propostas não escolhidas serão reprovadas.">
                                        @csrf

                                        {{-- Adjudicar: o Termo que encerra a Seleção é de
                                             ADJUDICAÇÃO e homologação. Sem dizer quem venceu,
                                             o chamamento era encerrado e nenhuma proposta ficava
                                             'aprovada' — e a Celebração, que exige isso, nunca
                                             abria. --}}
                                        @if($emJulgamento->isNotEmpty())
                                            <div class="mb-3 border border-gray-200 rounded-lg p-3">
                                                <p class="text-sm font-semibold text-gray-900">Proposta(s) vencedora(s)</p>
                                                <p class="text-xs text-gray-500 mt-0.5 mb-2">
                                                    O que for marcado é adjudicado e segue para a Celebração;
                                                    o restante é reprovado neste mesmo ato.
                                                </p>
                                                @foreach($emJulgamento as $proposta)
                                                    <label class="flex items-start gap-2 text-sm text-gray-700 py-1">
                                                        <input type="checkbox" name="vencedoras[]" value="{{ $proposta->id }}"
                                                               class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                        <span>
                                                            {{ $proposta->titulo }}
                                                            <span class="text-gray-500">— {{ $proposta->osc?->name }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                                <x-input-error :messages="$errors->get('vencedoras')" class="mt-1" />
                                            </div>
                                        @endif

                                        <button type="submit" @disabled($pendencias)
                                                class="btn btn-primary">
                                            Encerrar Seleção (adjudicar e homologar)
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('chamamentos.selecao.avancar', $chamamento) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <textarea name="parecer" rows="2" placeholder="Observação (opcional)"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                                        <button type="submit" @disabled($pendencias)
                                                class="btn btn-primary">
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
                                                class="btn btn-danger-outline">
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
                            <summary class="text-xs text-brand-600 cursor-pointer hover:underline">
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

            {{-- Recursos contra o resultado provisório --}}
            @if($chamamento->temTramiteSelecao() && ($chamamento->recursos->isNotEmpty() || $chamamento->faseRecursalAberta()))
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Recursos</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Protocolados pelas OSCs contra o resultado provisório. Cada recurso precisa de
                                resposta antes do resultado definitivo.
                            </p>
                        </div>
                        @php $semResp = $chamamento->recursos->whereNull('respondido_em')->count(); @endphp
                        @if($semResp > 0)
                            <span class="px-2.5 py-1 text-xs font-medium bg-accent-100 text-accent-800 rounded-full whitespace-nowrap">
                                {{ $semResp }} sem resposta
                            </span>
                        @endif
                    </div>

                    @forelse($chamamento->recursos as $rec)
                        <div class="px-6 py-4 border-b border-gray-100 last:border-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ $rec->osc->name ?? 'OSC' }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Protocolado em {{ $rec->protocolado_em?->format('d/m/Y H:i') }}
                                        @if($rec->temArquivo())
                                            · <a href="{{ route('recursos.download', $rec) }}" class="text-brand-600 hover:underline">
                                                {{ $rec->arquivo_nome }} ({{ $rec->tamanhoFormatado() }})
                                            </a>
                                        @endif
                                    </p>
                                </div>
                                @if($rec->respondido())
                                    @php $cor = \App\Models\Recurso::RESULTADO_COLORS[$rec->resultado] ?? 'gray'; @endphp
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 rounded-full whitespace-nowrap">
                                        {{ $rec->resultadoLabel() }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full whitespace-nowrap">
                                        Aguardando resposta
                                    </span>
                                @endif
                            </div>

                            @if($rec->fundamentacao)
                                <div class="mt-2 bg-gray-50 rounded-md p-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fundamentação da OSC</p>
                                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $rec->fundamentacao }}</p>
                                </div>
                            @endif

                            @if($rec->respondido())
                                <div class="mt-2 border-l-2 border-brand-200 pl-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                        Resposta da Unidade Gestora
                                    </p>
                                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $rec->resposta }}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $rec->respondente->name ?? '—' }} · {{ $rec->respondido_em->format('d/m/Y H:i') }}
                                        @if($rec->codigo_validacao)
                                            · código <strong class="font-mono">{{ $rec->codigo_validacao }}</strong>
                                        @endif
                                    </p>
                                </div>
                            @elseif($chamamento->faseRecursalAberta() && auth()->user()->setor === $chamamento->selecao_setor)
                                <form action="{{ route('recursos.responder', $rec) }}" method="POST" class="mt-3 space-y-2">
                                    @csrf
                                    <div class="flex flex-wrap items-center gap-3">
                                        <label class="text-xs font-medium text-gray-500">Resultado</label>
                                        <select name="resultado" required
                                                class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                            <option value="">Selecione…</option>
                                            @foreach(\App\Models\Recurso::RESULTADOS as $k => $lbl)
                                                <option value="{{ $k }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <textarea name="resposta" rows="3" required
                                              placeholder="Fundamentação da decisão sobre o recurso"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                                    <button type="submit"
                                            class="btn btn-primary btn-sm">
                                        Responder recurso
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-6 text-sm text-gray-500">
                            Fase recursal aberta — nenhum recurso protocolado até o momento.
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Peças, com o progresso no próprio cabeçalho --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                @include('pecas._cabecalho', [
                    'titulo' => 'Documentos da Seleção',
                    'descricao' => 'Documentos do editor saem com brasão e assinatura digital; os demais são arquivos anexados.',
                    'progresso' => $progresso,
                ])
                {{-- A rota habilita o botão de criar espaço de anexo: quantas
                     publicações um chamamento exige varia de um para outro. --}}
                @include('pecas._checklist', [
                    'pecas'          => $pecas,
                    'rotaAnexoExtra' => route('chamamentos.selecao.anexos.store', $chamamento),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
