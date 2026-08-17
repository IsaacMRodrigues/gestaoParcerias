<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.index') }}" class="hover:underline">Processos</a>
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Novo Processo de Planejamento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-4">
                    O número do processo será gerado automaticamente no formato
                    <span class="font-mono text-gray-700">UG.Sequencial.Ano.Esfera</span>
                    (ex.: <span class="font-mono text-gray-700">0206.0133.2026.01</span>).
                    Após criar, você preencherá o Ofício, o Termo de Referência e as demais peças.
                </p>
                <form action="{{ route('processos.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label value="Unidade Gestora *" />
                        @if($orgaoUsuario)
                            <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
                                {{ $orgaoUsuario->codigo }} — {{ $orgaoUsuario->name }}
                                <span class="text-xs text-gray-400">(da sua lotação)</span>
                            </div>
                        @else
                            <select id="orgao_id" name="orgao_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                                <option value="">Selecione...</option>
                                @foreach($orgaos as $orgao)
                                    <option value="{{ $orgao->id }}" {{ old('orgao_id') == $orgao->id ? 'selected' : '' }}>
                                        {{ $orgao->codigo }} — {{ $orgao->name }} {{ $orgao->sigla ? "($orgao->sigla)" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
                        @endif
                    </div>
                    <div>
                        <x-input-label for="esfera" value="Esfera do Concedente *" />
                        <select id="esfera" name="esfera" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                            @foreach($esferas as $cod => $label)
                                <option value="{{ $cod }}" {{ old('esfera', '01') === $cod ? 'selected' : '' }}>
                                    {{ $cod }} — {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('esfera')" class="mt-2" />
                    </div>
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('processos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit"
                                class="btn btn-primary">
                            Abrir Processo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
