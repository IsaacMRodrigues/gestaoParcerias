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
                $tr = $processo->termoReferencia;
                $atual = $processo->tramitacaoAtual();
                $aguardandoRecebimento = $atual && is_null($atual->recebido_em);
                $emAndamento = !in_array($processo->status, ['concluido', 'arquivado']);
                $etapaInfo = $processo->etapaInfo();
                // só o setor responsável (e enquanto está com ele) preenche cada peça
                $ehUG = $emAndamento && $usuarioSetor === 'ug' && $processo->setor_atual === 'ug';
                $ehSeplan = $emAndamento && $usuarioSetor === 'seplan' && $processo->setor_atual === 'seplan';
            @endphp

            {{-- Stepper do fluxo --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Fluxo do Planejamento</h3>
                <ol class="flex flex-wrap gap-y-3">
                    @foreach(\App\Models\Processo::ETAPAS as $i => $et)
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
            </div>

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
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Peças do Processo</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    {{-- Ofício --}}
                    @php $oficio = $processo->peca('oficio'); @endphp
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">1. Ofício</p>
                            <p class="text-xs text-gray-400">
                                @if($oficio?->assinado())
                                    Assinado por {{ $oficio->assinante->name }} em {{ $oficio->assinado_em->format('d/m/Y H:i') }}
                                @elseif($oficio?->conteudo)
                                    Preenchido — não assinado
                                @else
                                    Não preenchido
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('processos.pecas.edit', [$processo, $oficio]) }}"
                           class="text-sm text-indigo-600 hover:text-indigo-900">
                            {{ $ehUG && !$oficio?->assinado() ? 'Preencher' : 'Ver' }}
                        </a>
                    </div>

                    {{-- Termo de Referência --}}
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">2. Termo de Referência</p>
                            <p class="text-xs text-gray-400">
                                @if($tr?->assinado())
                                    Assinado por {{ $tr->assinante->name }} em {{ $tr->assinado_em->format('d/m/Y H:i') }}
                                @elseif($tr?->objeto_resumido)
                                    Preenchido — não assinado
                                @else
                                    Não preenchido
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('processos.termo.edit', $processo) }}"
                           class="text-sm text-indigo-600 hover:text-indigo-900">
                            {{ $ehUG && !$tr?->assinado() ? 'Preencher' : 'Ver' }}
                        </a>
                    </div>

                    {{-- Parecer Financeiro --}}
                    @php $parecer = $processo->peca('parecer_financeiro'); @endphp
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">3. Parecer Financeiro</p>
                            <p class="text-xs text-gray-400">
                                @if($parecer?->assinado())
                                    Assinado por {{ $parecer->assinante->name }} em {{ $parecer->assinado_em->format('d/m/Y H:i') }}
                                @elseif($parecer?->conteudo)
                                    Preenchido — não assinado
                                @else
                                    Não preenchido (emitido pela SEPLAN)
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('processos.pecas.edit', [$processo, $parecer]) }}"
                           class="text-sm text-indigo-600 hover:text-indigo-900">
                            {{ $ehSeplan && !$parecer?->assinado() ? 'Preencher' : 'Ver' }}
                        </a>
                    </div>

                    {{-- Abertura de Processo --}}
                    @php $abertura = $processo->peca('abertura'); @endphp
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">4. Abertura de Processo</p>
                            <p class="text-xs text-gray-400">
                                @if($abertura?->assinado())
                                    Assinado por {{ $abertura->assinante->name }} em {{ $abertura->assinado_em->format('d/m/Y H:i') }}
                                @elseif($abertura?->conteudo)
                                    Preenchido — não assinado
                                @else
                                    Não preenchido (assinatura da UG)
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('processos.pecas.edit', [$processo, $abertura]) }}"
                           class="text-sm text-indigo-600 hover:text-indigo-900">
                            {{ $ehUG && !$abertura?->assinado() ? 'Preencher' : 'Ver' }}
                        </a>
                    </div>
                </div>
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

                            {{-- Avançar / Concluir --}}
                            @if($processo->ultimaEtapa())
                                <form action="{{ route('processos.concluir', $processo) }}" method="POST"
                                      onsubmit="return confirm('Concluir o processo e encaminhar para publicação?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                        Concluir (encaminhar para publicação)
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('processos.avancar', $processo) }}" method="POST" class="space-y-3"
                                      @if(!$processo->estaApto()) onsubmit="return confirm('Há alertas de conformidade. Encaminhar mesmo assim?')" @endif>
                                    @csrf
                                    <div>
                                        <x-input-label for="parecer" value="Parecer / observação (opcional)" />
                                        <input id="parecer" name="parecer" type="text"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                               placeholder="Análise do setor antes de encaminhar...">
                                    </div>
                                    <button type="submit"
                                            @disabled(!empty($pendencias))
                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Encaminhar para {{ \App\Models\Processo::SETORES[$processo->proximoSetor()] ?? $processo->proximoSetor() }}
                                    </button>
                                </form>
                            @endif

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
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
