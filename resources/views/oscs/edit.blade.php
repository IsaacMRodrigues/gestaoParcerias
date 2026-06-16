<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar OSC</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('oscs.update', $osc) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('oscs._form', ['osc' => $osc])
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('oscs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
