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
            @endphp

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

            {{-- Aviso de recebimento pendente --}}
            @if($aguardandoRecebimento && $podeAtuar)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                    <p class="text-sm text-blue-800">
                        Este processo foi enviado ao seu setor por
                        <strong>{{ \App\Models\Processo::SETORES[$atual->de_setor] ?? $atual->de_setor }}</strong>.
                        Registre o recebimento para iniciar a análise.
                    </p>
                    <form action="{{ route('processos.receber', $processo) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Registrar Recebimento
                        </button>
                    </form>
                </div>
            @endif

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
                            {{ $oficio?->assinado() ? 'Ver' : 'Preencher' }}
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
                            {{ $tr?->assinado() ? 'Ver' : 'Preencher' }}
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
                            {{ $parecer?->assinado() ? 'Ver' : 'Preencher' }}
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
                            {{ $abertura?->assinado() ? 'Ver' : 'Preencher' }}
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

                {{-- Ações de trâmite --}}
                @if($processo->status !== 'apto' && $processo->status !== 'arquivado')
                    @if($podeAtuar && !$aguardandoRecebimento)
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                            <form action="{{ route('processos.enviar', $processo) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <x-input-label for="para_setor" value="Enviar para *" />
                                        <select id="para_setor" name="para_setor" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <option value="">Selecione...</option>
                                            @foreach(\App\Models\Processo::SETORES as $key => $label)
                                                @if($key !== $processo->setor_atual)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label for="parecer" value="Parecer / observação (opcional)" />
                                        <input id="parecer" name="parecer" type="text"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                               placeholder="Análise do setor antes de encaminhar...">
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                        Enviar Processo
                                    </button>
                                </div>
                            </form>

                            @if($processo->setor_atual === 'ug' && $processo->estaApto())
                                <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between">
                                    <p class="text-sm text-green-700">O planejamento está apto. A UG pode concluir abrindo o processo.</p>
                                    <form action="{{ route('processos.abrir', $processo) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                            Concluir / Marcar Apto
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @elseif(!$podeAtuar)
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 text-sm text-gray-500">
                            Este processo está com o setor
                            <strong>{{ \App\Models\Processo::SETORES[$processo->setor_atual] ?? $processo->setor_atual }}</strong>.
                            Apenas usuários desse setor podem encaminhá-lo.
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
