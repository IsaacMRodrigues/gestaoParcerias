<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('processos.show', $processo) }}" class="hover:underline">Processo {{ $processo->numero }}</a>
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
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
                <div class="bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-lg text-sm">
                    Assinado por {{ $peca->assinante->name }} em {{ $peca->assinado_em->format('d/m/Y H:i') }}.
                </div>
            @elseif($podeAssinar && !$podeEditar)
                <div class="bg-slate-50 border border-slate-200 text-slate-800 px-4 py-3 rounded-lg text-sm">
                    Documento elaborado pela {{ \App\Models\Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel() }}.
                    Revise e <strong>assine</strong> abaixo.
                </div>
            @elseif(!$podeEditar)
                {{-- O motivo vem do model, com os fatos do caso: qual etapa preenche
                     este documento, em que etapa o processo está e com quem. A versão
                     anterior só dizia "preenchida pelo setor X na etapa correspondente",
                     que soa como contradição para quem é do próprio setor X. --}}
                <div class="bg-accent-50 border border-accent-200 text-accent-800 px-4 py-3 rounded-lg text-sm flex gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span>
                        <strong>Modo leitura.</strong>
                        {{ $peca->motivoNaoPodeEditar($processo, auth()->user()) }}
                    </span>
                </div>
            @endif

            @unless($peca->ehArquivo())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <x-input-label value="Conteúdo (modelo padrão)" class="mb-1" />
                @if($podeEditar)
                    <form action="{{ route('processos.pecas.update', [$processo, $peca]) }}" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        {{-- editor rico (TinyMCE) sobre o textarea --}}
                        <textarea name="conteudo" data-editor-rico>{!! old('conteudo', $peca->conteudo) !!}</textarea>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('processos.show', $processo) }}"
                               class="btn btn-secondary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Voltar
                            </a>
                            <button type="submit"
                                    class="btn btn-primary">
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
                           class="btn btn-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar
                        </a>
                        <a href="{{ route('processos.pecas.imprimir', [$processo, $peca]) }}" target="_blank"
                           class="btn btn-primary">
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
                           class="btn btn-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar
                        </a>
                    </div>
                @endif
            @endif

            @if(!$peca->ehArquivo() && ($podeAssinar || $peca->assinado()))
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center justify-between">
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
                              data-confirm="Confirma a assinatura deste documento?">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="btn btn-primary">
                                Assinar
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
