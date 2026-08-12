<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('instrumentos.show', $instrumento) }}" class="hover:underline">{{ $instrumento->numero }}</a>
                    &rsaquo; Aditivos
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
                    Documentação — {{ $aditivo->numero }}º Aditivo
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        ({{ \App\Models\Aditivo::TIPOS[$aditivo->tipo] ?? $aditivo->tipo }})
                    </span>
                </h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-brand-50 text-brand-700 rounded-full">
                {{ \App\Models\Peca::CATEGORIA_LABELS[$categoria] ?? $categoria }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                @include('pecas._cabecalho', [
                    'titulo' => 'Documentos do Processo',
                    'descricao' => 'Itens "puxar do módulo Gestão de Parcerias" podem ser anexados manualmente nesta versão.',
                    'progresso' => $progresso,
                ])
                @include('pecas._checklist', ['pecas' => $pecas])
            </div>
        </div>
    </div>
</x-app-layout>
