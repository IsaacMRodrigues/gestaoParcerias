<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('chamamentos.index') }}" class="hover:underline">Chamamentos</a>
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Novo Chamamento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <form action="{{ route('chamamentos.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="orgao_id" value="Secretaria *" />
                        <select id="orgao_id" name="orgao_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Selecione…</option>
                            @foreach($orgaos as $orgao)
                                <option value="{{ $orgao->id }}" @selected((string) old('orgao_id') === (string) $orgao->id)>{{ $orgao->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
                    </div>
                    @include('chamamentos._form')
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('chamamentos.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            Cadastrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
