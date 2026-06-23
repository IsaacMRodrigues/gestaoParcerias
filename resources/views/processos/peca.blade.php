<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.show', $processo) }}" class="hover:underline">Processo {{ $processo->numero }}</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">
            {{ \App\Models\ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if($peca->assinado())
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    Assinado por {{ $peca->assinante->name }} em {{ $peca->assinado_em->format('d/m/Y H:i') }}.
                </div>
            @elseif($podeAssinar && !$podeEditar)
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
                    Documento elaborado pela {{ \App\Models\Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel() }}.
                    Revise e <strong>assine</strong> abaixo.
                </div>
            @elseif(!$podeEditar)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    Esta peça é preenchida pelo setor
                    <strong>{{ \App\Models\Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel() }}</strong>
                    na etapa correspondente. Você está no modo leitura.
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <x-input-label value="Conteúdo (modelo padrão)" class="mb-1" />
                @if($podeEditar)
                    <form action="{{ route('processos.pecas.update', [$processo, $peca]) }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        {{-- editor rico (Quill); sincroniza o HTML para o input ao salvar --}}
                        <input type="hidden" name="conteudo" id="conteudo_input" value="{{ old('conteudo', $peca->conteudo) }}">
                        <div data-editor-rico data-target="conteudo_input" class="bg-white">{!! old('conteudo', $peca->conteudo) !!}</div>
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('processos.show', $processo) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Salvar
                            </button>
                        </div>
                    </form>
                @else
                    {{-- modo leitura: renderiza o HTML do documento --}}
                    <div class="documento-html border border-gray-200 rounded-md p-4 bg-gray-50 text-gray-800">
                        {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                    </div>
                    <div class="flex justify-end mt-4">
                        <a href="{{ route('processos.show', $processo) }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
                    </div>
                @endif
            </div>

            @if($podeAssinar || $peca->assinado())
                <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Assinatura digital</p>
                        <p class="text-xs text-gray-400">
                            {{ $peca->assinado() ? 'Documento assinado.' : 'Confira o conteúdo e assine.' }}
                        </p>
                    </div>
                    @if($podeAssinar)
                        <form action="{{ route('processos.pecas.assinar', [$processo, $peca]) }}" method="POST"
                              onsubmit="return confirm('Confirma a assinatura deste documento?')">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                Assinar
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
