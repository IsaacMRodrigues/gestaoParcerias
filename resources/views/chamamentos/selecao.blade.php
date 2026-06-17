<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('programas.chamamentos.index', $chamamento->programa) }}" class="hover:underline">
                        {{ $chamamento->programa->sigla ?? $chamamento->programa->name }}
                    </a>
                    &rsaquo; Chamamentos
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
                    Seleção e Celebração
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        — {{ $chamamento->numero ? $chamamento->numero . ' · ' : '' }}{{ $chamamento->titulo }}
                    </span>
                </h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-indigo-50 text-indigo-700 rounded-full">
                {{ \App\Models\Peca::CATEGORIA_LABELS[$categoria] ?? $categoria }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Progresso --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold text-gray-800">Progresso da Documentação</h3>
                    <span class="text-sm text-gray-500">{{ $progresso['ok'] }}/{{ $progresso['total'] }} obrigatórias</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-green-500 h-2.5 rounded-full transition-all" style="width: {{ $progresso['percent'] }}%"></div>
                </div>
                @if($progresso['percent'] === 100)
                    <p class="text-sm text-green-700 mt-2">🟢 Documentação obrigatória completa.</p>
                @endif
            </div>

            {{-- Checklist --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Peças do Processo de Seleção</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        "Modelo padrão" tem assinatura digital. Demais itens são arquivos anexados.
                    </p>
                </div>
                @include('pecas._checklist', ['pecas' => $pecas])
            </div>
        </div>
    </div>
</x-app-layout>
