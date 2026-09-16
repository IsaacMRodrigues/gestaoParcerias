{{-- A tela da prestação de contas.

     O que a OSC vê aqui são campos, e não documentos para redigir: o ofício, o
     relatório e o resumo da folha são gerados do que ela lança, com as somas
     feitas, e aparecem no checklist prontos para assinar. --}}
@php
    $u = auth()->user();
    $ehOsc = $u->ehRepresentanteOsc();
    $daVez = $u->setorNoTramite() === $pc->setor && ! $pc->concluida();
    $podeEditar = $daVez && $pc->setor === 'osc';
    $dinheiro = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $pendencias = $pc->pendencias();
@endphp
<x-dynamic-component :component="$ehOsc ? 'portal-layout' : 'app-layout'">
    @unless($ehOsc)
        <x-slot name="header">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('prestacao-contas.index') }}" class="hover:underline">← Prestação de Contas</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $pc->rotulo() }}</h2>
                <p class="text-sm text-gray-500">{{ $pc->instrumento?->numero }} · {{ $pc->osc()?->name }}</p>
            </div>
        </x-slot>
    @endunless

    <div class="{{ $ehOsc ? 'max-w-5xl' : 'max-w-7xl' }} mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if($ehOsc)
            <div>
                <p class="text-sm text-gray-500"><a href="{{ route('prestacao-contas.index') }}" class="hover:underline">← Prestação de contas</a></p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $pc->rotulo() }}</h1>
                <p class="text-sm text-gray-500">{{ $pc->instrumento?->numero }}</p>
            </div>
        @endif

        <x-flash-message />

        {{-- Onde está --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @foreach(\App\Models\PrestacaoContas::ETAPAS as $i => $etapa)
                    <span class="px-2.5 py-1 text-xs rounded-lg {{ $i === $pc->etapa && ! $pc->concluida()
                        ? 'bg-brand-600 text-white font-semibold'
                        : ($i < $pc->etapa || $pc->concluida() ? 'bg-brand-50 text-brand-800' : 'bg-gray-100 text-gray-500') }}">
                        {{ $i + 1 }}. {{ \App\Models\PrestacaoContas::SETORES[$etapa['setor']] }}
                    </span>
                @endforeach
            </div>
            <p class="text-sm text-gray-600">
                {{ $pc->concluida()
                    ? 'Prestação de contas encerrada em ' . $pc->concluida_em->format('d/m/Y H:i') . '.'
                    : \App\Models\PrestacaoContas::ETAPAS[$pc->etapa]['acao'] . '.' }}
            </p>

            {{-- Os números do período, já somados --}}
            <div class="grid sm:grid-cols-4 gap-3 mt-4">
                @foreach([
                    ['Repasses no período', $pc->totalRepassado()],
                    ['Despesas no período', $pc->totalGasto()],
                    ['Total de créditos', $pc->totalCreditos()],
                    ['Saldo do período', $pc->saldoAtual()],
                ] as [$rotulo, $valor])
                    <div class="border border-gray-200 rounded-lg px-4 py-3">
                        <p class="text-xs text-gray-500">{{ $rotulo }}</p>
                        <p class="text-lg font-bold {{ $valor < 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $dinheiro($valor) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @if($podeEditar)
            {{-- 1. Ofício --}}
            <form action="{{ route('prestacao-contas.atualizar', $pc) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                @csrf @method('PUT') <input type="hidden" name="bloco" value="oficio">
                <h2 class="font-semibold text-gray-900">Ofício de encaminhamento</h2>
                <div class="grid sm:grid-cols-4 gap-3">
                    <div><x-input-label for="folhas" value="Nº de folhas" />
                        <x-text-input id="folhas" name="folhas" type="number" min="1" class="mt-1 block w-full" :value="$pc->folhas" /></div>
                    <div><x-input-label for="parcelas_recebidas" value="Parcelas recebidas" />
                        <x-text-input id="parcelas_recebidas" name="parcelas_recebidas" type="number" min="0" class="mt-1 block w-full" :value="$pc->parcelas_recebidas" /></div>
                    <div><x-input-label for="responsavel_nome" value="Responsável pela prestação" />
                        <x-text-input id="responsavel_nome" name="responsavel_nome" class="mt-1 block w-full" :value="$pc->responsavel_nome" /></div>
                    <div><x-input-label for="responsavel_email" value="E-mail" />
                        <x-text-input id="responsavel_email" name="responsavel_email" type="email" class="mt-1 block w-full" :value="$pc->responsavel_email" /></div>
                </div>
                <div class="sm:w-1/4"><x-input-label for="responsavel_telefone" value="Telefone" />
                    <x-text-input id="responsavel_telefone" name="responsavel_telefone" class="mt-1 block w-full" :value="$pc->responsavel_telefone" /></div>
                <button class="btn btn-secondary btn-sm">Salvar ofício</button>
            </form>

            {{-- 2. Relatório de Execução do Objeto --}}
            <form action="{{ route('prestacao-contas.atualizar', $pc) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                @csrf @method('PUT') <input type="hidden" name="bloco" value="reo">
                <h2 class="font-semibold text-gray-900">Relatório de Execução do Objeto</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div><x-input-label for="objetivo_geral" value="Objetivo geral (do Plano de Trabalho)" />
                        <textarea id="objetivo_geral" name="objetivo_geral" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ $pc->objetivo_geral }}</textarea></div>
                    <div><x-input-label for="objetivos_especificos" value="Objetivos específicos" />
                        <textarea id="objetivos_especificos" name="objetivos_especificos" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ $pc->objetivos_especificos }}</textarea></div>
                </div>

                <p class="text-sm font-medium text-gray-700 pt-2">Monitoramento das metas</p>
                @if($pc->metas->isEmpty())
                    <p class="text-xs text-gray-500">O Plano de Trabalho desta parceria não tem metas cadastradas.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                <tr><th class="px-3 py-2 text-left">Meta</th><th class="px-3 py-2 text-left">Prevista</th>
                                    <th class="px-3 py-2 text-left">Atendida</th><th class="px-3 py-2 text-left">Cumpriu</th>
                                    <th class="px-3 py-2 text-left">Justificativa (se não atendeu)</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($pc->metas as $m)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $m->descricao }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ $m->quantidade_prevista ?: '—' }}</td>
                                        <td class="px-3 py-2"><input name="metas[{{ $m->id }}][quantidade_atendida]" value="{{ $m->quantidade_atendida }}"
                                            class="w-24 border-gray-300 rounded text-sm focus:ring-brand-500 focus:border-brand-500"></td>
                                        <td class="px-3 py-2"><input type="checkbox" name="metas[{{ $m->id }}][cumpriu]" value="1" @checked($m->cumpriu)
                                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></td>
                                        <td class="px-3 py-2"><input name="metas[{{ $m->id }}][justificativa]" value="{{ $m->justificativa }}"
                                            class="w-full border-gray-300 rounded text-sm focus:ring-brand-500 focus:border-brand-500"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div><x-input-label for="conclusao" value="Justificativas, informações complementares e conclusão" />
                    <textarea id="conclusao" name="conclusao" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ $pc->conclusao }}</textarea></div>
                <button class="btn btn-secondary btn-sm">Salvar relatório do objeto</button>
            </form>

            {{-- 3. Execução financeira --}}
            <form action="{{ route('prestacao-contas.atualizar', $pc) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                @csrf @method('PUT') <input type="hidden" name="bloco" value="ref">
                <h2 class="font-semibold text-gray-900">Execução financeira</h2>
                <p class="text-xs text-gray-500 -mt-1">
                    Repasses e despesas do período vêm da execução da parceria e já estão somados.
                    Preencha o que só o extrato mostra.
                </p>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div><x-input-label for="banco" value="Banco" /><x-text-input id="banco" name="banco" class="mt-1 block w-full" :value="$pc->banco" /></div>
                    <div><x-input-label for="agencia" value="Agência" /><x-text-input id="agencia" name="agencia" class="mt-1 block w-full" :value="$pc->agencia" /></div>
                    <div><x-input-label for="conta_corrente" value="Conta específica" /><x-text-input id="conta_corrente" name="conta_corrente" class="mt-1 block w-full" :value="$pc->conta_corrente" /></div>
                </div>
                <div class="grid sm:grid-cols-5 gap-3">
                    @foreach([
                        'saldo_anterior' => 'Saldo anterior', 'outros_creditos' => 'Outros créditos',
                        'recursos_proprios' => 'Recursos próprios', 'despesas_bancarias' => 'Despesas bancárias',
                        'valor_ressarcido' => 'Ressarcido aos cofres',
                    ] as $campo => $rotulo)
                        <div><x-input-label :for="$campo" :value="$rotulo" />
                            <x-text-input :id="$campo" :name="$campo" type="number" step="0.01" class="mt-1 block w-full" :value="$pc->{$campo}" /></div>
                    @endforeach
                </div>

                <p class="text-sm font-medium text-gray-700 pt-2">Metas financeiras — aprovado no Plano de Trabalho e glosas do período</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                            <tr><th class="px-3 py-2 text-left">Natureza</th><th class="px-3 py-2 text-left">Aprovado</th>
                                @foreach(range(1, 6) as $mes)<th class="px-3 py-2 text-left">Glosa {{ $mes }}</th>@endforeach
                                <th class="px-3 py-2 text-right">Executado</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach(\App\Models\PrestacaoContas::BLOCOS as $chave => $bloco)
                                @php $g = $pc->glosas->firstWhere('natureza', $chave); @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $bloco['rotulo'] }}</td>
                                    <td class="px-3 py-2"><input type="number" step="0.01" name="glosas[{{ $chave }}][valor_aprovado]" value="{{ $g?->valor_aprovado }}"
                                        class="w-28 border-gray-300 rounded text-sm focus:ring-brand-500 focus:border-brand-500"></td>
                                    @foreach(range(1, 6) as $mes)
                                        <td class="px-3 py-2"><input type="number" step="0.01" name="glosas[{{ $chave }}][glosa_mes_{{ $mes }}]" value="{{ $g?->{'glosa_mes_' . $mes} }}"
                                            class="w-24 border-gray-300 rounded text-sm focus:ring-brand-500 focus:border-brand-500"></td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right text-gray-600">{{ $dinheiro($pc->totalDoBloco($chave)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-secondary btn-sm">Salvar execução financeira</button>
            </form>

            {{-- 4. Bens móveis --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="font-semibold text-gray-900">Bens móveis adquiridos</h2>
                @if($pc->bens->isNotEmpty())
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach($pc->bens as $bem)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $bem->especificacao }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $bem->quantidade }} × {{ $dinheiro($bem->valor_unitario) }}</td>
                                    <td class="px-3 py-2 text-right font-medium">{{ $dinheiro($bem->total()) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <form action="{{ route('prestacao-contas.bens.destroy', [$pc, $bem]) }}" method="POST" data-confirm="Remover este bem?">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-gray-400 hover:text-red-700">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50"><td colspan="2" class="px-3 py-2 font-semibold">Total</td>
                                <td class="px-3 py-2 text-right font-bold">{{ $dinheiro($pc->totalBens()) }}</td><td></td></tr>
                        </tbody>
                    </table>
                @endif
                <form action="{{ route('prestacao-contas.bens.store', $pc) }}" method="POST" class="grid sm:grid-cols-5 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-2"><x-input-label for="especificacao" value="Especificação do bem" />
                        <x-text-input id="especificacao" name="especificacao" class="mt-1 block w-full" /></div>
                    <div><x-input-label for="quantidade" value="Quantidade" />
                        <x-text-input id="quantidade" name="quantidade" type="number" step="0.01" value="1" class="mt-1 block w-full" /></div>
                    <div><x-input-label for="valor_unitario" value="Valor unitário" />
                        <x-text-input id="valor_unitario" name="valor_unitario" type="number" step="0.01" class="mt-1 block w-full" /></div>
                    <div><button class="btn btn-secondary btn-sm w-full">Incluir bem</button></div>
                </form>
            </div>

            {{-- 5. Folha --}}
            <form action="{{ route('prestacao-contas.atualizar', $pc) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                @csrf @method('PUT') <input type="hidden" name="bloco" value="folha">
                <h2 class="font-semibold text-gray-900">Resumo da folha de pagamento</h2>
                <p class="text-xs text-gray-500 -mt-1">Só se houver despesa com pessoal. Proventos, descontos, líquido e encargos são calculados.</p>
                <div class="grid sm:grid-cols-5 gap-3">
                    @foreach([
                        'folha_funcionarios' => 'Nº de funcionários', 'folha_salarios' => 'Salários',
                        'folha_vantagens' => 'Vantagens', 'folha_adicionais' => 'Adicionais', 'folha_inss' => 'INSS',
                        'folha_irrf' => 'IRRF', 'folha_plano_saude' => 'Plano de saúde', 'folha_fgts' => 'FGTS',
                        'folha_ferias' => 'Férias', 'folha_rescisao' => 'Rescisão',
                    ] as $campo => $rotulo)
                        <div><x-input-label :for="$campo" :value="$rotulo" />
                            <x-text-input :id="$campo" :name="$campo" type="number" :step="$campo === 'folha_funcionarios' ? '1' : '0.01'"
                                          class="mt-1 block w-full" :value="$pc->{$campo}" /></div>
                    @endforeach
                </div>
                <p class="text-sm text-gray-600">
                    Proventos <strong>{{ $dinheiro($pc->folhaProventos()) }}</strong> ·
                    Descontos <strong>{{ $dinheiro($pc->folhaDescontos()) }}</strong> ·
                    Líquido <strong>{{ $dinheiro($pc->folhaLiquido()) }}</strong> ·
                    Encargos <strong>{{ $dinheiro($pc->folhaEncargos()) }}</strong>
                </p>
                <button class="btn btn-secondary btn-sm">Salvar folha</button>
            </form>
        @endif

        {{-- Checklist --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @include('pecas._cabecalho', [
                'titulo'    => 'Documentos da Prestação de Contas',
                'descricao' => 'O ofício, o relatório e o resumo da folha são gerados dos campos acima, com as somas prontas — basta conferir e assinar.',
                'progresso' => $progresso,
            ])
            @include('pecas._checklist', ['pecas' => $pecas])
        </div>

        {{-- Trâmite --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Trâmite</h2></div>
            <div class="px-6 py-4 space-y-3">
                @forelse($pc->tramitacoes as $t)
                    <p class="text-sm text-gray-800">
                        <strong>{{ \App\Models\PrestacaoContas::SETORES[$t->de_setor] ?? $t->de_setor }}</strong>
                        {{ $t->status === 'devolvido' ? 'devolveu para' : ($t->status === 'concluido' ? 'encerrou — ' : 'enviou para') }}
                        <strong>{{ \App\Models\PrestacaoContas::SETORES[$t->para_setor] ?? $t->para_setor }}</strong>
                        <span class="block text-xs text-gray-400">
                            {{ $t->enviado_em->format('d/m/Y H:i') }} por {{ $t->remetente?->name ?? '—' }}
                            @if($t->parecer) · {{ $t->parecer }} @endif
                        </span>
                    </p>
                @empty
                    <p class="text-sm text-gray-400">Ainda sem movimentação — a prestação está sendo montada pela OSC.</p>
                @endforelse
            </div>

            @if($daVez)
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
                    @if($pendencias)
                        <p class="text-sm text-red-700">Falta concluir: <strong>{{ implode(', ', $pendencias) }}</strong>.</p>
                    @endif
                    @if($pc->ultimaEtapa())
                        <form action="{{ route('prestacao-contas.concluir', $pc) }}" method="POST"
                              data-confirm="Encerrar a prestação de contas?">
                            @csrf
                            <button @disabled($pendencias) class="btn btn-primary">Encerrar prestação de contas</button>
                        </form>
                    @else
                        <form action="{{ route('prestacao-contas.avancar', $pc) }}" method="POST" class="space-y-2">
                            @csrf
                            <input name="parecer" placeholder="Observação (opcional)"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            <button @disabled($pendencias) class="btn btn-primary">
                                Encaminhar para {{ \App\Models\PrestacaoContas::SETORES[\App\Models\PrestacaoContas::ETAPAS[$pc->etapa + 1]['setor']] }}
                            </button>
                        </form>
                    @endif

                    @if($pc->etapa > 0 && $pc->setor !== 'osc')
                        <form action="{{ route('prestacao-contas.devolver', $pc) }}" method="POST" class="flex gap-2 pt-3 border-t border-gray-200">
                            @csrf
                            <input name="parecer" required placeholder="Motivo da devolução…"
                                   class="flex-1 border-gray-300 rounded-lg shadow-sm text-sm focus:ring-red-500 focus:border-red-500">
                            <button class="btn btn-secondary btn-sm !text-accent-800 !border-accent-300">Devolver</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
