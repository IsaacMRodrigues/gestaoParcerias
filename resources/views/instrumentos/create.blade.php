<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('instrumentos.index') }}" class="hover:underline">Instrumentos</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
            Formalizar Instrumento
            <span class="text-sm font-normal text-gray-500 ml-2">— {{ $proposta->titulo }}</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Resumo da proposta --}}
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6 text-sm">
                <p class="font-medium text-indigo-800">Proposta aprovada</p>
                <div class="grid grid-cols-3 gap-4 mt-2 text-indigo-700">
                    <div>OSC: <span class="font-medium">{{ $proposta->osc->name }}</span></div>
                    <div>Valor solicitado: <span class="font-medium">R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</span></div>
                    <div>Contrapartida: <span class="font-medium">R$ {{ number_format($proposta->valor_proprio, 2, ',', '.') }}</span></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('instrumentos.store', $proposta) }}" method="POST" class="space-y-4">
                    @csrf
                    {{-- Pré-preenche tipo com base no programa --}}
                    @php
                        $tipoPrograma = $proposta->chamamento->programa->tipo ?? null;
                    @endphp
                    @include('instrumentos._form', [
                        'instrumento' => (object)[
                            'numero'        => null,
                            'tipo'          => $tipoPrograma,
                            'objeto'        => $proposta->objeto,
                            'valor_repasse' => $proposta->valor_solicitado,
                            'valor_proprio' => $proposta->valor_proprio,
                            'data_inicio'   => $proposta->data_inicio_prevista,
                            'data_fim'      => $proposta->data_fim_prevista,
                            'status'        => 'minuta',
                            'publicado_doe' => false,
                            'data_assinatura'     => null,
                            'data_publicacao_doe' => null,
                            'numero_doe'          => null,
                        ]
                    ])
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('propostas.show', $proposta) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Criar Instrumento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
