<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.index') }}" class="hover:underline">Processos</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Novo Processo de Planejamento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    O número do processo será gerado automaticamente. Após criar, você preencherá
                    o Ofício, o Termo de Referência e as demais peças.
                </p>
                <form action="{{ route('processos.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="orgao_id" value="Unidade Gestora *" />
                        <select id="orgao_id" name="orgao_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($orgaos as $orgao)
                                <option value="{{ $orgao->id }}" {{ old('orgao_id') == $orgao->id ? 'selected' : '' }}>
                                    {{ $orgao->name }} {{ $orgao->sigla ? "($orgao->sigla)" : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
                    </div>
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('processos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Abrir Processo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
