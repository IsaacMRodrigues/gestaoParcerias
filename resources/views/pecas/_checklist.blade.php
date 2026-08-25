{{-- Espera: $pecas (Collection de App\Models\Peca)

     A lista é longa (10+ peças), então cada linha mostra só o essencial:
     estado, nome e uma ação. Formulários de upload/edição ficam recolhidos —
     abertos de uma vez, viravam uma parede de campos repetidos.

     Os badges de tipo ("modelo padrão" / "arquivo") saíram: a própria ação
     ("Preencher conteúdo" x "Enviar arquivo") já diz qual é. E como quase toda
     peça é obrigatória, marcamos só a exceção ("opcional"). --}}
@php
    /* Agrupamento por etapa do trâmite.
     *
     * A lista saía na ordem do template, misturando etapas: um documento da
     * etapa 4 aparecia entre dois da etapa 1, todos com a mesma aparência. Quem
     * abria a tela não tinha como saber o que preencher agora e o que é de
     * depois — a informação existia (cada peça sabe a sua etapa), mas a tela não
     * usava para nada.
     *
     * Agora cada etapa é um bloco, na ordem do fluxo, e o bloco diz em que pé
     * está: concluída, agora, ou depois. Os documentos fora do trâmite
     * (Dispensa, Aditivo, Apostilamento — onde selecaoSetor() é null) vão para
     * um bloco próprio no topo e a tela degrada para a lista simples de antes
     * quando NENHUMA peça é governada por trâmite. */
    $governadas = $pecas->filter->emTramite();
    $agrupar    = $governadas->isNotEmpty();
    $etapaAtual = $governadas->first()?->etapaAtualDoTramite();
    $encerrado  = (bool) $governadas->first()?->tramiteJaEncerrado();

    $SEM_ETAPA = 'livre';
    // Etapas do fluxo, para desenhar TODAS — inclusive as que não têm documento
    // próprio. Sem isto a numeração pulava: a etapa 13 (SCP elabora a Ordem de
    // Pagamento) some da lista quando a peça migra para a etapa 14 (UG assina),
    // e a tela ia de 12 para 14 como se o fluxo tivesse buraco.
    $etapasTramite = $agrupar ? $governadas->first()->etapasDoTramite() : [];

    $grupos = $agrupar
        ? $pecas->groupBy(fn ($p) => $p->emTramite() ? $p->etapaDaProximaAcao() : $SEM_ETAPA)
        : collect([$SEM_ETAPA => $pecas]);

    if ($agrupar) {
        foreach (array_keys($etapasTramite) as $i) {
            $grupos->has($i) or $grupos->put($i, collect());
        }
        $grupos = $grupos->sortKeysUsing(fn ($a, $b) => // o bloco sem etapa vem primeiro
            ($a === $SEM_ETAPA ? -1 : (int) $a) <=> ($b === $SEM_ETAPA ? -1 : (int) $b));
    }
@endphp

