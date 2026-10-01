@php
    // A tela serve tanto à equipe da Administração quanto à OSC da parceria:
    // o layout muda, o conteúdo é o mesmo.
    $ehOsc  = !auth()->user()->temAcessoInterno();
    $layout = $ehOsc ? 'portal-layout' : 'app-layout';

    $etapaAtual = (int) $proposta->celebracao_etapa;
    $concluida  = $proposta->celebracaoConcluida();
    // setorNoTramite(): a OSC atua como setor 'osc' e não tem lotação — comparar
    // com users.setor dava sempre falso e escondia dela o botão de encaminhar,
    // deixando a parceria parada sem que ninguém pudesse movimentá-la.
    // Na etapa conjunta (UG e SCP em paralelo), cada setor tem a vez até
    // concluir a sua parte.
    $meuSetor   = auth()->user()->setorNoTramite();
    $conjunta   = $proposta->etapaConjuntaCelebracao();
    $souDoSetor = $proposta->usuarioTemAVezNaCelebracao(auth()->user())
        && ($meuSetor !== 'osc'
            || auth()->user()->osc?->id === $proposta->osc_id);
    $pendencias = $concluida ? [] : $proposta->pendenciasCelebracao($conjunta && $souDoSetor ? $meuSetor : null);
    $setorLabel = fn ($s) => \App\Models\Proposta::SETORES_CELEBRACAO[$s] ?? $s;
    $comQuem    = collect($proposta->setoresComAVezNaCelebracao())
        ->map(fn ($s) => $s === 'gestor' && $proposta->gestorDaCelebracao
            ? $setorLabel($s) . ' (' . $proposta->gestorDaCelebracao->name . ')'
            : $setorLabel($s))
        ->implode(' e ');
@endphp

