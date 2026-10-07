{{--
    Plano de Trabalho no modelo da cliente: mesma ordem, números e nomes de itens. O item 1 vem
    do cadastro; o 9 sai somado do plano de aplicação (item 13). Serve à manifestação e à proposta.

    @param $dono         Proposta|ManifestacaoInteresse
    @param $rota         prefixo nomeado, ex.: 'portal.proposta.plano'
    @param $podeEditar   bool
    @param $mostrarMetas bool — falso nas telas do município, que têm o próprio bloco de metas
--}}
@php
    $mostrarMetas = $mostrarMetas ?? true;
    $id       = ['id' => $dono->id];
    $avisos   = $dono->divergenciasDoPlano();
    $moeda    = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $data     = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $campo    = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
    $cartao   = 'bg-white rounded-xl border border-gray-200 shadow-sm p-6';

    // Item 11: metas nas linhas, parcelas nas colunas — no mínimo as 12 do modelo.
    $parcelas     = $dono->desembolsos;
    $numParcelas  = max(12, (int) $parcelas->max('parcela'));
    $semMeta      = $parcelas->whereNull('meta_id');
    $naturezas    = $dono->naturezasDaDespesa();
@endphp

<div class="space-y-6">

    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">Plano de trabalho</h2>
        <p class="text-xs text-gray-400 mt-0.5">
            Segue o modelo de Plano de Trabalho do Município, item a item. O item 1 — Identificação — vem do
            cadastro da organização.
        </p>
    </div>

    {{-- Itens 2 a 6 --}}
    <div class="{{ $cartao }}">
        @if($podeEditar)
            <form action="{{ route($rota . '.atualizar', $id) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')

                <div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">2 – Identificação do projeto</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <x-input-label for="titulo" value="Nome do projeto *" />
                            <x-text-input id="titulo" name="titulo" type="text" required maxlength="255"
                                          :value="old('titulo', $dono->titulo)" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="objeto" value="Objeto de execução *" />
                            <textarea name="objeto" maxlength="1000" id="objeto" rows="2" required class="{{ $campo }}">{{ old('objeto', $dono->objeto) }}</textarea>
                            <x-input-error :messages="$errors->get('objeto')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="publico_alvo" value="Público alvo" />
                            <textarea name="publico_alvo" maxlength="1000" id="publico_alvo" rows="2" class="{{ $campo }}">{{ old('publico_alvo', $dono->publico_alvo) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-sm font-medium text-gray-700">Duração execução</span>
                            <div class="grid sm:grid-cols-3 gap-3 mt-1">
                                <div>
                                    <label for="vigencia_dias" class="block text-xs text-gray-500">Dias corridos</label>
                                    <x-text-input id="vigencia_dias" name="vigencia_dias" type="number" min="1" max="3650"
                                                  :value="old('vigencia_dias', $dono->vigencia_dias)" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <label for="data_inicio_prevista" class="block text-xs text-gray-500">Início</label>
                                    <x-text-input id="data_inicio_prevista" name="data_inicio_prevista" type="date"
                                                  :value="old('data_inicio_prevista', $dono->data_inicio_prevista?->format('Y-m-d'))"
                                                  class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <label for="data_fim_prevista" class="block text-xs text-gray-500">Fim</label>
                                    <x-text-input id="data_fim_prevista" name="data_fim_prevista" type="date"
                                                  :value="old('data_fim_prevista', $dono->data_fim_prevista?->format('Y-m-d'))"
                                                  class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('data_fim_prevista')" class="mt-1" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="valor_solicitado" value="Valor pleiteado (R$) *" />
                            <x-input-dinheiro name="valor_solicitado" :value="$dono->valor_solicitado" required class="mt-1" />
                            <x-input-error :messages="$errors->get('valor_solicitado')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="descricao_realidade" value="3 – Descrição da realidade (por que o projeto deve ser implementado?)" />
                    <textarea name="descricao_realidade" maxlength="1000" id="descricao_realidade" rows="4" class="{{ $campo }}">{{ old('descricao_realidade', $dono->descricao_realidade) }}</textarea>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-800">4 – Objetivos</h3>
                    <p class="text-xs text-gray-400 mb-2">Apresentar de forma clara e objetiva o que se pretende alcançar.</p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="objetivos" value="Geral" />
                            <textarea name="objetivos" maxlength="1000" id="objetivos" rows="3" class="{{ $campo }}">{{ old('objetivos', $dono->objetivos) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="objetivos_especificos" value="Específicos" />
                            <textarea name="objetivos_especificos" maxlength="1000" id="objetivos_especificos" rows="3" class="{{ $campo }}">{{ old('objetivos_especificos', $dono->objetivos_especificos) }}</textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="metodologia" value="5 – Metodologia" />
                    <p class="text-xs text-gray-400">
                        Como o projeto vai alcançar seus objetivos? Descrever as estratégias e técnicas que serão empregadas.
                    </p>
                    <textarea name="metodologia" maxlength="1000" id="metodologia" rows="4" class="{{ $campo }}">{{ old('metodologia', $dono->metodologia) }}</textarea>
                </div>

                <div>
                    <x-input-label for="justificativa" value="6 – Diagnóstico/Justificativa" />
                    <p class="text-xs text-gray-400">
                        Por que se propõe o projeto diante do diagnóstico da realidade, e sua importância para os
                        beneficiários do projeto, devendo ser demonstrado o nexo entre essa realidade e a atividade e
                        metas a serem atingidas.
                    </p>
                    <textarea name="justificativa" maxlength="1000" id="justificativa" rows="4" class="{{ $campo }}">{{ old('justificativa', $dono->justificativa) }}</textarea>
                </div>

                <button class="btn btn-primary">Salvar plano</button>
            </form>
        @else
            @include('plano._dados-leitura', ['dono' => $dono])
        @endif
    </div>

    @if($mostrarMetas)
    {{-- 7. Metas, indicadores e resultados — com as atividades de cada meta --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">7 – Metas, indicadores e resultados</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            As atividades de cada meta, com o período e o valor estimado, formam o item 10 — Cronograma de execução
            física e financeira.
        </p>

        <div class="space-y-4">
            @forelse($dono->metas as $meta)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">Meta {{ $meta->numero }} — {{ $meta->descricao }}</p>
                            <dl class="mt-1 grid sm:grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600">
                                @foreach([
                                    'Objetivo específico'       => $meta->objetivo_especifico,
                                    'Indicadores qualitativos'  => $meta->indicador,
                                    'Indicadores quantitativos' => $meta->meta_quantitativa,
                                    'Resultados esperados'      => $meta->resultados_esperados,
                                    'Meios de verificação'      => $meta->meios_verificacao,
                                ] as $rotulo => $valor)
                                    @if($valor)
                                        <div><dt class="text-gray-400 inline">{{ $rotulo }}:</dt> <dd class="inline">{{ $valor }}</dd></div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                        @if($podeEditar)
                            <form action="{{ route($rota . '.metas.destroy', $id + ['meta' => $meta->id]) }}" method="POST"
                                  data-confirm="Remover a meta {{ $meta->numero }}, suas atividades e suas parcelas de desembolso?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-gray-400 hover:text-red-700 transition shrink-0">Remover</button>
                            </form>
                        @endif
                    </div>

                    <p class="mt-3 text-xs font-medium text-gray-500">Atividades</p>
                    <ul class="mt-1 space-y-1">
                        @forelse($meta->etapas as $atividade)
                            <li class="text-xs text-gray-600 flex items-start justify-between gap-2 border-l-2 border-gray-200 pl-3">
                                <span>{{ $atividade->numero }}. {{ $atividade->descricao }}
                                    <span class="text-gray-400">
                                        · {{ $data($atividade->data_inicio) }} a {{ $data($atividade->data_fim) }}
                                        @if($atividade->valor !== null) · {{ $moeda($atividade->valor) }} @endif
                                    </span>
                                </span>
                                @if($podeEditar)
                                    <form action="{{ route($rota . '.etapas.destroy', $id + ['meta' => $meta->id, 'etapa' => $atividade->id]) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-gray-400 hover:text-red-700 transition" aria-label="Remover atividade">&times;</button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            {{-- Meta antiga: as atividades eram um texto só. --}}
                            <li class="text-xs text-gray-400">{{ $meta->atividades ?: 'Nenhuma atividade lançada.' }}</li>
                        @endforelse
                    </ul>

                    @if($podeEditar)
                        <form action="{{ route($rota . '.etapas.store', $id + ['meta' => $meta->id]) }}" method="POST"
                              class="mt-3 grid sm:grid-cols-6 gap-2 items-end">
                            @csrf
                            <div class="sm:col-span-6">
                                <label class="block text-xs text-gray-500">Nova atividade desta meta *</label>
                                <input type="text" name="descricao" required maxlength="1000" placeholder="Tarefa que será executada para alcançar a meta"
                                       class="{{ $campo }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500">Início</label>
                                <input type="date" name="data_inicio" class="{{ $campo }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500">Fim</label>
                                <input type="date" name="data_fim" class="{{ $campo }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500">Estimado (R$)</label>
                                <x-input-dinheiro name="valor" :id="'etapa_valor_' . $meta->id" class="mt-1" />
                            </div>
                            <div class="sm:col-span-6">
                                <button class="btn btn-secondary btn-sm">Adicionar atividade</button>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Nenhuma meta cadastrada.</p>
            @endforelse
        </div>

        @if($podeEditar)
            <form action="{{ route($rota . '.metas.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 grid sm:grid-cols-2 gap-3">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="meta_objetivo" value="Objetivo específico (conforme já descrito no item 4)" />
                    <input type="text" name="objetivo_especifico" maxlength="1000" id="meta_objetivo" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="meta_descricao" value="Nova meta *" />
                    <p class="text-xs text-gray-400">Marco concreto, expressando quantidades e/ou qualidades.</p>
                    <input type="text" name="descricao" id="meta_descricao" required maxlength="1000" class="{{ $campo }}">
                    <x-input-error :messages="$errors->get('descricao')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="meta_indicador" value="Indicadores qualitativos" />
                    <input type="text" name="indicador" id="meta_indicador" maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="meta_quantitativa" value="Indicadores quantitativos" />
                    <input type="text" name="meta_quantitativa" id="meta_quantitativa" maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="meta_resultados" value="Resultados esperados" />
                    <input type="text" name="resultados_esperados" maxlength="1000" id="meta_resultados" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="meta_meios" value="Meios de verificação (comprovantes da realização)" />
                    <input type="text" name="meios_verificacao" maxlength="1000" id="meta_meios" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-secondary btn-sm">Adicionar meta</button>
                </div>
            </form>
        @endif
    </div>
    @endif

    {{-- 8. Contrapartida não financeira --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">8 – Descrição da contrapartida não financeira, quando houver</h2>

        <table class="mt-4 min-w-full text-sm">
            <thead class="text-xs text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="text-left py-2 pr-3 w-28">Contrapartida Nº</th>
                    <th class="text-left py-2 pr-3">Descrição</th>
                    <th class="text-left py-2 pr-3">Quantidade</th>
                    @if($podeEditar)<th></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dono->contrapartidas as $cp)
                    <tr>
                        <td class="py-2 pr-3 text-gray-500">{{ $cp->numero }}</td>
                        <td class="py-2 pr-3 text-gray-900">{{ $cp->descricao }}</td>
                        <td class="py-2 pr-3 text-gray-600">{{ $cp->quantidade ?: '—' }}</td>
                        @if($podeEditar)
                            <td class="py-2 text-right">
                                <form action="{{ route($rota . '.contrapartidas.destroy', $id + ['contrapartida' => $cp->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-sm text-gray-400">Nenhuma contrapartida não financeira.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($podeEditar)
            <form action="{{ route($rota . '.contrapartidas.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 grid sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="cp_descricao" value="Descrição *" />
                    <input type="text" name="descricao" id="cp_descricao" required maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="cp_quantidade" value="Quantidade" />
                    <input type="text" name="quantidade" id="cp_quantidade" maxlength="100" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-3">
                    <button class="btn btn-secondary btn-sm">Adicionar contrapartida</button>
                </div>
            </form>
        @endif
    </div>

    {{-- 9. Natureza da despesa — campo do ordenador; soma do plano de aplicação --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">9 – Descrição da natureza da despesa</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            Campo reservado ao ordenador de despesa – PMSGRA. Os valores são a soma do plano de aplicação (item 13)
            por natureza.
        </p>
        <table class="min-w-full text-sm">
            <thead class="text-xs text-gray-500 border-b border-gray-200">
                <tr><th class="text-left py-2">Natureza</th><th class="text-right py-2">Valor</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(\App\Models\Despesa::NATUREZAS as $chave => $rotulo)
                    <tr>
                        <td class="py-1.5 text-gray-700">{{ $rotulo }}</td>
                        <td class="py-1.5 text-right {{ $naturezas[$chave] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ $moeda($naturezas[$chave]) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-gray-200">
                <tr>
                    <td class="py-2 font-semibold text-gray-800">TOTAL</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ $moeda(array_sum($naturezas)) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- 10. Cronograma de execução física e financeira — das atividades do item 7 --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">10 – Cronograma de execução física e financeira</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            Montado a partir das atividades de cada meta (item 7). As metas/ações aqui descritas deverão estar
            relacionadas ao Plano de Aplicação dos Recursos.
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-2 pr-3">Meta nº</th>
                        <th class="text-left py-2 pr-3">Atividades</th>
                        <th class="text-left py-2 pr-3">Início</th>
                        <th class="text-left py-2 pr-3">Fim</th>
                        <th class="text-right py-2">Estimado (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dono->metas as $meta)
                        @forelse($meta->etapas as $atividade)
                            <tr>
                                <td class="py-2 pr-3 text-gray-500">{{ $meta->numero }}</td>
                                <td class="py-2 pr-3 text-gray-900">{{ $atividade->descricao }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $data($atividade->data_inicio) }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $data($atividade->data_fim) }}</td>
                                <td class="py-2 text-right text-gray-900">{{ $atividade->valor !== null ? $moeda($atividade->valor) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-2 pr-3 text-gray-500">{{ $meta->numero }}</td>
                                <td class="py-2 pr-3 text-gray-400">{{ $meta->atividades ?: 'Sem atividades lançadas.' }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $data($meta->data_inicio) }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $data($meta->data_fim) }}</td>
                                <td class="py-2 text-right text-gray-900">{{ (float) $meta->valor > 0 ? $moeda($meta->valor) : '—' }}</td>
                            </tr>
                        @endforelse
                    @empty
                        <tr><td colspan="5" class="py-3 text-sm text-gray-400">Lance as metas e as atividades no item 7.</td></tr>
                    @endforelse
                </tbody>
                @if($dono->metas->isNotEmpty())
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="py-2 pr-3 text-right font-semibold text-gray-800">TOTAL</td>
                            <td class="py-2 text-right font-semibold text-gray-900">{{ $moeda($dono->totalDasMetas()) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- 11. Cronograma de desembolso — metas × parcelas --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">11 – Cronograma de desembolso</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">O valor de cada parcela, por meta.</p>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-2 pr-2">Meta nº</th>
                        @for($n = 1; $n <= $numParcelas; $n++)
                            <th class="text-right py-2 px-2 whitespace-nowrap">{{ \App\Models\PlanoDesembolso::rotuloParcela($n) }}</th>
                        @endfor
                        <th class="text-right py-2 px-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $linhas = $dono->metas->map(fn ($m) => ['rotulo' => $m->numero, 'parcelas' => $parcelas->where('meta_id', $m->id)]);
                        if ($semMeta->isNotEmpty()) {
                            $linhas->push(['rotulo' => 'Sem meta', 'parcelas' => $semMeta]);
                        }
                    @endphp
                    @forelse($linhas as $linha)
                        <tr>
                            <td class="py-2 pr-2 text-gray-600">{{ $linha['rotulo'] }}</td>
                            @for($n = 1; $n <= $numParcelas; $n++)
                                @php $celula = $linha['parcelas']->where('parcela', $n); @endphp
                                <td class="py-2 px-2 text-right whitespace-nowrap {{ $celula->isNotEmpty() ? 'text-gray-900' : 'text-gray-300' }}">
                                    {{ $celula->isNotEmpty() ? number_format((float) $celula->sum('valor'), 2, ',', '.') : '—' }}
                                    @if($podeEditar)
                                        @foreach($celula as $p)
                                            <form action="{{ route($rota . '.desembolsos.destroy', $id + ['desembolso' => $p->id]) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="text-gray-400 hover:text-red-700 transition" aria-label="Remover parcela">&times;</button>
                                            </form>
                                        @endforeach
                                    @endif
                                </td>
                            @endfor
                            <td class="py-2 px-2 text-right font-medium text-gray-900 whitespace-nowrap">{{ number_format((float) $linha['parcelas']->sum('valor'), 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $numParcelas + 2 }}" class="py-3 text-sm text-gray-400">Lance as metas no item 7 para montar o cronograma.</td></tr>
                    @endforelse
                </tbody>
                @if($parcelas->isNotEmpty())
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td class="py-2 pr-2 font-semibold text-gray-800">Total</td>
                            @for($n = 1; $n <= $numParcelas; $n++)
                                <td class="py-2 px-2 text-right text-gray-700 whitespace-nowrap">{{ number_format((float) $parcelas->where('parcela', $n)->sum('valor'), 2, ',', '.') }}</td>
                            @endfor
                            <td class="py-2 px-2 text-right font-semibold text-gray-900 whitespace-nowrap">{{ number_format($dono->totalDesembolso(), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($podeEditar && $dono->metas->isNotEmpty())
            <form action="{{ route($rota . '.desembolsos.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label for="desembolso_meta" value="Meta nº *" />
                    <select name="meta_id" id="desembolso_meta" required
                            class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach($dono->metas as $meta)
                            <option value="{{ $meta->id }}">{{ $meta->numero }} — {{ \Illuminate\Support\Str::limit($meta->descricao, 40) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('meta_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="desembolso_parcela" value="Parcela *" />
                    <input type="number" name="parcela" id="desembolso_parcela" min="1" max="120" required
                           value="{{ min(120, (int) $parcelas->max('parcela') + 1) }}"
                           class="mt-1 w-24 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="desembolso_valor" value="Valor (R$) *" />
                    <div class="mt-1 w-40"><x-input-dinheiro name="valor" id="desembolso_valor" required /></div>
                </div>
                <button class="btn btn-secondary btn-sm">Adicionar parcela</button>
            </form>
        @endif
    </div>

    {{-- 12. Equipe --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">12 – Relação da equipe contratada ou da equipe própria da OSC a serviço da parceria</h2>

        <div class="overflow-x-auto">
            <table class="mt-4 min-w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-2 pr-3">Cargo/função</th>
                        <th class="text-left py-2 pr-3">Formação profissional</th>
                        <th class="text-left py-2 pr-3">Carga horária mensal</th>
                        <th class="text-left py-2 pr-3">Natureza do vínculo</th>
                        @if($podeEditar)<th></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dono->equipe as $membro)
                        <tr>
                            <td class="py-2 pr-3 text-gray-900">{{ $membro->cargo_funcao }}</td>
                            <td class="py-2 pr-3 text-gray-600">{{ $membro->formacao ?: '—' }}</td>
                            <td class="py-2 pr-3 text-gray-600">{{ $membro->carga_horaria_mensal ?: '—' }}</td>
                            <td class="py-2 pr-3 text-gray-600">{{ $membro->vinculoLabel() }}</td>
                            @if($podeEditar)
                                <td class="py-2 text-right">
                                    <form action="{{ route($rota . '.equipe.destroy', $id + ['membro' => $membro->id]) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-sm text-gray-400">Nenhum integrante lançado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($podeEditar)
            <form action="{{ route($rota . '.equipe.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 grid sm:grid-cols-2 gap-3">
                @csrf
                <div>
                    <x-input-label for="equipe_cargo" value="Cargo/função *" />
                    <input type="text" name="cargo_funcao" id="equipe_cargo" required maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="equipe_formacao" value="Formação profissional" />
                    <input type="text" name="formacao" id="equipe_formacao" maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="equipe_carga" value="Carga horária mensal" />
                    <input type="text" name="carga_horaria_mensal" id="equipe_carga" maxlength="50" placeholder="ex.: 160 horas" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="equipe_vinculo" value="Natureza do vínculo *" />
                    <select name="vinculo" id="equipe_vinculo" required class="{{ $campo }}">
                        @foreach(\App\Models\PlanoEquipe::VINCULOS as $k => $rotulo)
                            <option value="{{ $k }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-secondary btn-sm">Adicionar à equipe</button>
                </div>
            </form>
        @endif
    </div>

    {{-- 13. Plano de aplicação dos recursos (planilha anexa) --}}
    <div class="{{ $cartao }}">
        <h2 class="text-base font-semibold text-gray-800">13 – Plano de aplicação dos recursos (Planilha anexa)</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            A natureza de cada item alimenta o item 9, e é a mesma usada na execução e na prestação de contas.
        </p>

        @if($podeEditar)
            <form action="{{ route($rota . '.aplicacao', $id) }}" method="POST" class="mb-5 space-y-2">
                @csrf @method('PUT')
                <x-input-label for="plano_aplicacao" value="Descrição do plano de aplicação" />
                <textarea name="plano_aplicacao" id="plano_aplicacao" rows="4" maxlength="1000" class="{{ $campo }}">{{ old('plano_aplicacao', $dono->plano_aplicacao) }}</textarea>
                <x-input-error :messages="$errors->get('plano_aplicacao')" />
                <button type="submit" class="btn btn-secondary btn-sm">Salvar descrição</button>
            </form>
        @elseif(filled($dono->plano_aplicacao))
            <p class="mb-5 text-sm text-gray-700 whitespace-pre-line">{{ $dono->plano_aplicacao }}</p>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-2 pr-3">#</th>
                        <th class="text-left py-2 pr-3">Descrição</th>
                        <th class="text-left py-2 pr-3">Natureza da despesa</th>
                        <th class="text-left py-2 pr-3">Unid.</th>
                        <th class="text-right py-2 pr-3">Qtd.</th>
                        <th class="text-right py-2 pr-3">Valor unit.</th>
                        <th class="text-right py-2 pr-3">Valor total</th>
                        <th class="text-left py-2 pr-3">Atividades vinculadas</th>
                        @if($podeEditar)<th></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dono->planoItens as $item)
                        <tr>
                            <td class="py-2 pr-3 text-gray-400">{{ $item->numero }}</td>
                            <td class="py-2 pr-3 text-gray-900">{{ $item->descricao }}</td>
                            <td class="py-2 pr-3 text-gray-600">{{ $item->tipoLabel() }}</td>
                            <td class="py-2 pr-3 text-gray-600">{{ $item->unidade ?: '—' }}</td>
                            <td class="py-2 pr-3 text-right text-gray-600">{{ rtrim(rtrim(number_format((float) $item->quantidade, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="py-2 pr-3 text-right text-gray-600">{{ $moeda($item->valor_unitario) }}</td>
                            <td class="py-2 pr-3 text-right font-medium text-gray-900">{{ $moeda($item->total()) }}</td>
                            <td class="py-2 pr-3 text-gray-500">{{ $item->atividades_vinculadas ?: '—' }}</td>
                            @if($podeEditar)
                                <td class="py-2 text-right">
                                    <form action="{{ route($rota . '.itens.destroy', $id + ['item' => $item->id]) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-3 text-sm text-gray-400">Nenhum item lançado.</td></tr>
                    @endforelse
                </tbody>
                @if($dono->planoItens->isNotEmpty())
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td colspan="6" class="py-2 pr-3 text-right text-xs text-gray-500">Total do plano de aplicação</td>
                            <td class="py-2 pr-3 text-right font-semibold text-gray-900">{{ $moeda($dono->totalPlanoAplicacao()) }}</td>
                            <td colspan="{{ $podeEditar ? 2 : 1 }}"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($podeEditar)
            <form action="{{ route($rota . '.itens.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 grid sm:grid-cols-3 gap-3">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="item_descricao" value="Descrição *" />
                    <input type="text" name="descricao" id="item_descricao" required maxlength="1000" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="tipo_despesa" value="Natureza da despesa *" />
                    <select name="tipo_despesa" id="tipo_despesa" required class="{{ $campo }}">
                        @foreach(\App\Models\Despesa::NATUREZAS as $k => $rotulo)
                            <option value="{{ $k }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="item_unidade" value="Unidade" />
                    <input type="text" name="unidade" id="item_unidade" maxlength="30" placeholder="mês, un., serviço" class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="item_quantidade" value="Quantidade *" />
                    <input type="number" name="quantidade" id="item_quantidade" step="0.01" min="0.01" value="1" required class="{{ $campo }}">
                </div>
                <div>
                    <x-input-label for="item_valor" value="Valor unitário (R$) *" />
                    <x-input-dinheiro name="valor_unitario" id="item_valor" required class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="item_atividades" value="Atividades vinculadas" />
                    <input type="text" name="atividades_vinculadas" id="item_atividades" maxlength="1000" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-3">
                    <button class="btn btn-secondary btn-sm">Adicionar item</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Conferência: o que não fecha entre as tabelas do próprio plano --}}
    @if($avisos)
        <div class="bg-accent-50 border border-accent-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-accent-800">Confira antes de apresentar:</p>
            <ul class="mt-1 text-sm text-accent-700 list-disc list-inside space-y-0.5">
                @foreach($avisos as $aviso)<li>{{ $aviso }}</li>@endforeach
            </ul>
            <p class="mt-2 text-xs text-accent-600">
                São avisos, não impedimentos — mas a análise técnica vai reparar.
            </p>
        </div>
    @endif
</div>
