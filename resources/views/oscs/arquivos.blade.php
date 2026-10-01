<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">Arquivos da OSC — {{ $osc->name }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            O que a organização anexou uma vez para todas as parcerias. A análise é feita em cada parceria, na tela da proposta.
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('arquivos-osc._lista', ['osc' => $osc, 'podeEditar' => false])
        </div>
    </div>
</x-app-layout>
