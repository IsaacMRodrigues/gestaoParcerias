{{--
    Plano de Trabalho — o formulário do modelo 3.1.

    Serve a manifestação de interesse e a proposta de chamamento público: é o
    mesmo plano, e uma tela só evita que os dois caminhos divirjam. Quem chama
    passa o dono e o prefixo das rotas; $podeEditar decide entre editar e ler.

    @param $dono         Proposta|ManifestacaoInteresse
    @param $rota         prefixo nomeado, ex.: 'portal.proposta.plano'
    @param $podeEditar   bool
    @param $mostrarMetas bool — falso nas telas do município, que já têm o
                         próprio bloco de metas, com edição na negociação
--}}
@php
    $mostrarMetas = $mostrarMetas ?? true;
    $id     = ['id' => $dono->id];
    $fontes = $dono->quadroDeFontes();
    $avisos = $dono->divergenciasDoPlano();
    $moeda  = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp

<div class="space-y-6">

    {{-- 1. Dados do plano --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Plano de trabalho</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            O que será feito, para quem, onde e com quanto. É este plano que a análise técnica examina
            e que, aprovado, passa a ser o compromisso da parceria.
        </p>

        @if($podeEditar)
            <form action="{{ route($rota . '.atualizar', $id) }}" method="POST"
                  x-data="{ rede: {{ old('atuacao_rede', $dono->atuacao_rede) ? 'true' : 'false' }} }"
                  class="grid sm:grid-cols-2 gap-4">
                @csrf @method('PUT')

                <div class="sm:col-span-2">
                    <x-input-label for="titulo" value="Título *" />
                    <x-text-input id="titulo" name="titulo" type="text" required maxlength="255"
                                  :value="old('titulo', $dono->titulo)" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="objeto" value="Objeto *" />
                    <textarea name="objeto" id="objeto" rows="2" required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('objeto', $dono->objeto) }}</textarea>
                    <x-input-error :messages="$errors->get('objeto')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="descricao_realidade" value="Descrição da realidade que será objeto da parceria" />
                    <textarea name="descricao_realidade" id="descricao_realidade" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('descricao_realidade', $dono->descricao_realidade) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="justificativa" value="Justificativa" />
                    <textarea name="justificativa" id="justificativa" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('justificativa', $dono->justificativa) }}</textarea>
                </div>

                <div>
                    <x-input-label for="publico_alvo" value="Público-alvo (tipo e quantidade)" />
                    <textarea name="publico_alvo" id="publico_alvo" rows="2"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('publico_alvo', $dono->publico_alvo) }}</textarea>
                </div>

                <div>
                    <x-input-label for="objetivos" value="Objetivos" />
                    <textarea name="objetivos" id="objetivos" rows="2"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('objetivos', $dono->objetivos) }}</textarea>
                </div>

                <div>
                    <x-input-label for="data_inicio_prevista" value="Data prevista para início" />
                    <x-text-input id="data_inicio_prevista" name="data_inicio_prevista" type="date"
                                  :value="old('data_inicio_prevista', $dono->data_inicio_prevista?->format('Y-m-d'))"
                                  class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="data_fim_prevista" value="Data prevista para término" />
                    <x-text-input id="data_fim_prevista" name="data_fim_prevista" type="date"
                                  :value="old('data_fim_prevista', $dono->data_fim_prevista?->format('Y-m-d'))"
                                  class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('data_fim_prevista')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="vigencia_dias" value="Proposta de vigência (dias corridos)" />
                    <x-text-input id="vigencia_dias" name="vigencia_dias" type="number" min="1" max="3650"
                                  :value="old('vigencia_dias', $dono->vigencia_dias)" class="mt-1 block w-full" />
                </div>

                <div class="grid grid-cols-3 gap-2 sm:col-span-2">
                    <div>
                        <x-input-label for="valor_solicitado" value="Valor solicitado (PMSGRA) *" />
                        <x-text-input id="valor_solicitado" name="valor_solicitado" type="number" step="0.01" min="0" required
                                      :value="old('valor_solicitado', $dono->valor_solicitado)" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('valor_solicitado')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="valor_proprio" value="Contrapartida da OSC" />
                        <x-text-input id="valor_proprio" name="valor_proprio" type="number" step="0.01" min="0"
                                      :value="old('valor_proprio', $dono->valor_proprio)" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="valor_outras_fontes" value="Outras fontes" />
                        <x-text-input id="valor_outras_fontes" name="valor_outras_fontes" type="number" step="0.01" min="0"
                                      :value="old('valor_outras_fontes', $dono->valor_outras_fontes)" class="mt-1 block w-full" />
                    </div>
                </div>

                {{-- Atuação em rede (art. 35-A): os campos da executante só
                     aparecem quando há rede, e somem do banco quando não há. --}}
                <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="atuacao_rede" value="0">
                        <input type="checkbox" name="atuacao_rede" value="1" x-model="rede"
                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Haverá atuação em rede
                    </label>

                    <div x-show="rede" x-cloak class="grid sm:grid-cols-2 gap-3 mt-3">
                        <div>
                            <x-input-label for="rede_cnpj" value="CNPJ da executante *" />
                            <x-text-input id="rede_cnpj" name="rede_cnpj" type="text" maxlength="18"
                                          :value="old('rede_cnpj', $dono->rede_cnpj)" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('rede_cnpj')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="rede_razao_social" value="Razão social *" />
                            <x-text-input id="rede_razao_social" name="rede_razao_social" type="text" maxlength="255"
                                          :value="old('rede_razao_social', $dono->rede_razao_social)" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('rede_razao_social')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="rede_municipio" value="Município" />
                            <x-text-input id="rede_municipio" name="rede_municipio" type="text" maxlength="255"
                                          :value="old('rede_municipio', $dono->rede_municipio)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="rede_data_termo" value="Data de assinatura do Termo de Atuação em Rede" />
                            <x-text-input id="rede_data_termo" name="rede_data_termo" type="date"
                                          :value="old('rede_data_termo', $dono->rede_data_termo?->format('Y-m-d'))"
                                          class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <button class="btn btn-primary">Salvar plano</button>
                </div>
            </form>
        @else
            @include('plano._dados-leitura', ['dono' => $dono])
        @endif
    </div>

    {{-- 2. Endereços de execução --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Endereços de execução</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            Onde a atividade, a obra, o evento, o serviço ou a entrega do bem acontecem. Pode ser mais de um.
        </p>

        <ul class="divide-y divide-gray-100">
            @forelse($dono->enderecosExecucao as $end)
                <li class="py-2 flex items-start justify-between gap-3">
                    <span class="min-w-0 text-sm">
                        <span class="text-gray-900">{{ $end->endereco }}</span>
                        @if($end->descricao)<span class="block text-xs text-gray-400">{{ $end->descricao }}</span>@endif
                    </span>
                    @if($podeEditar)
                        <form action="{{ route($rota . '.enderecos.destroy', $id + ['endereco' => $end->id]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="text-xs text-gray-400 hover:text-red-700 transition shrink-0">Remover</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-2 text-sm text-gray-400">Nenhum endereço informado.</li>
            @endforelse
        </ul>

        @if($podeEditar)
            <form action="{{ route($rota . '.enderecos.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[16rem]">
                    <x-input-label for="endereco" value="Endereço *" />
                    <x-text-input id="endereco" name="endereco" type="text" required maxlength="255"
                                  placeholder="Rua, número, bairro, município" class="mt-1 block w-full" />
                </div>
                <div class="min-w-[12rem]">
                    <x-input-label for="endereco_descricao" value="O que acontece ali" />
                    <input type="text" name="descricao" id="endereco_descricao" maxlength="255"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <button class="btn btn-secondary btn-sm">Adicionar endereço</button>
            </form>
        @endif
    </div>

    @if($mostrarMetas)
    {{-- 3. Cronograma de execução (metas e etapas) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Cronograma de execução</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            As metas, com o que será feito, como se verifica e o que se espera alcançar.
        </p>

        <div class="space-y-4">
            @forelse($dono->metas as $meta)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">
                                Meta {{ $meta->numero }} — {{ $meta->descricao }}
                                @if((float) $meta->valor > 0)
                                    <span class="font-normal text-gray-500">· {{ $moeda($meta->valor) }}</span>
                                @endif
                            </p>
                            <dl class="mt-1 grid sm:grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600">
                                @foreach([
                                    'Atividades'            => $meta->atividades,
                                    'Indicadores'           => $meta->indicador,
                                    'Meios de verificação'  => $meta->meios_verificacao,
                                    'Resultados esperados'  => $meta->resultados_esperados,
                                    'Meta quantitativa'     => $meta->meta_quantitativa,
                                ] as $rotulo => $valor)
                                    @if($valor)
                                        <div><dt class="text-gray-400 inline">{{ $rotulo }}:</dt> <dd class="inline">{{ $valor }}</dd></div>
                                    @endif
                                @endforeach
                                @if($meta->data_inicio || $meta->data_fim)
                                    <div>
                                        <dt class="text-gray-400 inline">Prazo:</dt>
                                        <dd class="inline">{{ $meta->data_inicio?->format('d/m/Y') ?? '—' }} a {{ $meta->data_fim?->format('d/m/Y') ?? '—' }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                        @if($podeEditar)
                            <form action="{{ route($rota . '.metas.destroy', $id + ['meta' => $meta->id]) }}" method="POST"
                                  data-confirm="Remover a meta {{ $meta->numero }} e suas etapas?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-gray-400 hover:text-red-700 transition shrink-0">Remover</button>
                            </form>
                        @endif
                    </div>

                    <ul class="mt-3 space-y-1">
                        @foreach($meta->etapas as $etapa)
                            <li class="text-xs text-gray-600 flex items-start justify-between gap-2 border-l-2 border-gray-200 pl-3">
                                <span>{{ $etapa->numero }}. {{ $etapa->descricao }}
                                    @if($etapa->responsavel)<span class="text-gray-400"> · {{ $etapa->responsavel }}</span>@endif
                                </span>
                                @if($podeEditar)
                                    <form action="{{ route($rota . '.etapas.destroy', $id + ['meta' => $meta->id, 'etapa' => $etapa->id]) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-gray-400 hover:text-red-700 transition">&times;</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if($podeEditar)
                        <form action="{{ route($rota . '.etapas.store', $id + ['meta' => $meta->id]) }}" method="POST"
                              class="mt-3 flex flex-wrap gap-2">
                            @csrf
                            <input type="text" name="descricao" required maxlength="255" placeholder="Nova etapa desta meta"
                                   class="flex-1 min-w-[14rem] border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            <input type="text" name="responsavel" maxlength="255" placeholder="Responsável"
                                   class="w-40 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            <button class="btn btn-secondary btn-sm">Adicionar etapa</button>
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
                    <x-input-label for="meta_descricao" value="Nova meta — descrição *" />
                    <input type="text" name="descricao" id="meta_descricao" required maxlength="255"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                    <x-input-error :messages="$errors->get('descricao')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="meta_atividades" value="Atividades" />
                    <input type="text" name="atividades" id="meta_atividades"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_indicador" value="Indicadores" />
                    <input type="text" name="indicador" id="meta_indicador" maxlength="255"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_meios" value="Documentos e meios de verificação" />
                    <input type="text" name="meios_verificacao" id="meta_meios"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_resultados" value="Resultados esperados" />
                    <input type="text" name="resultados_esperados" id="meta_resultados"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_valor" value="Valor (R$)" />
                    <input type="number" name="valor" id="meta_valor" step="0.01" min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_inicio" value="Data de início" />
                    <input type="date" name="data_inicio" id="meta_inicio"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="meta_fim" value="Data de término" />
                    <input type="date" name="data_fim" id="meta_fim"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-secondary btn-sm">Adicionar meta</button>
                </div>
            </form>
        @endif
    </div>
    @endif

    {{-- 4. Plano de aplicação (I — Demonstrativo de recursos) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Plano de aplicação dos recursos</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            I — Demonstrativo de recursos. O tipo de despesa é o mesmo usado na execução e na
            prestação de contas, para que o aprovado e o gasto sejam comparáveis.
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-2 pr-3">#</th>
                        <th class="text-left py-2 pr-3">Descrição</th>
                        <th class="text-left py-2 pr-3">Tipo de despesa</th>
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
                    <input type="text" name="descricao" id="item_descricao" required maxlength="255"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="tipo_despesa" value="Tipo de despesa *" />
                    <select name="tipo_despesa" id="tipo_despesa" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(\App\Models\Despesa::NATUREZAS as $k => $rotulo)
                            <option value="{{ $k }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="item_unidade" value="Unidade" />
                    <input type="text" name="unidade" id="item_unidade" maxlength="30" placeholder="mês, un., serviço"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="item_quantidade" value="Quantidade *" />
                    <input type="number" name="quantidade" id="item_quantidade" step="0.01" min="0.01" value="1" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="item_valor" value="Valor unitário (R$) *" />
                    <input type="number" name="valor_unitario" id="item_valor" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="item_atividades" value="Atividades vinculadas" />
                    <input type="text" name="atividades_vinculadas" id="item_atividades" maxlength="255"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="sm:col-span-3">
                    <button class="btn btn-secondary btn-sm">Adicionar item</button>
                </div>
            </form>
        @endif
    </div>

    {{-- 5. II — Valor total da proposta / contrapartida --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Valor total da proposta e contrapartida</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">II — Calculado a partir dos valores do plano.</p>

        <table class="min-w-full text-sm">
            <thead class="text-xs text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="text-left py-2">Especificação</th>
                    <th class="text-right py-2">Valor</th>
                    <th class="text-right py-2 w-24">% do total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($fontes['linhas'] as $linha)
                    <tr>
                        <td class="py-2 text-gray-700">{{ $linha['nome'] }}</td>
                        <td class="py-2 text-right text-gray-900">{{ $moeda($linha['valor']) }}</td>
                        <td class="py-2 text-right text-gray-500">{{ number_format($linha['percentual'], 2, ',', '.') }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-gray-200">
                <tr>
                    <td class="py-2 font-semibold text-gray-800">Total</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ $moeda($fontes['total']) }}</td>
                    <td class="py-2 text-right text-gray-500">{{ $fontes['total'] > 0 ? '100,00%' : '0,00%' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- 6. Cronograma de desembolso --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800">Cronograma de desembolso</h2>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">
            Quando o recurso do município precisa entrar na conta da parceria. Uma linha por mês.
        </p>

        <table class="min-w-full text-sm">
            <thead class="text-xs text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="text-left py-2">Ano</th>
                    <th class="text-left py-2">Mês</th>
                    <th class="text-right py-2">Valor</th>
                    @if($podeEditar)<th></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dono->desembolsos as $parcela)
                    <tr>
                        <td class="py-2 text-gray-700">{{ $parcela->ano }}</td>
                        <td class="py-2 text-gray-700">{{ $parcela->mesLabel() }}</td>
                        <td class="py-2 text-right text-gray-900">{{ $moeda($parcela->valor) }}</td>
                        @if($podeEditar)
                            <td class="py-2 text-right">
                                <form action="{{ route($rota . '.desembolsos.destroy', $id + ['desembolso' => $parcela->id]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-sm text-gray-400">Nenhuma parcela lançada.</td></tr>
                @endforelse
            </tbody>
            @if($dono->desembolsos->isNotEmpty())
                <tfoot class="border-t border-gray-200">
                    <tr>
                        <td colspan="2" class="py-2 text-right text-xs text-gray-500">Total</td>
                        <td class="py-2 text-right font-semibold text-gray-900">{{ $moeda($dono->totalDesembolso()) }}</td>
                        @if($podeEditar)<td></td>@endif
                    </tr>
                </tfoot>
            @endif
        </table>

        @if($podeEditar)
            <form action="{{ route($rota . '.desembolsos.store', $id) }}" method="POST"
                  class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label for="desembolso_ano" value="Ano *" />
                    <input type="number" name="ano" id="desembolso_ano" min="2020" max="2100" required
                           value="{{ $dono->data_inicio_prevista?->year ?? now()->year }}"
                           class="mt-1 w-28 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <x-input-label for="desembolso_mes" value="Mês *" />
                    <select name="mes" id="desembolso_mes" required
                            class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(\App\Models\PlanoDesembolso::MESES as $n => $nome)
                            <option value="{{ $n }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="desembolso_valor" value="Valor (R$) *" />
                    <input type="number" name="valor" id="desembolso_valor" step="0.01" min="0.01" required
                           class="mt-1 w-40 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <button class="btn btn-secondary btn-sm">Adicionar parcela</button>
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
