{{--
    Arquivos da OSC: um quadro por grupo, no mesmo desenho da lista de
    documentos dos trâmites. Serve ao portal (a OSC anexa) e à Prefeitura (vê;
    na tela da proposta, analisa nesta parceria).

    @param $osc          Osc (com arquivos carregados)
    @param $podeEditar   bool — a OSC pode anexar nova versão
    @param $proposta     ?Proposta — na tela da proposta: análise nesta parceria
    @param $podeAnalisar bool
--}}
@php
    $atuais   = $osc->arquivosAtuais();
    $baixa    = $osc->arquivos->first()?->podeBaixar(auth()->user()) ?? false;
    $versoes  = $osc->arquivos->groupBy('tipo');
    $proposta = $proposta ?? null;
    $podeAnalisar = $podeAnalisar ?? false;
    $analises = $proposta
        ? \App\Models\OscArquivoAnalise::where('proposta_id', $proposta->id)->with('analista')->get()->keyBy('osc_arquivo_id')
        : collect();
    $descricoes = [
        'certidoes'      => 'Com data de validade. Um aviso chega por e-mail ' . \App\Models\OscArquivo::DIAS_AVISO_VENCIMENTO . ' dias antes de vencer.',
        'institucionais' => 'Estatuto e ata de eleição da diretoria atual.',
        'declaracoes'    => 'Abra o texto já preenchido com o cadastro, imprima, assine e anexe.',
    ];
    $acao = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer select-none transition marker:content-none';
    $seta = '<svg class="w-3.5 h-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
    $campo = 'block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm';
@endphp