@foreach($grupos as $chaveGrupo => $pecasDoGrupo)
    @php
        $semEtapa = $chaveGrupo === $SEM_ETAPA;
        $nEtapa   = $semEtapa ? null : (int) $chaveGrupo;

        // Três estados possíveis, e só um deles pede ação agora.
        $grupoFeito  = !$semEtapa && ($encerrado || $nEtapa < $etapaAtual);
        $grupoAgora  = !$semEtapa && !$encerrado && $nEtapa === $etapaAtual;
        $grupoFuturo = !$semEtapa && !$encerrado && $nEtapa > $etapaAtual;

        // Etapa sem documento próprio: o setor e a ação vêm do fluxo, não das peças.
        $acaoDaEtapa = $semEtapa ? null : ($etapasTramite[$nEtapa]['acao'] ?? null);

        $setoresDoGrupo = $pecasDoGrupo->isNotEmpty()
            ? $pecasDoGrupo->map->setorDaProximaAcao()->filter()->unique()
                ->map(fn ($s) => $pecasDoGrupo->first()->rotuloDoSetor($s))->implode(' e ')
            : $governadas->first()->rotuloDoSetor($etapasTramite[$nEtapa]['setor'] ?? null);

        // Alguma peça deste grupo é minha para fazer agora? Contra-assinar conta:
        // é a única ação pendente do Termo quando ele espera a OSC.
        $minhaVez = $pecasDoGrupo->contains(fn ($p) =>
            $p->podePreencher(auth()->user())
            || $p->podeAssinar(auth()->user())
            || $p->podeContraAssinar(auth()->user()));
    @endphp

    @if($agrupar)
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-6 py-2.5 border-t border-gray-100
                    {{ $grupoAgora ? 'bg-accent-50' : 'bg-gray-50' }}">
            <span class="text-[12px] font-bold uppercase tracking-wider
                         {{ $grupoAgora ? 'text-accent-800' : ($grupoFuturo ? 'text-gray-400' : 'text-gray-500') }}">
                @if($semEtapa)
                    {{-- Rótulo neutro: o mesmo partial serve Seleção, Celebração e
                         Aditivos, e "do chamamento" só faria sentido no primeiro. --}}
                    Documentos gerais
                @else
                    Etapa {{ $nEtapa + 1 }} · {{ $setoresDoGrupo }}
                @endif
            </span>

            @if($semEtapa)
                <span class="text-xs text-gray-400">disponíveis a qualquer momento</span>
            @elseif($grupoFeito)
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Etapa vencida
                </span>
            @elseif($grupoAgora)
                <span class="px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide bg-accent-500 text-white rounded">
                    Etapa atual
                </span>
                @if($minhaVez)
                    <span class="text-xs font-semibold text-accent-800">— é a sua vez</span>
                @else
                    <span class="text-xs text-accent-700">— aguardando {{ $setoresDoGrupo }}</span>
                @endif
            @else
                <span class="text-xs text-gray-400">depois</span>
            @endif
        </div>
    @endif

    @php
        /* Espaço extra de anexo, onde a tela dona ofereceu a rota.
         *
         * Vale nos dois tipos de bloco, e por motivos diferentes: na etapa
         * corrente, porque é ali que se está trabalhando; nos documentos
         * gerais, porque é onde moram as peças sem etapa — a fase do edital e
         * a Dispensa inteira, que não passa por julgamento. Em ambos, $minhaVez
         * já responde se a pessoa pode agir no bloco. */
        $podeAnexarExtra = ($rotaAnexoExtra ?? null) && $minhaVez && ($grupoAgora || $semEtapa);
    @endphp

    {{-- Etapa que não tem documento próprio: em vez de sumir da lista (e abrir
         buraco na numeração), aparece dizendo o que se faz nela. É o caso da
         assinatura das partes e da assinatura da Ordem de Pagamento, cujos
         documentos ficam no bloco de quem os emitiu. --}}
    @if($pecasDoGrupo->isEmpty() && $acaoDaEtapa)
        <p class="px-6 py-3 text-xs text-gray-400 {{ $grupoFuturo ? 'opacity-60' : '' }}">
            {{ $acaoDaEtapa }}.
        </p>
    @endif

    {{-- Etapa futura recua: continua legível e clicável, mas para de disputar
         atenção com o que precisa ser feito agora. --}}
    <div class="divide-y divide-gray-100 {{ $grupoFuturo ? 'opacity-60' : '' }}">
    @foreach($pecasDoGrupo as $peca)
        @php
            // No trâmite da Seleção só o setor da etapa atual preenche/assina;
            // fora dele (Dispensa, Aditivo, Apostilamento) tudo segue liberado.
            $podePreencher = $peca->podePreencher(auth()->user());
            $podeAssinar   = $peca->podeAssinar(auth()->user());
            $trava         = $peca->motivoTrava(auth()->user());

            // Agrupado, a trava "Disponível na etapa 2 (SCP)" repete palavra por
            // palavra o cabeçalho logo acima. Fica só nas travas que acrescentam
            // algo — as da etapa corrente, que dizem de quem é a vez.
            if ($agrupar && !$semEtapa && $nEtapa !== $etapaAtual) {
                $trava = null;
            }

            $ehModelo   = $peca->tipo === 'modelo';
            // Arquivo não se assina: enviado, está pronto. Medir tudo por
            // assinado() deixava todo anexo preso em "falta assinar".
            $emAndamento = $peca->preenchido() && ! $peca->concluida();

            // Estilo do "botão" que abre/fecha o bloco de trabalho da peça
            $acao = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                     cursor-pointer select-none transition marker:content-none';
        @endphp

        {{-- Âncora da linha: salvar/assinar/enviar redirecionam para
             #peca-{id}, senão a página voltava ao topo a cada ação e o usuário
             tinha de reencontrar, numa lista de 18 itens, onde estava.
             scroll-margin-top desconta o cabeçalho fixo (inline: classe nova
             obrigaria a recompilar o CSS só por causa de uma margem). --}}
        <div id="peca-{{ $peca->id }}" style="scroll-margin-top:7rem" class="px-6 py-3.5">
            <div class="flex items-start gap-3">

                {{-- Estado da peça --}}
                <span class="mt-0.5 shrink-0" title="{{ $peca->concluida() ? ($ehModelo ? 'Assinado' : 'Arquivo enviado') : ($peca->preenchido() ? 'Preenchido, aguardando assinatura' : 'Pendente') }}">
                    @if($peca->concluida())
                        <span class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @elseif($peca->preenchido())
                        <span class="w-5 h-5 rounded-full border-2 border-accent-400 flex items-center justify-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-accent-400"></span>
                        </span>
                    @else
                        <span class="block w-5 h-5 rounded-full border-2 border-gray-200"></span>
                    @endif
                </span>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold {{ $peca->concluida() ? 'text-gray-500' : 'text-gray-900' }}">
                                {{ $peca->rotulo }}
                                {{-- Alguns rótulos já trazem "(opcional)" no texto; não repete --}}
                                @if(! $peca->obrigatorio && ! \Illuminate\Support\Str::contains($peca->rotulo, 'opcional', true))
                                    <span class="ml-1.5 text-xs font-medium text-gray-400">opcional</span>
                                @endif
                                {{-- Distingue o que alguém acrescentou do checklist oficial do fluxo --}}
                                @if($peca->extra)
                                    <span class="ml-1.5 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600 bg-slate-100 ring-1 ring-slate-200 rounded">
                                        anexo avulso
                                    </span>
                                @endif
                            </p>

                            {{-- Linha secundária: só aparece quando há o que informar --}}
                            @if($peca->assinado())
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Assinado por {{ $peca->assinante->name }} em {{ $peca->assinado_em->format('d/m/Y H:i') }}
                                </p>
                                @if($peca->exigeContraAssinatura())
                                    <p class="text-xs mt-0.5 {{ $peca->contraAssinado() ? 'text-gray-400' : 'text-accent-700' }}">
                                        @if($peca->contraAssinado())
                                            Contra-assinado pela OSC — {{ $peca->contraAssinante->name ?? '—' }}
                                            em {{ $peca->contra_assinado_em->format('d/m/Y H:i') }}
                                        @else
                                            Aguardando a contra-assinatura da OSC
                                        @endif
                                    </p>
                                @endif
                            @elseif($trava)
                                <p class="text-xs text-accent-700 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                    </svg>
                                    {{ $trava }}
                                </p>
                            @elseif($emAndamento)
                                <p class="text-xs text-accent-700 mt-0.5">Preenchido — falta assinar</p>
                            @endif
                        </div>

                        {{-- Arquivo já enviado: chip compacto no lugar do formulário --}}
                        @if(! $ehModelo && $peca->temArquivo())
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 max-w-xs">
                                    <span class="text-[11px] font-bold text-brand-700 uppercase shrink-0">
                                        {{ strtoupper(pathinfo($peca->arquivo_nome, PATHINFO_EXTENSION)) }}
                                    </span>
                                    <span class="text-xs text-gray-700 truncate">{{ $peca->arquivo_nome }}</span>
                                    <span class="text-xs text-gray-400 shrink-0">{{ $peca->tamanhoFormatado() }}</span>
                                </span>
                                <a href="{{ route('pecas.download', $peca) }}"
                                   class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">Baixar</a>
                                @if($podePreencher)
                                    <form action="{{ route('pecas.arquivo.remover', $peca) }}" method="POST"
                                          data-confirm="Remover este arquivo?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                                    </form>
                                @endif
                            </div>
                        @elseif(! $ehModelo && ! $podePreencher)
                            <span class="text-xs text-gray-400 shrink-0">Nenhum arquivo enviado</span>
                        @endif

                        {{-- Criado à mão, apagado à mão: as peças do template são
                             a regra do fluxo e voltariam na próxima sincronização. --}}
                        @if($peca->extra && $podePreencher)
                            <form action="{{ route('pecas.extra.destruir', $peca) }}" method="POST" class="shrink-0"
                                  data-confirm="Excluir o espaço &quot;{{ $peca->rotulo }}&quot;? O arquivo enviado nele, se houver, também sai.">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-700 transition">Excluir espaço</button>
                            </form>
                        @endif
                    </div>

                    {{-- ===== Bloco de trabalho, recolhido por padrão ===== --}}

                    {{-- MODELO: editor rico (brasão + HTML) + assinar --}}
                    @if($ehModelo)
                        {{-- Recolhido por padrão: com várias peças preenchidas e não
                             assinadas, abrir todas empilhava um editor rico atrás do
                             outro e a lista virava uma parede de documentos.
                             A exceção é a vez de contra-assinar — o botão da OSC mora
                             dentro do documento, e recolhido a tela dizia "aguardando a
                             contra-assinatura da OSC" sem nada visível em que clicar. --}}
                        <details class="mt-2 group" @if($peca->podeContraAssinar(auth()->user())) open @endif>
                            <summary class="{{ $acao }} {{ $peca->assinado()
                                        ? 'text-gray-600 bg-gray-100 hover:bg-gray-200'
                                        : 'text-brand-800 bg-brand-50 hover:bg-brand-100' }}">
                                <svg class="w-3.5 h-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ $peca->assinado() ? 'Ver documento' : ($peca->preenchido() ? 'Editar conteúdo' : 'Preencher conteúdo') }}
                            </summary>

                            <div class="mt-3">
                                @if($peca->assinado())
                                    <div class="documento-html border border-gray-200 rounded-lg p-4 bg-white text-gray-800 text-sm">
                                        {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                                        @php
                                            $qr = fn (?string $codigo) => $codigo
                                                ? \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)
                                                    ->generate(route('validacao.mostrar', $codigo))
                                                : null;
                                            $qrValidacao = $qr($peca->codigo_validacao);
                                            // Assinatura das partes: a contra-assinatura tem código próprio.
                                            $qrContra = $qr($peca->contraAssinado() ? $peca->codigo_validacao_contra : null);
                                        @endphp
                                        @include('processos._carimbo', [
                                            'peca'        => $peca,
                                            'qrValidacao' => $qrValidacao,
                                            'qrContra'    => $qrContra,
                                        ])
                                    </div>
                                    @if($peca->codigo_validacao)
                                        <p class="mt-2 text-xs text-gray-500">
                                            Código de validação:
                                            <strong class="font-mono">{{ $peca->codigo_validacao }}</strong>
                                            · <a href="{{ route('validacao.mostrar', $peca->codigo_validacao) }}" target="_blank" class="text-brand-700 font-medium hover:underline">Validar</a>
                                        </p>
                                    @endif
                                    @if($peca->contraAssinado() && $peca->codigo_validacao_contra)
                                        <p class="mt-1 text-xs text-gray-500">
                                            Contra-assinatura da OSC:
                                            <strong class="font-mono">{{ $peca->codigo_validacao_contra }}</strong>
                                            · <a href="{{ route('validacao.mostrar', $peca->codigo_validacao_contra) }}" target="_blank" class="text-brand-700 font-medium hover:underline">Validar</a>
                                        </p>
                                    @endif
                                    @if($peca->podeContraAssinar(auth()->user()))
                                        <form action="{{ route('pecas.contra-assinar', $peca) }}" method="POST" class="mt-3"
                                              data-confirm="Confirma a assinatura deste Termo pela OSC?">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-primary btn-sm">
                                                Assinar como OSC (contra-assinatura)
                                            </button>
                                        </form>
                                    {{-- Sem botão, a tela dizia só "aguardando a contra-assinatura
                                         da OSC" e ninguém sabia o que falta. Agora diz — inclusive
                                         ao membro da OSC, que vê o Termo mas não o assina. --}}
                                    @elseif($motivoContra = $peca->motivoNaoPodeContraAssinar(auth()->user()))
                                        <p class="mt-3 flex items-start gap-2 text-xs text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                            <svg class="w-4 h-4 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                            </svg>
                                            <span><strong>Assinatura das partes.</strong> {{ $motivoContra }}</span>
                                        </p>
                                    @endif
                                @elseif($podePreencher)
                                    <form action="{{ route('pecas.salvar', $peca) }}" method="POST">
                                        @csrf @method('PUT')
                                        <textarea name="conteudo" data-editor-rico>{!! old('conteudo', $peca->conteudo) !!}</textarea>
                                        <button type="submit"
                                                class="mt-2 btn btn-primary btn-sm">
                                            Salvar
                                        </button>
                                    </form>
                                @else
                                    {{-- Antes o bloco cinza aparecia sozinho, sem dizer por que
                                         não dava para editar. Agora o motivo vem junto. --}}
                                    @if($motivo = $peca->motivoNaoPodePreencher(auth()->user()))
                                        <p class="mb-2 flex items-start gap-2 text-xs text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                            <svg class="w-4 h-4 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                            </svg>
                                            <span><strong>Modo leitura.</strong> {{ $motivo }}</span>
                                        </p>
                                    @endif
                                    <div class="documento-html border border-gray-200 rounded-lg p-4 bg-gray-50 text-gray-700 text-sm">
                                        {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                                    </div>
                                @endif

                                @if($peca->preenchido() && $podeAssinar)
                                    <form action="{{ route('pecas.assinar', $peca) }}" method="POST" class="mt-2"
                                          data-confirm="Confirma a assinatura digital deste documento?">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-primary btn-sm">
                                            Assinar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </details>

                    {{-- ARQUIVO pendente: upload recolhido --}}
                    @elseif($podePreencher && ! $peca->temArquivo())
                        <details class="mt-2 group">
                            <summary class="{{ $acao }} text-brand-800 bg-brand-50 hover:bg-brand-100">
                                <svg class="w-3.5 h-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                Enviar arquivo
                            </summary>

                            <div class="mt-3">
                                <form action="{{ route('pecas.upload', $peca) }}" method="POST" enctype="multipart/form-data"
                                      class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="file" name="arquivo" required
                                           class="block text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                    <button type="submit"
                                            class="btn btn-primary btn-sm">
                                        Enviar
                                    </button>
                                </form>

                                @if($peca->puxavel())
                                    @php $docsDisponiveis = $peca->documentosDisponiveis(); @endphp
                                    @if($docsDisponiveis->isNotEmpty())
                                        <form action="{{ route('pecas.puxar', $peca) }}" method="POST"
                                              class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 flex-wrap">
                                            @csrf
                                            <span class="text-xs text-gray-500">ou puxar do módulo Gestão de Parcerias:</span>
                                            <select name="documento_id" required
                                                    class="text-xs border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 max-w-xs">
                                                <option value="">Selecione um documento…</option>
                                                @foreach($docsDisponiveis as $doc)
                                                    <option value="{{ $doc->id }}">
                                                        {{ \App\Models\Documento::TIPOS[$doc->tipo] ?? $doc->tipo }} — {{ $doc->nome_original }}{{ $doc->proposta->osc ? ' · ' . $doc->proposta->osc->name : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                    class="btn btn-outline btn-sm">
                                                Puxar
                                            </button>
                                        </form>
                                    @else
                                        <p class="mt-3 text-xs text-gray-400">
                                            Nenhum documento disponível para puxar do módulo Gestão de Parcerias.
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </details>

                    {{-- ARQUIVO sem envio liberado.
                         Precisa existir: as peças de modelo SEMPRE mostram um botão
                         (que abre em leitura quando bloqueado), e as de arquivo não
                         mostravam nada — a linha virava um beco sem saída, e quem
                         precisava enviar o documento achava que a interface estava
                         quebrada, sem nada em que clicar. --}}
                    @elseif(! $ehModelo && ! $podePreencher)
                        {{-- Só quando o usuário não pode agir: com envio liberado e
                             arquivo presente, o cabeçalho da linha já traz o chip com
                             Baixar e Remover, e repetir aqui seria ruído. --}}
                        <details class="mt-2 group">
                            <summary class="{{ $acao }} text-gray-600 bg-gray-100 hover:bg-gray-200">
                                <svg class="w-3.5 h-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                Por que não posso enviar?
                            </summary>

                            <div class="mt-3">
                                <p class="flex items-start gap-2 text-xs text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                    <svg class="w-4 h-4 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    <span>
                                        <strong>Envio bloqueado.</strong>
                                        {{ $peca->motivoNaoPodePreencher(auth()->user()) }}
                                    </span>
                                </p>
                                @unless($peca->temArquivo())
                                    <p class="mt-2 text-xs text-gray-500">
                                        Nada foi enviado ainda. Assim que a vez chegar ao seu setor,
                                        o botão de envio aparece aqui.
                                    </p>
                                @endunless
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    @if($podeAnexarExtra)
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/60">
            <details class="group">
                <summary class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-800
                                cursor-pointer select-none marker:content-none hover:text-brand-900">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Adicionar espaço de anexo {{ $semEtapa ? 'nos documentos gerais' : 'nesta etapa' }}
                </summary>
                <form action="{{ $rotaAnexoExtra }}" method="POST" class="mt-3 flex flex-wrap items-start gap-2">
                    @csrf
                    {{-- Em qual bloco o anexo nasce: o servidor precisa saber, e
                         não dá para deduzir — o mesmo usuário pode estar com a
                         vez na etapa e ainda assim querer um documento geral. --}}
                    <input type="hidden" name="escopo" value="{{ $semEtapa ? 'geral' : 'etapa' }}">
                    <div class="flex-1 min-w-[16rem]">
                        <input type="text" name="rotulo" maxlength="120" required
                               placeholder="Nome do anexo (ex.: Publicação — 2ª edição)"
                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm
                                      focus:ring-brand-500 focus:border-brand-500">
                        <x-input-error :messages="$errors->get('rotulo')" class="mt-1" />
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm">Criar espaço</button>
                </form>
                <p class="mt-2 text-xs text-gray-400">
                    Anexo complementar: entra como opcional e não trava o encaminhamento da etapa.
                </p>
            </details>
        </div>
    @endif
    </div>
@endforeach
