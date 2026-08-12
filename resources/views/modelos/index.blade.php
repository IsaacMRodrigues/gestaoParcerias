<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Tecnologia da Informação</p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Modelos padrão</h2>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm text-gray-500">
                    <strong class="text-gray-900">{{ $resumo['com_texto'] }}</strong> de
                    {{ $resumo['total'] }} com texto
                </p>
                <p class="text-xs text-gray-400">documentos que alimentam os trâmites</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white border border-gray-200 rounded-xl p-4 text-sm text-gray-600">
                Cada item abaixo é um <strong>modelo padrão</strong>: o texto já vem pré-preenchido na peça
                quando o trâmite a cria, e o setor responsável apenas ajusta os campos
                <span class="font-mono text-xs bg-gray-100 px-1 rounded">XXXX</span> antes de assinar.
                Alterar um modelo afeta somente as peças criadas <em>daí em diante</em> — peças já
                existentes mantêm o texto que receberam.
            </div>

            @foreach($grupos as $origem => $modelos)
                @continue(empty($modelos))
                <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-800">
                            {{ \App\Http\Controllers\ModeloController::ORIGENS[$origem] }}
                        </h3>
                        @php $comTexto = collect($modelos)->where('tem_texto', true)->count(); @endphp
                        <span class="text-xs text-gray-400 whitespace-nowrap">
                            {{ $comTexto }}/{{ count($modelos) }} com texto
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach($modelos as $m)
                            <div class="px-6 py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800">{{ $m['rotulo'] }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5 flex flex-wrap items-center gap-x-3">
                                        <span class="font-mono">{{ $m['chave'] }}</span>
                                        @if($m['setor'])
                                            <span>
                                                {{ \App\Http\Controllers\ModeloController::setorLabel($origem, $m['setor']) }}
                                            </span>
                                        @endif
                                        @if(!is_null($m['etapa']))
                                            <span>etapa {{ $m['etapa'] + 1 }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    @if($m['tem_texto'])
                                        <a href="{{ route('modelos.show', [$origem, $m['chave']]) }}"
                                           class="px-3 py-1.5 text-xs font-medium text-brand-700 border border-brand-300 rounded-md hover:bg-brand-50 transition">
                                            Ver modelo
                                        </a>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-amber-50 text-amber-700 rounded-full">
                                            sem texto
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
