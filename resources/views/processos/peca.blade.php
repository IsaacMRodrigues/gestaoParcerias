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

            @if($peca->ehArquivo())
                <div class="bg-gray-50 border border-gray-200 text-gray-700 px-4 py-3 rounded-lg text-sm">
                    Este documento é anexado como <strong>arquivo</strong> (sem edição de texto nem assinatura no sistema).
                    Responsável: <strong>{{ \App\Models\Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel() }}</strong>.
                    @unless($podeAnexar)
                        Você está no modo leitura.
                    @endunless
                </div>
            @elseif($peca->assinado())
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

            @unless($peca->ehArquivo())
            <div class="bg-white shadow rounded-lg p-6">
                <x-input-label value="Conteúdo (modelo padrão)" class="mb-1" />
                @if($podeEditar)
                    <form action="{{ route('processos.pecas.update', [$processo, $peca]) }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        {{-- editor rico (TinyMCE) sobre o textarea --}}
                        <textarea name="conteudo" data-editor-rico>{!! old('conteudo', $peca->conteudo) !!}</textarea>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('processos.show', $processo) }}"
                               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-400 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Voltar
                            </a>
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition">
                                Salvar
                            </button>
                        </div>
                    </form>
                @else
                    {{-- modo leitura: renderiza o HTML do documento --}}
                    <div class="documento-html border border-gray-200 rounded-md p-4 bg-gray-50 text-gray-800">
                        {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                        @include('processos._carimbo', ['peca' => $peca, 'qrValidacao' => $qrValidacao])
                    </div>
                    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                        <a href="{{ route('processos.show', $processo) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar
                        </a>
                        <a href="{{ route('processos.pecas.imprimir', [$processo, $peca]) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir / PDF
                        </a>
                    </div>
                @endif
            </div>
            @endunless

            {{-- Anexos: peça ARQUIVO (só arquivos) ou peça de texto que aceita anexos (Edital) --}}
            @if($peca->aceitaAnexos())
                @include('processos._anexos', ['processo' => $processo, 'peca' => $peca, 'anexos' => $anexos, 'podeAnexar' => $podeAnexar])

                {{-- Peça ARQUIVO não tem card de conteúdo: oferece o Voltar aqui --}}
                @if($peca->ehArquivo())
                    <div>
                        <a href="{{ route('processos.show', $processo) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar
                        </a>
                    </div>
                @endif
            @endif

            @if(!$peca->ehArquivo() && ($podeAssinar || $peca->assinado()))
                <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Assinatura digital</p>
                        @if($peca->assinado())
                            <p class="text-xs text-gray-500">
                                Assinado por <strong>{{ $peca->assinante->name }}</strong>
                                em {{ $peca->assinado_em->format('d/m/Y H:i') }}.
                                Código de validação: <strong>{{ $peca->codigo_validacao }}</strong>
                            </p>
                        @else
                            <p class="text-xs text-gray-400">Confira o conteúdo e assine.</p>
                        @endif
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
