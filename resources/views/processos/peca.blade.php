<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.show', $processo) }}" class="hover:underline">Processo {{ $processo->numero }}</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
            {{ \App\Models\ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if($peca->assinado())
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    Assinado por {{ $peca->assinante->name }} em {{ $peca->assinado_em->format('d/m/Y H:i') }}.
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('processos.pecas.update', [$processo, $peca]) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="conteudo" value="Conteúdo (modelo padrão)" />
                        <textarea id="conteudo" name="conteudo" rows="12"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                  placeholder="Preencha o conteúdo do documento...">{{ old('conteudo', $peca->conteudo) }}</textarea>
                    </div>
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('processos.show', $processo) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Assinatura digital</p>
                    <p class="text-xs text-gray-400">
                        {{ $peca->assinado() ? 'Documento assinado.' : 'Salve o conteúdo antes de assinar.' }}
                    </p>
                </div>
                @unless($peca->assinado())
                    <form action="{{ route('processos.pecas.assinar', [$processo, $peca]) }}" method="POST"
                          onsubmit="return confirm('Confirma a assinatura deste documento?')">
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
