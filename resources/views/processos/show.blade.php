<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('processos.index') }}" class="hover:underline">Processos</a>
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
                    Processo {{ $processo->numero }}
                    <span class="text-sm font-normal text-gray-500 ml-1">— {{ $processo->orgao->name }}</span>
                </h2>
            </div>
            <div class="flex items-center gap-3">
                @php $color = \App\Models\Processo::STATUS_COLORS[$processo->status] ?? 'gray'; @endphp
                <span class="px-3 py-1.5 text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                    {{ \App\Models\Processo::STATUS[$processo->status] }}
                </span>
                <span class="text-sm text-gray-500">
                    Setor atual: <strong>{{ \App\Models\Processo::SETORES[$processo->setor_atual] ?? $processo->setor_atual }}</strong>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @php
                $usuarioSetor = auth()->user()->setor;
                $podeAtuar = $usuarioSetor === $processo->setor_atual;
                $atual = $processo->tramitacaoAtual();
                $aguardandoRecebimento = $atual && is_null($atual->recebido_em);
                $emAndamento = !in_array($processo->status, ['concluido', 'arquivado']);
                $etapaInfo = $processo->etapaInfo();
            @endphp

            {{-- Stepper do fluxo --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Fluxo do Planejamento</h3>
                <ol class="flex flex-wrap gap-y-3">
                    @foreach($processo->etapas() as $i => $et)
                        @php
                            $feita = $i < $processo->etapa || $processo->status === 'concluido';
                            $atualEtapa = $i === $processo->etapa && $processo->status !== 'concluido';
                        @endphp
                        <li class="flex items-center">
                            <div class="flex flex-col items-center text-center w-24">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $feita ? 'bg-green-500 text-white' : ($atualEtapa ? 'bg-indigo-600 text-white ring-4 ring-indigo-100' : 'bg-gray-200 text-gray-500') }}">
                                    {{ $feita ? '✓' : $i + 1 }}
                                </div>
                                <span class="mt-1 text-[11px] leading-tight {{ $atualEtapa ? 'text-indigo-700 font-semibold' : 'text-gray-500' }}">
                                    {{ strtoupper($et['setor']) }}
                                </span>
                            </div>
                            @if(!$loop->last)
                                <div class="w-6 h-px {{ $feita ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if($emAndamento)
                    <p class="text-sm text-gray-600 mt-4">
                        <span class="font-medium text-indigo-700">Etapa {{ $processo->etapa + 1 }}/{{ $processo->totalEtapas() }}
                        — {{ \App\Models\Processo::SETORES[$etapaInfo['setor']] ?? $etapaInfo['setor'] }}:</span>
                        {{ $etapaInfo['acao'] }}
                    </p>
                @else
                    <p class="text-sm text-green-700 mt-4 font-medium">Trâmite concluído — encaminhado para publicação no site oficial.</p>
                @endif
                @if($processo->modalidade)
                    <p class="text-sm text-gray-600 mt-2">
                        Modalidade definida pelo SCP:
                        <span class="font-medium text-indigo-700">{{ \App\Models\Processo::MODALIDADES[$processo->modalidade] ?? $processo->modalidade }}</span>
                    </p>
                @endif
            </div>

            {{-- Seleção 2.2 (Celebração) — rota de Dispensa/Inexigibilidade --}}
            @if($processo->podeVerSelecao())
                @php $progSel = \App\Models\Peca::progresso($processo->pecasSelecao); @endphp
                <a href="{{ route('processos.selecao', $processo) }}"
                   class="block bg-white shadow rounded-lg p-6 hover:ring-2 hover:ring-indigo-200 transition">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Seleção 2.2 — Celebração da Parceria</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Plano de trabalho, habilitação, pareceres, minuta e termo (itens 7–18 do checklist de dispensa).
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            @if($processo->pecasSelecao->isNotEmpty())
                                <span class="text-sm font-medium text-gray-700">{{ $progSel['ok'] }}/{{ $progSel['total'] }}</span>
                                <span class="block text-xs text-gray-400">obrigatórias</span>
                            @else
                                <span class="text-sm font-medium text-indigo-600">Iniciar &rarr;</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endif

            {{-- Alertas automáticos --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-3">Conformidade do Planejamento</h3>
                <ul class="space-y-1.5">
                    @foreach($processo->alertas() as $alerta)
                        <li class="flex items-center gap-2 text-sm">
                            @if($alerta['nivel'] === 'erro')
                                <span class="text-red-500">🔴</span>
                                <span class="text-red-700">{{ $alerta['texto'] }}</span>
                            @else
                                <span class="text-green-500">🟢</span>
                                <span class="text-green-700 font-medium">{{ $alerta['texto'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Peças do processo --}}
            @php
                $u = auth()->user();
                $rotuloPeca = function ($p) use ($processo, $u) {
                    if (!$p || $p->assinado()) return 'Ver';
                    if ($p->podeEditarConteudo($processo, $u)) return 'Preencher';
                    if ($p->podeAssinar($processo, $u)) return 'Assinar';
                    return 'Ver';
                };
                $statusPeca = function ($p) {
                    if (!$p) return 'Não criada';
                    if ($p->assinado()) return 'Assinado por ' . $p->assinante->name . ' em ' . $p->assinado_em->format('d/m/Y H:i');
                    if (!empty($p->conteudo)) return 'Preenchido — não assinado';
                    return 'Não preenchido';
                };
                $comum = ['oficio', 'termo_referencia', 'pedido_parecer', 'parecer_financeiro', 'abertura'];
                $ordem = $processo->ehDispensa()
                    ? array_merge($comum, ['justificativa_dispensa', 'parecer_cnas'])
                    : array_merge($comum, ['edital', 'solicitacao_parecer_juridico', 'parecer_juridico']);
            @endphp
            <div class="bg-white shadow rounded-lg">
                <form method="GET" action="{{ route('processos.pecas.imprimir-lote', $processo) }}"
                      onsubmit="if(!this.querySelector('input[name=&quot;pecas[]&quot;]:checked')){alert('Selecione ao menos um documento para baixar.');return false;}">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-800">Peças do Processo</h3>
                        <button type="submit"
                                class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 whitespace-nowrap">
                            ⬇ Baixar selecionados (PDF)
                        </button>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach($ordem as $i => $tipo)
                            @php $p = $processo->peca($tipo); @endphp
                            @if($p)
                                <div class="flex items-center justify-between px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($p->conteudo))
                                            <input type="checkbox" name="pecas[]" value="{{ $p->id }}"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                   title="Selecionar para download">
                                        @else
                                            <span class="inline-block w-4" title="Documento ainda não preenchido"></span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ $i + 1 }}. {{ \App\Models\ProcessoPeca::TIPOS[$tipo] }}
                                                <span class="text-xs font-normal text-gray-400">— {{ strtoupper($p->setorResponsavel()) }}</span>
                                                @if(in_array($tipo, \App\Models\ProcessoPeca::OPCIONAIS))
                                                    <span class="ml-1 text-[10px] font-medium text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full">opcional</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400">{{ $statusPeca($p) }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('processos.pecas.edit', [$processo, $p]) }}"
                                       class="text-sm text-indigo-600 hover:text-indigo-900">
                                        {{ $rotuloPeca($p) }}
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </form>
            </div>

            {{-- Trâmite --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Trâmite entre Setores</h3>
                </div>

                {{-- Histórico --}}
                <div class="px-6 py-4">
                    @forelse($processo->tramitacoes as $t)
                        <div class="flex gap-3 pb-4 last:pb-0">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 mt-1.5"></div>
                                @if(!$loop->last)<div class="w-px flex-1 bg-gray-200"></div>@endif
                            </div>
                            <div class="flex-1 pb-2">
                                <p class="text-sm text-gray-800">
                                    <strong>{{ \App\Models\Processo::SETORES[$t->de_setor] ?? $t->de_setor }}</strong>
                                    enviou para
                                    <strong>{{ \App\Models\Processo::SETORES[$t->para_setor] ?? $t->para_setor }}</strong>
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $t->enviado_em->format('d/m/Y H:i') }} por {{ $t->remetente->name }}
                                    @if($t->recebido_em)
                                        · recebido em {{ $t->recebido_em->format('d/m/Y H:i') }}
                                        @if($t->recebedor) por {{ $t->recebedor->name }} @endif
                                    @endif
                                </p>
                                @if($t->parecer)
                                    <p class="text-sm text-gray-600 mt-1 bg-gray-50 px-3 py-2 rounded">{{ $t->parecer }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-2">Processo ainda na Unidade Gestora — sem trâmite registrado.</p>
                    @endforelse
                </div>

                {{-- Ações de trâmite (guiado) --}}
                @if($emAndamento)
                    @if(!$podeAtuar)
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 text-sm text-gray-500">
                            Este processo está com o setor
                            <strong>{{ \App\Models\Processo::SETORES[$processo->setor_atual] ?? $processo->setor_atual }}</strong>.
                            Apenas usuários desse setor podem movimentá-lo.
                        </div>
                    @elseif($aguardandoRecebimento)
                        <div class="px-6 py-4 border-t border-gray-100 bg-blue-50 flex items-center justify-between">
                            <p class="text-sm text-blue-800">Registre o recebimento para iniciar a etapa.</p>
                            <form action="{{ route('processos.receber', $processo) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                    Registrar Recebimento
                                </button>
                            </form>
                        </div>
                    @else
                        @php $pendencias = $processo->pendenciasParaAvancar(); @endphp
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-4">
                            {{-- Aviso consultivo de conformidade (não bloqueia) --}}
                            @if(!$processo->estaApto())
                                <p class="text-sm text-amber-700">
                                    ⚠️ Há alertas de conformidade acima (consultivos). Você pode encaminhar mesmo assim.
                                </p>
                            @endif

                            {{-- Pendências de assinatura (bloqueiam o encaminhamento) --}}
                            @if(!empty($pendencias) && !$processo->ultimaEtapa())
                                <p class="text-sm text-red-700">
                                    🔴 Assine antes de encaminhar: <strong>{{ implode(', ', $pendencias) }}</strong>.
                                </p>
                            @endif

                            @if($processo->ultimaEtapa())
                                {{-- Concluir (última etapa) --}}
                                <form action="{{ route('processos.concluir', $processo) }}" method="POST"
                                      onsubmit="return confirm('Concluir o processo e encaminhar para publicação?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                        Concluir (encaminhar para publicação)
                                    </button>
                                </form>

                            @elseif($processo->etapaEhAnalise())
                                {{-- Etapa de análise (SCP): definir a modalidade e APROVAR, ou REJEITAR --}}
                                <p class="text-sm text-gray-600">Analise os documentos acima, defina a modalidade da seleção e decida:</p>
                                <form action="{{ route('processos.avancar', $processo) }}" method="POST" class="space-y-4"
                                      onsubmit="return confirm('Aprovar com a modalidade selecionada e liberar para a próxima etapa?')">
                                    @csrf
                                    <div>
                                        <x-input-label value="Modalidade da seleção (define o caminho do processo)" class="mb-2" />
                                        <div class="space-y-2">
                                            @foreach(\App\Models\Processo::MODALIDADES as $valor => $rotulo)
                                                <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30">
                                                    <input type="radio" name="modalidade" value="{{ $valor }}" required
                                                           class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                                           @checked(old('modalidade', $processo->modalidade) === $valor)>
                                                    <span>
                                                        <span class="block text-sm font-medium text-gray-800">{{ $rotulo }}</span>
                                                        <span class="block text-xs text-gray-500">{{ \App\Models\Processo::MODALIDADES_DESC[$valor] ?? '' }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-input-error :messages="$errors->get('modalidade')" class="mt-1" />
                                    </div>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                        ✓ Aprovar e liberar para a {{ strtoupper($processo->proximoSetor()) }}
                                    </button>
                                </form>
                                <form action="{{ route('processos.devolver', $processo) }}" method="POST"
                                      class="pt-3 border-t border-gray-200 space-y-2">
                                    @csrf
                                    <x-input-label for="motivo" value="Rejeitar e devolver para correção (informe o motivo)" />
                                    <div class="flex gap-2">
                                        <input id="motivo" name="parecer" type="text"
                                               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm"
                                               placeholder="Motivo da rejeição / correções necessárias...">
                                        <button type="submit"
                                                class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 whitespace-nowrap">
                                            ✕ Rejeitar
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('parecer')" class="mt-1" />
                                </form>

                            @else
                                {{-- Etapa com documento: encaminhar para o próximo setor --}}
                                <form action="{{ route('processos.avancar', $processo) }}" method="POST" class="space-y-3"
                                      @if(!$processo->estaApto()) onsubmit="return confirm('Há alertas de conformidade. Encaminhar mesmo assim?')" @endif>
                                    @csrf
                                    <div>
                                        <x-input-label for="parecer" value="Parecer / observação (opcional)" />
                                        <input id="parecer" name="parecer" type="text"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                               placeholder="Observação do setor antes de encaminhar...">
                                    </div>
                                    <button type="submit"
                                            @disabled(!empty($pendencias))
                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Encaminhar para {{ \App\Models\Processo::SETORES[$processo->proximoSetor()] ?? $processo->proximoSetor() }}
                                    </button>
                                </form>

                                {{-- Devolver para etapa anterior --}}
                                @if($processo->etapa > 0)
                                    <form action="{{ route('processos.devolver', $processo) }}" method="POST"
                                          class="pt-3 border-t border-gray-200 space-y-2">
                                        @csrf
                                        <x-input-label for="motivo" value="Devolver para {{ \App\Models\Processo::SETORES[$processo->setorAnterior()] ?? $processo->setorAnterior() }} (informe o motivo)" />
                                        <div class="flex gap-2">
                                            <input id="motivo" name="parecer" type="text"
                                                   class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm"
                                                   placeholder="Motivo da devolução...">
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-sm font-medium text-amber-700 border border-amber-300 rounded-md hover:bg-amber-50 whitespace-nowrap">
                                                Devolver
                                            </button>
                                        </div>
                                        <x-input-error :messages="$errors->get('parecer')" class="mt-1" />
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
