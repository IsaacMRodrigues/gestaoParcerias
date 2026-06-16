<x-portal-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <p class="text-sm text-indigo-600 mb-2">
            <a href="{{ route('portal.chamamento', $chamamento) }}" class="hover:underline">← {{ $chamamento->titulo }}</a>
        </p>

        <h1 class="text-2xl font-bold text-gray-900 mb-1">Submeter Proposta</h1>

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6 text-sm text-indigo-800">
            <strong>OSC:</strong> {{ $osc->name }}
            &nbsp;·&nbsp; <strong>CNPJ:</strong> {{ $osc->cnpj }}
            @if($chamamento->valor_disponivel)
                &nbsp;·&nbsp; <strong>Valor disponível:</strong> R$ {{ number_format($chamamento->valor_disponivel, 2, ',', '.') }}
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <form action="{{ route('portal.proposta.store', $chamamento) }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título da Proposta *</label>
                    <input id="titulo" name="titulo" type="text" required
                           value="{{ old('titulo') }}"
                           placeholder="Ex.: Projeto de Formação Profissional para Jovens"
                           class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm @error('titulo') border-red-300 @enderror">
                    @error('titulo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="objeto" class="block text-sm font-medium text-gray-700 mb-1">Objeto da Proposta *</label>
                    <textarea id="objeto" name="objeto" rows="3" required
                              placeholder="Descreva o que sua organização irá realizar com este parceria..."
                              class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm @error('objeto') border-red-300 @enderror">{{ old('objeto') }}</textarea>
                    @error('objeto') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="justificativa" class="block text-sm font-medium text-gray-700 mb-1">Justificativa</label>
                    <textarea id="justificativa" name="justificativa" rows="3"
                              placeholder="Por que sua organização é adequada para este chamamento?"
                              class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('justificativa') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="valor_solicitado" class="block text-sm font-medium text-gray-700 mb-1">Valor Solicitado (R$) *</label>
                        <input id="valor_solicitado" name="valor_solicitado" type="number" step="0.01" min="0" required
                               value="{{ old('valor_solicitado') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm @error('valor_solicitado') border-red-300 @enderror">
                        @error('valor_solicitado') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="valor_proprio" class="block text-sm font-medium text-gray-700 mb-1">Contrapartida da OSC (R$)</label>
                        <input id="valor_proprio" name="valor_proprio" type="number" step="0.01" min="0"
                               value="{{ old('valor_proprio', 0) }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data_inicio_prevista" class="block text-sm font-medium text-gray-700 mb-1">Início Previsto</label>
                        <input id="data_inicio_prevista" name="data_inicio_prevista" type="date"
                               value="{{ old('data_inicio_prevista') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label for="data_fim_prevista" class="block text-sm font-medium text-gray-700 mb-1">Fim Previsto</label>
                        <input id="data_fim_prevista" name="data_fim_prevista" type="date"
                               value="{{ old('data_fim_prevista') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    Após salvar, você poderá anexar documentos e submeter a proposta quando estiver pronta.
                </p>

                <div class="flex items-center justify-end gap-4 pt-2 border-t border-gray-100">
                    <a href="{{ route('portal.chamamento', $chamamento) }}" class="text-sm text-gray-500 hover:text-gray-800">Cancelar</a>
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Salvar Proposta
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-portal-layout>
