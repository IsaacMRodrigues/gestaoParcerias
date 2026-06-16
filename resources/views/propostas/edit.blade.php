<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
            &rsaquo; {{ $proposta->titulo }}
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Editar Proposta</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('propostas.update', $proposta) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('propostas._form', ['proposta' => $proposta])
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('propostas.show', $proposta) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
