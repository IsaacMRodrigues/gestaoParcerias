<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.show', $processo) }}" class="hover:underline">Processo {{ $processo->numero }}</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Termo de Referência</h2>
    </x-slot>

    @php $tr = $processo->termoReferencia; @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if($tr->assinado())
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    Termo assinado por {{ $tr->assinante->name }} em {{ $tr->assinado_em->format('d/m/Y H:i') }}.
                </div>
            @endif

            <form action="{{ route('processos.termo.update', $processo) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')

                {{-- 2.1 Descrição da realidade --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">2.1 Descrição da Realidade</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Descreva a situação que demanda a atuação da administração pública e os resultados esperados para a população.
                    </p>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="problema_identificado" value="Problema identificado" />
                                <x-text-input id="problema_identificado" name="problema_identificado" type="text"
                                              class="mt-1 block w-full" value="{{ old('problema_identificado', $tr->problema_identificado) }}" />
                            </div>
                            <div>
                                <x-input-label for="publico_alvo" value="Público-alvo" />
                                <x-text-input id="publico_alvo" name="publico_alvo" type="text"
                                              class="mt-1 block w-full" value="{{ old('publico_alvo', $tr->publico_alvo) }}" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="qtd_beneficiarios" value="Quantidade estimada de beneficiários" />
                                <x-text-input id="qtd_beneficiarios" name="qtd_beneficiarios" type="number" min="0"
                                              class="mt-1 block w-full" value="{{ old('qtd_beneficiarios', $tr->qtd_beneficiarios) }}" />
                            </div>
                            <div>
                                <x-input-label for="area_tematica" value="Área temática" />
                                <select id="area_tematica" name="area_tematica"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="">Selecione...</option>
                                    @foreach(\App\Models\Processo::AREAS_TEMATICAS as $key => $label)
                                        <option value="{{ $key }}" {{ old('area_tematica', $tr->area_tematica) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="justificativa_necessidade" value="Justificativa da necessidade pública" />
                            <textarea id="justificativa_necessidade" name="justificativa_necessidade" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('justificativa_necessidade', $tr->justificativa_necessidade) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="indicadores" value="Indicadores que demonstram a necessidade" />
                            <textarea id="indicadores" name="indicadores" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('indicadores', $tr->indicadores) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 2.2 Vinculação ao Planejamento --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">2.2 Vinculação ao Planejamento Governamental</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="programa_governo" value="Programa de Governo" />
                            <x-text-input id="programa_governo" name="programa_governo" type="text"
                                          class="mt-1 block w-full" value="{{ old('programa_governo', $tr->programa_governo) }}" />
                        </div>
                        <div>
                            <x-input-label for="acao_governamental" value="Ação Governamental" />
                            <x-text-input id="acao_governamental" name="acao_governamental" type="text"
                                          class="mt-1 block w-full" value="{{ old('acao_governamental', $tr->acao_governamental) }}" />
                        </div>
                        <div>
                            <x-input-label for="dotacao_orcamentaria" value="Dotação orçamentária" />
                            <x-text-input id="dotacao_orcamentaria" name="dotacao_orcamentaria" type="text"
                                          class="mt-1 block w-full" value="{{ old('dotacao_orcamentaria', $tr->dotacao_orcamentaria) }}" />
                        </div>
                    </div>
                    <p class="text-xs text-amber-600 mt-2">Validação manual: conferir se existe dotação orçamentária compatível.</p>
                </div>

                {{-- 2.3 Definição do Objeto --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">2.3 Definição do Objeto</h3>
                    <p class="text-xs text-gray-400 mb-4">O objeto deve ser específico, mensurável e compatível com a política pública da área.</p>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="objeto_resumido" value="Objeto resumido" />
                            <textarea id="objeto_resumido" name="objeto_resumido" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objeto_resumido', $tr->objeto_resumido) }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="vigencia_prevista" value="Vigência prevista" />
                                <x-text-input id="vigencia_prevista" name="vigencia_prevista" type="text"
                                              class="mt-1 block w-full" value="{{ old('vigencia_prevista', $tr->vigencia_prevista) }}"
                                              placeholder="Ex.: 12 meses" />
                            </div>
                            <div>
                                <x-input-label for="local_execucao" value="Local de execução" />
                                <x-text-input id="local_execucao" name="local_execucao" type="text"
                                              class="mt-1 block w-full" value="{{ old('local_execucao', $tr->local_execucao) }}" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="objetivo_geral" value="Objetivo geral" />
                            <textarea id="objetivo_geral" name="objetivo_geral" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objetivo_geral', $tr->objetivo_geral) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="objetivos_especificos" value="Objetivos específicos" />
                            <textarea id="objetivos_especificos" name="objetivos_especificos" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objetivos_especificos', $tr->objetivos_especificos) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 2.4 Justificativa --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">2.4 Justificativa</h3>
                    <textarea id="justificativa" name="justificativa" rows="4"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('justificativa', $tr->justificativa) }}</textarea>
                </div>

                {{-- 2.5 Recursos Financeiros --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">2.5 Recursos Financeiros</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="valor_total" value="Valor total previsto (R$)" />
                            <x-text-input id="valor_total" name="valor_total" type="number" step="0.01" min="0"
                                          class="mt-1 block w-full" value="{{ old('valor_total', $tr->valor_total) }}" />
                        </div>
                        <div>
                            <x-input-label for="fonte_recurso" value="Fonte do recurso" />
                            <x-text-input id="fonte_recurso" name="fonte_recurso" type="text"
                                          class="mt-1 block w-full" value="{{ old('fonte_recurso', $tr->fonte_recurso) }}" />
                        </div>
                    </div>
                    <p class="text-xs text-amber-600 mt-2">Validação manual: sistema contábil e orçamentário.</p>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('processos.show', $processo) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Salvar Termo de Referência
                    </button>
                </div>
            </form>

            {{-- Assinatura --}}
            <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Assinatura digital</p>
                    <p class="text-xs text-gray-400">
                        {{ $tr->assinado() ? 'Assinado por ' . $tr->assinante->name . ' em ' . $tr->assinado_em->format('d/m/Y H:i') : 'Salve o conteúdo antes de assinar.' }}
                    </p>
                </div>
                @unless($tr->assinado())
                    <form action="{{ route('processos.termo.assinar', $processo) }}" method="POST"
                          onsubmit="return confirm('Confirma a assinatura do Termo de Referência?')">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Assinar
                        </button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</x-app-layout>