<div class="space-y-6">
    @foreach(\App\Models\OscArquivo::GRUPOS as $chaveGrupo => $grupo)
        @php
            $emDia = collect(array_keys($grupo['itens']))
                ->filter(fn ($t) => isset($atuais[$t]) && ! $atuais[$t]->vencida())->count();
            $total = count($grupo['itens']);
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @include('pecas._cabecalho', [
                'titulo'    => $grupo['rotulo'],
                'descricao' => $descricoes[$chaveGrupo] ?? '',
                'progresso' => ['ok' => $emDia, 'total' => $total, 'percent' => (int) round($emDia / $total * 100)],
                'rotuloProgresso'  => 'anexados e em dia',
                'mensagemCompleta' => 'Grupo completo e em dia.',
            ])

            <div class="divide-y divide-gray-100">
                @foreach($grupo['itens'] as $tipo => $rotulo)
                    @php
                        $atual = $atuais[$tipo] ?? null;
                        $historico = $versoes[$tipo] ?? collect();
                        $analise = $atual ? ($analises[$atual->id] ?? null) : null;
                        $emDiaItem = $atual && ! $atual->vencida();
                    @endphp
                    <div id="arquivo-{{ $tipo }}" style="scroll-margin-top:7rem" class="px-6 py-3.5">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 shrink-0" title="{{ ! $atual ? 'Não anexado' : ($atual->vencida() ? 'Vencido' : 'Anexado') }}">
                                @if($emDiaItem && ! $atual->venceEmBreve())
                                    <span class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @elseif($atual)
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
                                        <p class="text-sm font-semibold text-gray-900">{{ $rotulo }}</p>

                                        @if(! $atual)
                                            <p class="text-xs text-accent-700 mt-0.5">Não anexado</p>
                                        @else
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Versão {{ $atual->versao }} · enviada em {{ $atual->created_at->format('d/m/Y') }}
                                                @if($atual->validade)
                                                    ·
                                                    @if($atual->vencida())
                                                        <span class="font-semibold text-red-700">vencida em {{ $atual->validade->format('d/m/Y') }}</span>
                                                    @elseif($atual->venceEmBreve())
                                                        <span class="font-semibold text-accent-700">vence em {{ $atual->validade->format('d/m/Y') }}</span>
                                                    @else
                                                        válida até {{ $atual->validade->format('d/m/Y') }}
                                                    @endif
                                                @endif
                                            </p>
                                        @endif

                                        @if($proposta && $atual)
                                            @if($analise?->situacao === 'aprovado')
                                                <p class="text-xs font-semibold text-brand-700 mt-0.5">
                                                    Aprovado nesta parceria por {{ $analise->analista?->name ?? '—' }} em {{ $analise->analisado_em->format('d/m/Y') }}
                                                </p>
                                            @elseif($analise?->situacao === 'recusado')
                                                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                                    <span class="font-semibold">Recusado nesta parceria</span>
                                                    por {{ $analise->analista?->name ?? '—' }} em {{ $analise->analisado_em->format('d/m/Y') }}@if($analise->motivo): {{ $analise->motivo }}@endif
                                                </div>
                                            @else
                                                <p class="text-xs text-accent-700 mt-0.5">Aguardando análise nesta parceria</p>
                                            @endif
                                        @endif
                                    </div>

                                    @if($atual)
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 max-w-xs">
                                                <span class="text-[11px] font-bold text-brand-700 uppercase shrink-0">
                                                    {{ strtoupper(pathinfo($atual->arquivo_nome, PATHINFO_EXTENSION)) }}
                                                </span>
                                                <span class="text-xs text-gray-700 truncate">{{ $atual->arquivo_nome }}</span>
                                                <span class="text-xs text-gray-400 shrink-0">{{ $atual->tamanhoFormatado() }}</span>
                                            </span>
                                            @if($baixa)
                                                <a href="{{ route('arquivos-osc.download', $atual) }}"
                                                   class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">Baixar</a>
                                            @endif
                                        </div>
                                    @elseif(! $podeEditar)
                                        <span class="text-xs text-gray-400 shrink-0">Nenhum arquivo enviado</span>
                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap items-start gap-2">
                                    @if($podeEditar)
                                        <details class="group max-w-full">
                                            <summary class="{{ $acao }} text-brand-800 bg-brand-50 hover:bg-brand-100">
                                                {!! $seta !!}
                                                {{ $atual ? 'Enviar nova versão' : 'Enviar arquivo' }}
                                            </summary>
                                            <form action="{{ route('portal.arquivos.store', $tipo) }}" method="POST" enctype="multipart/form-data"
                                                  class="mt-3 flex flex-wrap items-end gap-3">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">PDF, JPG ou PNG — até 10 MB</label>
                                                    <input type="file" name="arquivo" required accept=".pdf,.jpg,.jpeg,.png"
                                                           class="block text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                                </div>
                                                @if(\App\Models\OscArquivo::exigeValidade($tipo))
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Válida até *</label>
                                                        <input type="date" name="validade" required min="{{ now()->format('Y-m-d') }}" class="{{ $campo }}">
                                                    </div>
                                                @endif
                                                <button type="submit" class="btn btn-primary btn-sm">Enviar</button>
                                            </form>
                                        </details>

                                        @if(\App\Models\OscArquivo::ehDeclaracao($tipo))
                                            <a href="{{ route('portal.arquivos.declaracao', $tipo) }}" target="_blank"
                                               class="{{ $acao }} text-gray-600 bg-gray-100 hover:bg-gray-200">Texto para assinar</a>
                                        @endif
                                    @endif

                                    @if($historico->count() > 1 || ($historico->isNotEmpty() && ! $podeEditar))
                                        <details class="group max-w-full">
                                            <summary class="{{ $acao }} text-gray-600 bg-gray-100 hover:bg-gray-200">
                                                {!! $seta !!}
                                                Histórico ({{ $historico->count() }} {{ $historico->count() === 1 ? 'versão' : 'versões' }})
                                            </summary>
                                            <ul class="mt-3 space-y-1 text-xs text-gray-600">
                                                @foreach($historico as $versao)
                                                    <li>
                                                        Versão {{ $versao->versao }} ·
                                                        @if($baixa)
                                                            <a href="{{ route('arquivos-osc.download', $versao) }}" class="text-brand-700 font-medium hover:underline">{{ $versao->arquivo_nome }}</a>
                                                        @else
                                                            {{ $versao->arquivo_nome }}
                                                        @endif
                                                        · {{ $versao->created_at->format('d/m/Y H:i') }}
                                                        @if($versao->remetente) · {{ $versao->remetente->name }} @endif
                                                        @if($versao->validade) · validade {{ $versao->validade->format('d/m/Y') }} @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif

                                    @if($proposta && $podeAnalisar && $atual)
                                        <details class="group max-w-full" @if($analise === null) open @endif>
                                            <summary class="{{ $acao }} text-brand-800 bg-brand-50 hover:bg-brand-100">
                                                {!! $seta !!}
                                                {{ $analise ? 'Rever a análise' : 'Analisar nesta parceria' }}
                                            </summary>
                                            <form action="{{ route('propostas.arquivos-osc.analisar', [$proposta, $atual]) }}" method="POST"
                                                  class="mt-3 flex flex-wrap items-end gap-2">
                                                @csrf
                                                <select name="situacao" required class="border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                                    @foreach(\App\Models\OscArquivoAnalise::SITUACOES as $k => $lbl)
                                                        <option value="{{ $k }}" @selected($analise?->situacao === $k)>{{ $lbl }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="motivo" placeholder="Motivo (obrigatório na recusa)" value="{{ $analise?->motivo }}"
                                                       class="flex-1 min-w-[12rem] border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                                <button type="submit" class="btn btn-primary btn-sm">Registrar</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