<x-dynamic-component :component="$layout">
    @unless($ehOsc)
        <x-slot name="header">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
                    &rsaquo; Celebração
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
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
                    <p class="text-sm text-brand-600">
                        <a href="{{ route('portal.minhas-propostas') }}" class="hover:underline">← Minhas inscrições</a>
                    </p>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">Celebração da Parceria</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $proposta->titulo }}</p>
                </div>
            @endif

            {{-- Outras vencedoras do mesmo chamamento: cada uma tem a sua Celebração. --}}
            @if($outrasVencedoras->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4">
                    <p class="text-sm font-semibold text-gray-800">
                        Este chamamento tem {{ $outrasVencedoras->count() + 1 }} parcerias vencedoras
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">Cada uma tem a sua Celebração, com trâmite e documentos próprios.</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach($outrasVencedoras as $outra)
                            <li>
                                <a href="{{ route('celebracao.show', $outra) }}" class="text-brand-600 hover:underline">{{ $outra->titulo }}</a>
                                <span class="text-gray-500">— {{ $outra->osc?->name }}</span>
                                @if($outra->celebracaoConcluida())
                                    <span class="text-xs text-gray-400">· concluída</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Plano de trabalho aberto: a OSC e a UG editam até o documento dele
                 ser assinado (decisão da gestão, 30/09/2026). --}}
            @php
                $linkPlano = null;
                if ($proposta->planoAbertoNaCelebracao()) {
                    if ($ehOsc && auth()->user()->can('osc_propostas')) {
                        $linkPlano = route('portal.proposta.show', $proposta);
                    } elseif (\App\Http\Controllers\PlanoTrabalhoController::ugPodeEditar($proposta, auth()->user())) {
                        $linkPlano = route('propostas.show', $proposta);
                    }
                }
            @endphp
            @if($linkPlano)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4 flex items-center justify-between gap-4">
                    <p class="text-sm text-gray-700">
                        O plano de trabalho pode ser editado até o documento "Plano de Trabalho" desta Celebração ser assinado.
                    </p>
                    <a href="{{ $linkPlano }}" class="btn btn-outline btn-sm shrink-0">Editar o plano</a>
                </div>
            @endif

            {{-- Identificação --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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
                            {{-- inline-block: como <span> inline, o rótulo longo ("Com Setor de
                                 Convênios e Parcerias (SCP)") quebrava em duas linhas e a moldura
                                 se partia junto — duas meias caixas, cada uma com metade da borda.
                                 Em bloco, o texto quebra dentro de uma caixa só. --}}
                            @if($concluida)
                                <span class="inline-block px-2.5 py-1 text-xs font-semibold leading-snug bg-brand-50 text-brand-800 border border-brand-200 rounded-md">Concluída</span>
                            @else
                                <span class="inline-block px-2.5 py-1 text-xs font-semibold leading-snug bg-accent-50 text-accent-800 border border-accent-200 rounded-md">
                                    Com {{ $comQuem ?: $setorLabel($proposta->celebracao_setor) }}
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
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Trâmite da Celebração</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Do plano de trabalho ao empenho global, com as análises e assinaturas de cada setor.
                        </p>
                    </div>
                    @if($concluida)
                        <span class="px-2.5 py-1 text-xs font-medium bg-brand-100 text-brand-800 rounded-full whitespace-nowrap">
                            Concluída em {{ $proposta->celebracao_concluida_em->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>

                @include('tramite._ultima-devolucao', ['tramitacoes' => $proposta->celebracaoTramitacoes, 'pecas' => $pecas])

                <x-tramite-trilha
                    :etapas="\App\Models\Proposta::ETAPAS_CELEBRACAO"
                    :atual="$etapaAtual"
                    :concluido="$concluida"
                    :labels="\App\Models\Proposta::SETORES_CELEBRACAO" />

                @unless($concluida)
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

                    @if($souDoSetor)
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                            @if($proposta->ultimaEtapaCelebracao())
                                <form action="{{ route('celebracao.concluir', $proposta) }}" method="POST"
                                      data-confirm="Concluir a Celebração? A parceria estará apta a iniciar a execução.">
                                    @csrf
                                    <button type="submit" @disabled($pendencias)
                                            class="btn btn-primary">
                                        Concluir Celebração
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('celebracao.avancar', $proposta) }}" method="POST" class="space-y-2">
                                    @csrf
                                    {{-- Para o Gestor da Parceria, a SCP escolhe quem assina (01/10/2026). --}}
                                    @if((\App\Models\Proposta::ETAPAS_CELEBRACAO[$etapaAtual + 1]['setor'] ?? null) === 'gestor')
                                        @php $gestores = $proposta->gestoresElegiveis(); @endphp
                                        <div>
                                            <label for="gestor_id" class="block text-xs font-medium text-gray-600 mb-1">
                                                Gestor da Parceria que vai assinar
                                                @if($proposta->celebracao_gestor_id)<span class="font-normal text-gray-400">(o mesmo do Termo, se não trocar)</span>@endif
                                            </label>
                                            @if($gestores->isEmpty())
                                                <p class="text-xs text-red-700">
                                                    Nenhum usuário com o perfil Gestor da Parceria nesta Secretaria. Cadastre o Gestor antes de encaminhar.
                                                </p>
                                            @else
                                                <select name="gestor_id" id="gestor_id" @required(! $proposta->celebracao_gestor_id)
                                                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                                    <option value="">Selecione…</option>
                                                    @foreach($gestores as $g)
                                                        <option value="{{ $g->id }}" @selected((int) old('gestor_id', $proposta->celebracao_gestor_id) === $g->id)>{{ $g->name }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <x-input-error :messages="$errors->get('gestor_id')" class="mt-1" />
                                        </div>
                                    @endif
                                    <textarea name="parecer" rows="2" placeholder="Observação (opcional)"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                                    <button type="submit" @disabled($pendencias)
                                            class="btn btn-primary">
                                        @if($conjunta && count($proposta->setoresComAVezNaCelebracao()) > 1)
                                            Concluir a minha parte
                                        @else
                                            Encaminhar para
                                            {{ collect(\App\Models\Proposta::setoresDaEtapaCelebracao($etapaAtual + 1))->map($setorLabel)->implode(' e ') }}
                                        @endif
                                    </button>
                                    @if($conjunta)
                                        <p class="text-xs text-gray-500">
                                            Etapa conjunta: a UG e a SCP concluem cada uma a sua parte; a Celebração avança quando as duas concluírem.
                                        </p>
                                    @endif
                                </form>
                            @endif

                            @if($etapaAtual > 0 && !$ehOsc)
                                {{-- Devolução dirigida: o erro nem sempre está na etapa
                                     anterior. Se o documento da etapa 6 saiu errado e o
                                     trâmite já vai na 9, voltar de uma em uma faria três
                                     setores reprocessarem o que estava certo. --}}
                                <form action="{{ route('celebracao.devolver', $proposta) }}" method="POST"
                                      class="space-y-2 pt-2 border-t border-gray-100">
                                    @csrf
                                    @include('tramite._devolver-documentos', ['documentos' => $proposta->documentosDevolviveis()])
                                    <div>
                                        <label for="etapa_destino" class="block text-xs font-medium text-gray-600 mb-1">
                                            Devolver para a etapa <span class="font-normal text-gray-400">(se nenhum documento for marcado acima)</span>
                                        </label>
                                        <select name="etapa_destino" id="etapa_destino"
                                                class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500">
                                            @foreach(\App\Models\Proposta::ETAPAS_CELEBRACAO as $i => $et)
                                                @continue($i >= $etapaAtual)
                                                <option value="{{ $i }}" @selected($i === $etapaAtual - 1)>
                                                    Etapa {{ $i + 1 }} · {{ $setorLabel($et['setor']) }} — {{ \Illuminate\Support\Str::limit($et['acao'], 70) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('etapa_destino')" class="mt-1" />
                                    </div>
                                    <textarea name="parecer" rows="2" required placeholder="O que está errado e como corrigir (obrigatório — é o que o outro setor vai ler)"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"></textarea>
                                    <x-input-error :messages="$errors->get('parecer')" class="mt-1" />
                                    <button type="submit" class="btn btn-danger-outline">
                                        Devolver
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                            @if($conjunta && in_array($meuSetor, \App\Models\Proposta::setoresDaEtapaCelebracao($etapaAtual), true))
                                Seu setor já concluiu a parte dele nesta etapa. Falta <strong>{{ $comQuem }}</strong>.
                            @else
                                A Celebração está com <strong>{{ $comQuem ?: $setorLabel($proposta->celebracao_setor) }}</strong>.
                                Só esse setor pode movimentá-la.
                            @endif
                        </p>
                    @endif
                @endunless

                @if($proposta->celebracaoTramitacoes->isNotEmpty())
                    <details class="mt-4 pt-4 border-t border-gray-100">
                        <summary class="text-xs text-brand-600 cursor-pointer hover:underline">
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

            {{-- Documentos, com o progresso no próprio cabeçalho --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                @include('pecas._cabecalho', [
                    'titulo' => 'Documentos da Celebração',
                    'descricao' => 'Cada documento é liberado ao setor responsável na etapa correspondente do trâmite.',
                    'progresso' => $progresso,
                ])
                {{-- A rota habilita o botão de criar espaço de anexo na etapa
                     corrente; o partial serve outras telas que não têm isso. --}}
                @include('pecas._checklist', [
                    'pecas'          => $pecas,
                    'rotaAnexoExtra' => route('celebracao.anexos.store', $proposta),
                ])
            </div>
        </div>
    </div>

    {{-- Tela longa: trilha de 15 etapas + 18 documentos. As setas levam ao topo
         (onde ficam o trâmite e os botões de encaminhar) e ao fim da lista. --}}
    <x-atalhos-rolagem />
</x-dynamic-component>
