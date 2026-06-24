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
                        {{-- editor rico (TinyMCE) sobre o textarea --}}
                        <textarea name="conteudo" data-editor-rico>{!! old('conteudo', $peca->conteudo) !!}</textarea>
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

                        {{-- carimbo da assinatura, no padrão de documento oficial --}}
                        @if($peca->assinado())
                            @php
                                $assinante = $peca->assinante;
                                $papel = $assinante?->roles->first()?->name;
                                $papelLabel = $papel ? (\App\Models\User::$roleLabels[$papel] ?? null) : null;
                                $setorLabel = $assinante?->setor ? (\App\Models\Processo::SETORES[$assinante->setor] ?? null) : null;
                                $cargo = $papelLabel ?: $setorLabel;
                            @endphp
                            <table style="border:none;border-collapse:collapse;width:100%;margin-top:28px;border-top:2px solid #1e3a8a;">
                                <tr>
                                    <td style="border:none;width:48px;vertical-align:top;padding-top:8px;font-size:26px;">🔏</td>
                                    <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                                        <p style="margin:0;">Documento assinado eletronicamente por
                                            <strong>{{ $assinante?->name }}</strong>@if($cargo), {{ $cargo }}@endif,
                                            em <strong>{{ $peca->assinado_em->format('d/m/Y') }}</strong>,
                                            às <strong>{{ $peca->assinado_em->format('H:i') }}</strong>,
                                            conforme horário oficial de Brasília, com fundamento na Lei Federal nº 13.019/2014.</p>
                                        <p style="margin:4px 0 0;">A autenticidade deste documento pode ser conferida no endereço
                                            <strong>{{ url('/validar') }}</strong>, informando o código verificador
                                            <strong style="font-family:monospace;letter-spacing:.5px;">{{ $peca->codigo_validacao }}</strong>.</p>
                                    </td>
                                </tr>
                            </table>
                        @endif
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
