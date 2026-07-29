<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('instrumentos.show', $instrumento) }}" class="hover:underline">Instrumento {{ $instrumento->numero }}</a>
            <span class="text-gray-300">/</span> Ordem de Pagamento
        </p>
        <h2 class="text-xl font-semibold text-gray-900 mt-0.5">Ordem de Pagamento nº {{ $op->numero }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @if($op->assinado())
                <div class="flex items-center gap-3 p-4 rounded-lg bg-green-50 border border-green-200 text-sm text-green-800">
                    <span class="text-lg">🔏</span>
                    <div>
                        Assinada eletronicamente por <strong>{{ $op->assinante?->name }}</strong>
                        em {{ $op->assinado_em->format('d/m/Y \à\s H:i') }}.
                        Código: <span class="font-mono">{{ $op->codigo_validacao }}</span>.
                    </div>
                </div>
            @endif

            {{-- Dados + documento --}}
            <form method="POST" action="{{ route('ordens-pagamento.update', $op) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-1">
                        <x-input-label for="favorecido" value="Favorecido (OSC)" />
                        <x-text-input id="favorecido" name="favorecido" type="text" class="mt-1 block w-full"
                                      value="{{ old('favorecido', $op->favorecido) }}" :disabled="$op->assinado()" />
                    </div>
                    <div>
                        <x-input-label for="valor" value="Valor (R$)" />
                        <x-text-input id="valor" name="valor" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                      value="{{ old('valor', $op->valor) }}" :disabled="$op->assinado()" />
                    </div>
                    <div>
                        <x-input-label for="data_emissao" value="Data de emissão" />
                        <x-text-input id="data_emissao" name="data_emissao" type="date" class="mt-1 block w-full"
                                      value="{{ old('data_emissao', optional($op->data_emissao)->format('Y-m-d')) }}" :disabled="$op->assinado()" />
                    </div>
                </div>

                <div>
                    <x-input-label for="conteudo" value="Documento (modelo padrão)" />
                    @if($op->assinado())
                        <div class="mt-1 border border-gray-200 rounded-lg p-4 documento-html bg-gray-50">{!! $op->conteudo !!}</div>
                    @else
                        <textarea id="conteudo" name="conteudo" data-editor-rico>{!! old('conteudo', $op->conteudo) !!}</textarea>
                    @endif
                    <x-input-error :messages="$errors->get('conteudo')" class="mt-2" />
                </div>

                @unless($op->assinado())
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('instrumentos.show', $instrumento) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                        <x-primary-button>Salvar</x-primary-button>
                    </div>
                @endunless
            </form>

            {{-- Dados bancários --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Dados bancários (comprovante)</h3>
                @if($op->temDadosBancarios())
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="text-gray-700">📎 {{ $op->dados_bancarios_nome }}</span>
                        <a href="{{ route('ordens-pagamento.dados-bancarios.download', $op) }}" class="text-indigo-600 hover:text-indigo-900">Baixar</a>
                    </div>
                @else
                    <p class="text-sm text-gray-400 mb-3">Nenhum arquivo anexado.</p>
                @endif
                <form method="POST" action="{{ route('ordens-pagamento.dados-bancarios.upload', $op) }}"
                      enctype="multipart/form-data" class="flex items-center gap-3 mt-3">
                    @csrf
                    <input type="file" name="arquivo" required accept=".pdf,.jpg,.jpeg,.png"
                           class="text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Anexar</button>
                </form>
                <x-input-error :messages="$errors->get('arquivo')" class="mt-2" />
            </div>

            {{-- Ações --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('ordens-pagamento.imprimir', $op) }}" target="_blank"
                   class="px-4 py-2 text-sm font-medium text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-50">
                    Imprimir / PDF
                </a>

                @unless($op->assinado())
                    <form method="POST" action="{{ route('ordens-pagamento.assinar', $op) }}"
                          data-confirm="Assinar eletronicamente esta ordem de pagamento? Após assinar não será possível editá-la.">
                        @csrf @method('PATCH')
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">
                            🔏 Assinar
                        </button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</x-app-layout>
