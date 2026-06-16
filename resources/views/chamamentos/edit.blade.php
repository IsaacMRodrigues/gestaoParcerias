<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('programas.index') }}" class="hover:underline">Programas</a>
            &rsaquo;
            <a href="{{ route('programas.chamamentos.index', $programa) }}" class="hover:underline">
                {{ $programa->sigla ?? $programa->name }}
            </a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Editar Chamamento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('programas.chamamentos.update', [$programa, $chamamento]) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('chamamentos._form', ['chamamento' => $chamamento])
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('programas.chamamentos.index', $programa) }}"
                           class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
