<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('modelos.index') }}" class="hover:underline">Modelos padrão</a>
                &rsaquo; {{ \App\Http\Controllers\ModeloController::ORIGENS[$origem] }}
            </p>
            <h2 class="text-xl font-semibold text-gray-800 mt-0.5">{{ $modelo['rotulo'] }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Ficha técnica --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Chave</dt>
                        <dd class="text-gray-800 mt-0.5 font-mono text-xs">{{ $modelo['chave'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Origem</dt>
                        <dd class="text-gray-800 mt-0.5">
                            {{ \App\Http\Controllers\ModeloController::ORIGENS[$origem] }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Responsável</dt>
                        <dd class="text-gray-800 mt-0.5">
                            {{ \App\Http\Controllers\ModeloController::setorLabel($origem, $modelo['setor']) ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Etapa</dt>
                        <dd class="text-gray-800 mt-0.5">
                            {{ is_null($modelo['etapa']) ? '—' : 'etapa ' . ($modelo['etapa'] + 1) }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Pré-visualização --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-800">Pré-visualização</h3>
                    <span class="text-xs text-gray-400">como o documento nasce na peça</span>
                </div>
                <div class="p-6 bg-gray-50">
                    <div class="documento-html bg-white border border-gray-200 rounded-md p-8 text-gray-800 text-sm shadow-sm">
                        {!! $conteudo !!}
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('modelos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Voltar aos modelos
                </a>
                <p class="text-xs text-gray-400">
                    Para alterar este texto é preciso editar o código-fonte do modelo.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
