{{-- Uma linha do painel: o que foi lançado, a situação e as ações de cada lado.
     Espera: $item (Despesa ou Lancamento), $tipo (do formulário), $titulo, $detalhe, $valor (?string),
     $arquivoUrl (?string), $arquivoNome, $instrumento, $ehOsc, $podeConferir. --}}
@php
    $ehDespesa = $item instanceof \App\Models\Despesa;
    $travado = $item->travadoPorPrestacao();
    $selo = [
        'pendente'  => 'bg-accent-50 text-accent-800 ring-accent-200',
        'conferido' => 'bg-brand-50 text-brand-800 ring-brand-200',
        'devolvido' => 'bg-red-50 text-red-700 ring-red-200',
    ][$item->situacao] ?? 'bg-gray-100 text-gray-600 ring-gray-200';
    $conferencia = $ehDespesa ? route('lancamentos.despesas.conferir', $item) : route('lancamentos.conferir', $item);
    $exclusao = $ehDespesa ? route('lancamentos.despesas.destroy', $item) : route('lancamentos.destroy', $item);
    $acao = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer select-none transition marker:content-none';
@endphp

<div class="px-6 py-3.5">
    <div class="flex flex-wrap items-start gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900">{{ $titulo }}</p>
            @if($detalhe)<p class="text-xs text-gray-500 mt-0.5">{{ $detalhe }}</p>@endif
            <p class="text-xs text-gray-400 mt-0.5">
                Lançado {{ $item->created_at?->format('d/m/Y') }}@if($item->autor) por {{ $item->autor->name }}@endif
                @if($item->conferido() && $item->conferente)
                    · conferido por {{ $item->conferente->name }} em {{ $item->conferido_em?->format('d/m/Y') }}
                @endif
                @if($travado) · já enviado numa prestação de contas @endif
            </p>
            @if($item->devolucao_motivo && ! $item->conferido())
                <p class="mt-1 text-xs {{ $item->devolvido() ? 'text-red-700' : 'text-gray-500' }}">
                    {{ $item->devolvido() ? 'Devolvido' : 'Corrigido após devolução' }}: {{ $item->devolucao_motivo }}
                </p>
            @endif
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if($valor)<span class="text-sm font-semibold text-gray-900">{{ $valor }}</span>@endif
            @if($arquivoUrl)
                <a href="{{ $arquivoUrl }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800" title="{{ $arquivoNome }}">Baixar</a>
            @endif
            <span class="px-2 py-0.5 text-[11px] font-semibold rounded ring-1 {{ $selo }}">{{ $item->rotuloDaSituacao() }}</span>
        </div>
    </div>

    {{-- Ações: a OSC corrige ou exclui o que ainda é dela; a Prefeitura confere ou devolve. --}}
    @if(($ehOsc && $item->podeSerAlteradoPelaOsc()) || ($podeConferir && ! $travado && ! $item->conferido()))
        <div class="mt-2 flex flex-wrap items-start gap-2">
            @if($ehOsc && $item->podeSerAlteradoPelaOsc())
                <details class="group w-full">
                    <summary class="{{ $acao }} {{ $item->devolvido() ? 'text-brand-800 bg-brand-50 hover:bg-brand-100' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}">
                        {{ $item->devolvido() ? 'Corrigir' : 'Editar' }}
                    </summary>
                    <div class="mt-3 p-4 rounded-lg border border-gray-200 bg-gray-50">
                        @include('lancamentos._form', ['tipo' => $tipo, 'item' => $item])
                        <form action="{{ $exclusao }}" method="POST" class="mt-3 pt-3 border-t border-gray-200"
                              data-confirm="Excluir este lançamento?">
                            @csrf @method('DELETE')
                            <button class="text-xs text-gray-500 hover:text-red-700">Excluir lançamento</button>
                        </form>
                    </div>
                </details>
            @endif

            @if($podeConferir && ! $travado && ! $item->conferido())
                <form action="{{ $conferencia }}" method="POST">
                    @csrf <input type="hidden" name="acao" value="conferir">
                    <button class="btn btn-primary btn-sm">Conferido</button>
                </form>
                @unless($item->devolvido())
                    <details class="group">
                        <summary class="{{ $acao }} text-accent-800 bg-accent-50 hover:bg-accent-100">Devolver à OSC</summary>
                        <form action="{{ $conferencia }}" method="POST" class="mt-2 flex flex-wrap gap-2">
                            @csrf <input type="hidden" name="acao" value="devolver">
                            <input name="motivo" required maxlength="1000" placeholder="O que está errado e como corrigir"
                                   class="flex-1 min-w-0 border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            <button class="btn btn-secondary btn-sm">Devolver</button>
                        </form>
                    </details>
                @endunless
            @endif
        </div>
    @endif
</div>
