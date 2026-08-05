{{-- Espera: $pecas (Collection de App\Models\Peca) --}}
<div class="divide-y divide-gray-100">
    @foreach($pecas as $peca)
        @php
            // No trâmite da Seleção só o setor da etapa atual preenche/assina;
            // fora dele (Dispensa, Aditivo, Apostilamento) tudo segue liberado.
            $podePreencher = $peca->podePreencher(auth()->user());
            $podeAssinar   = $peca->podeAssinar(auth()->user());
            $trava         = $peca->motivoTrava(auth()->user());
        @endphp
        <div class="px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    {{-- indicador de status --}}
                    @if($peca->assinado())
                        <span class="mt-0.5 text-green-500" title="Assinado">✅</span>
                    @elseif($peca->preenchido())
                        <span class="mt-0.5 text-blue-500" title="Preenchido">📄</span>
                    @else
                        <span class="mt-0.5 text-gray-300" title="Pendente">⬜</span>
                    @endif

                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $peca->rotulo }}
                            @if($peca->obrigatorio)
                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-red-50 text-red-600 font-medium">obrigatório</span>
                            @else
                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-normal">opcional</span>
                            @endif
                            <span class="ml-1 text-xs px-1.5 py-0.5 rounded {{ $peca->tipo === 'modelo' ? 'bg-purple-50 text-purple-600' : 'bg-amber-50 text-amber-600' }}">
                                {{ $peca->tipo === 'modelo' ? 'modelo padrão' : 'arquivo' }}
                            </span>
                        </p>
                        @if($peca->assinado())
                            <p class="text-xs text-gray-400 mt-0.5">
                                Assinado por {{ $peca->assinante->name }} em {{ $peca->assinado_em->format('d/m/Y H:i') }}
                            </p>
                            @if($peca->exigeContraAssinatura())
                                <p class="text-xs mt-0.5 {{ $peca->contraAssinado() ? 'text-gray-400' : 'text-amber-600' }}">
                                    @if($peca->contraAssinado())
                                        Contra-assinado pela OSC — {{ $peca->contraAssinante->name ?? '—' }}
                                        em {{ $peca->contra_assinado_em->format('d/m/Y H:i') }}
                                    @else
                                        ⏳ Aguardando a contra-assinatura da OSC (assinatura das partes)
                                    @endif
                                </p>
                            @endif
                        @elseif($trava)
                            <p class="text-xs text-amber-600 mt-0.5">🔒 {{ $trava }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TIPO MODELO: editor rico (brasão + HTML) + assinar --}}
            @if($peca->tipo === 'modelo')
                <details class="mt-3" {{ $peca->preenchido() && !$peca->assinado() ? 'open' : '' }}>
                    <summary class="text-xs text-brand-600 cursor-pointer hover:underline">
                        {{ $peca->assinado() ? 'Ver conteúdo' : ($peca->preenchido() ? 'Editar conteúdo' : 'Preencher conteúdo') }}
                    </summary>
                    <div class="mt-2">
                        @if($peca->assinado())
                            <div class="documento-html border border-gray-200 rounded-md p-4 bg-white text-gray-800 text-sm">
                                {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                                @php
                                    $qrValidacao = null;
                                    if ($peca->codigo_validacao) {
                                        $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)
                                            ->generate(route('validacao.mostrar', $peca->codigo_validacao));
                                    }
                                @endphp
                                @include('processos._carimbo', ['peca' => $peca, 'qrValidacao' => $qrValidacao])
                            </div>
                            @if($peca->codigo_validacao)
                                <p class="mt-2 text-xs text-gray-500">
                                    Código de validação:
                                    <strong class="font-mono">{{ $peca->codigo_validacao }}</strong>
                                    · <a href="{{ route('validacao.mostrar', $peca->codigo_validacao) }}" target="_blank" class="text-brand-600 hover:underline">Validar</a>
                                </p>
                            @endif
                            @if($peca->contraAssinado() && $peca->codigo_validacao_contra)
                                <p class="mt-1 text-xs text-gray-500">
                                    Contra-assinatura da OSC:
                                    <strong class="font-mono">{{ $peca->codigo_validacao_contra }}</strong>
                                </p>
                            @endif
                            @if($peca->podeContraAssinar(auth()->user()))
                                <form action="{{ route('pecas.contra-assinar', $peca) }}" method="POST" class="mt-3"
                                      data-confirm="Confirma a assinatura deste Termo pela OSC?">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-md hover:bg-emerald-700">
                                        Assinar como OSC (contra-assinatura)
                                    </button>
                                </form>
                            @endif
                        @elseif($podePreencher)
                            <form action="{{ route('pecas.salvar', $peca) }}" method="POST">
                                @csrf @method('PUT')
                                <textarea name="conteudo" data-editor-rico>{!! old('conteudo', $peca->conteudo) !!}</textarea>
                                <button type="submit"
                                        class="mt-2 px-3 py-1.5 text-xs font-medium text-white bg-brand-600 rounded-md hover:bg-brand-700">
                                    Salvar
                                </button>
                            </form>
                        @else
                            <div class="documento-html border border-gray-200 rounded-md p-4 bg-gray-50 text-gray-700 text-sm">
                                {!! $peca->conteudo ?: '<p class="text-gray-400">Documento ainda não preenchido.</p>' !!}
                            </div>
                        @endif
                        @if($peca->preenchido() && $podeAssinar)
                            <form action="{{ route('pecas.assinar', $peca) }}" method="POST" class="mt-2"
                                  data-confirm="Confirma a assinatura digital deste documento?">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                    Assinar
                                </button>
                            </form>
                        @endif
                    </div>
                </details>

            {{-- TIPO ARQUIVO: upload / download / remover --}}
            @else
                <div class="mt-3">
                    @if($peca->temArquivo())
                        <div class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-brand-600 uppercase">
                                    {{ strtoupper(pathinfo($peca->arquivo_nome, PATHINFO_EXTENSION)) }}
                                </span>
                                <span class="text-sm text-gray-700">{{ $peca->arquivo_nome }}</span>
                                <span class="text-xs text-gray-400">({{ $peca->tamanhoFormatado() }})</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('pecas.download', $peca) }}" class="text-xs text-brand-600 hover:text-brand-900">Baixar</a>
                                @if($podePreencher)
                                    <form action="{{ route('pecas.arquivo.remover', $peca) }}" method="POST"
                                          data-confirm="Remover este arquivo?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @elseif(!$podePreencher)
                        <p class="text-xs text-gray-400">Nenhum arquivo enviado.</p>
                    @else
                        <form action="{{ route('pecas.upload', $peca) }}" method="POST" enctype="multipart/form-data"
                              class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="arquivo" required
                                   class="block text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-brand-600 rounded-md hover:bg-brand-700">
                                Enviar
                            </button>
                        </form>

                        @if($peca->puxavel())
                            @php $docsDisponiveis = $peca->documentosDisponiveis(); @endphp
                            @if($docsDisponiveis->isNotEmpty())
                                <form action="{{ route('pecas.puxar', $peca) }}" method="POST"
                                      class="mt-2 flex items-center gap-2 flex-wrap">
                                    @csrf
                                    <span class="text-xs text-gray-500">ou puxar do módulo Gestão de Parcerias:</span>
                                    <select name="documento_id" required
                                            class="text-xs border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 max-w-xs">
                                        <option value="">Selecione um documento…</option>
                                        @foreach($docsDisponiveis as $doc)
                                            <option value="{{ $doc->id }}">
                                                {{ \App\Models\Documento::TIPOS[$doc->tipo] ?? $doc->tipo }} — {{ $doc->nome_original }}{{ $doc->proposta->osc ? ' · ' . $doc->proposta->osc->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                            class="px-3 py-1.5 text-xs font-medium text-brand-700 border border-brand-300 rounded-md hover:bg-brand-50">
                                        Puxar
                                    </button>
                                </form>
                            @else
                                <p class="mt-2 text-xs text-gray-400">
                                    Nenhum documento disponível para puxar do módulo Gestão de Parcerias.
                                </p>
                            @endif
                        @endif
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
