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
            @elseif(!$podeEditar)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    O Termo de Referência é de responsabilidade da <strong>Unidade Gestora</strong> e só pode ser
                    preenchido na 1ª etapa. Você está no modo leitura.
                </div>
            @endif

            <form action="{{ route('processos.termo.update', $processo) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')
                <fieldset @disabled(!$podeEditar) class="space-y-6 {{ $podeEditar ? '' : 'opacity-90' }}">

                    <div class="bg-white shadow rounded-lg p-6 space-y-4">
                        <div>
                            <x-input-label for="descricao_realidade" value="Descrição da realidade objeto da parceria" />
                            <textarea id="descricao_realidade" name="descricao_realidade" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('descricao_realidade', $tr->descricao_realidade) }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Descreva a situação que demanda a atuação da administração pública e os resultados esperados.</p>
                        </div>
                        <div>
                            <x-input-label for="justificativa" value="Justificativa" />
                            <textarea id="justificativa" name="justificativa" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('justificativa', $tr->justificativa) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="objeto" value="Objeto da parceria" />
                            <textarea id="objeto" name="objeto" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objeto', $tr->objeto) }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">O objeto deve ser específico, mensurável e compatível com a política pública da área.</p>
                        </div>
                        <div>
                            <x-input-label for="objetivos_especificos" value="Objetivos específicos" />
                            <textarea id="objetivos_especificos" name="objetivos_especificos" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objetivos_especificos', $tr->objetivos_especificos) }}</textarea>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Estimativa de Orçamento</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="valor_total" value="Valor total previsto (R$)" />
                                <x-text-input id="valor_total" name="valor_total" type="number" step="0.01" min="0"
                                              class="mt-1 block w-full" value="{{ old('valor_total', $tr->valor_total) }}" />
                            </div>
                            <div>
                                <x-input-label for="prazo_meses" value="Prazo de execução (meses)" />
                                <x-text-input id="prazo_meses" name="prazo_meses" type="number" min="0"
                                              class="mt-1 block w-full" value="{{ old('prazo_meses', $tr->prazo_meses) }}" />
                            </div>
                            <div>
                                <x-input-label for="dotacao" value="Dotação" />
                                <x-text-input id="dotacao" name="dotacao" type="text"
                                              class="mt-1 block w-full" value="{{ old('dotacao', $tr->dotacao) }}" />
                            </div>
                            <div>
                                <x-input-label for="ficha" value="Ficha" />
                                <x-text-input id="ficha" name="ficha" type="text"
                                              class="mt-1 block w-full" value="{{ old('ficha', $tr->ficha) }}" />
                            </div>
                            <div>
                                <x-input-label for="fonte" value="Fonte" />
                                <x-text-input id="fonte" name="fonte" type="text"
                                              class="mt-1 block w-full" value="{{ old('fonte', $tr->fonte) }}" />
                            </div>
                        </div>
                        <p class="text-xs text-amber-600 mt-2">Validação manual: conferir dotação/ficha/fonte no sistema orçamentário.</p>
                    </div>
                </fieldset>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('processos.show', $processo) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                    @if($podeEditar)
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Salvar Termo de Referência
                        </button>
                    @endif
                </div>
            </form>

            {{-- Assinatura --}}
            @if($podeEditar || $tr->assinado())
            <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Assinatura digital</p>
                    <p class="text-xs text-gray-400">
                        {{ $tr->assinado() ? 'Assinado por ' . $tr->assinante->name . ' em ' . $tr->assinado_em->format('d/m/Y H:i') : 'Salve o conteúdo antes de assinar.' }}
                    </p>
                </div>
                @if($podeEditar && !$tr->assinado())
                    <form action="{{ route('processos.termo.assinar', $processo) }}" method="POST"
                          onsubmit="return confirm('Confirma a assinatura do Termo de Referência?')">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Assinar
                        </button>
                    </form>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
