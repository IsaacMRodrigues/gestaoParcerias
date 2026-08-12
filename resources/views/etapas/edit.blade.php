<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
            &rsaquo;
            <a href="{{ route('propostas.show', $proposta) }}" class="hover:underline">{{ $proposta->titulo }}</a>
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
            Editar Etapa {{ $etapa->numero }} — Meta {{ $meta->numero }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <form action="{{ route('propostas.metas.etapas.update', [$proposta, $meta, $etapa]) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('etapas._form', ['etapa' => $etapa])
                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('propostas.show', $proposta) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
