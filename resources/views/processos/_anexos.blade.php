{{-- Seção de anexos de uma peça do processo.
     Espera: $processo, $peca, $anexos (Collection), $podeAnexar (bool) --}}
<div class="bg-white shadow rounded-lg p-6 space-y-4">
    <div>
        <p class="text-sm font-semibold text-gray-800">Arquivos anexados</p>
        <p class="text-xs text-gray-400">
            @if($peca->ehArquivo())
                Este documento vem assinado/publicado de fora — anexe o(s) arquivo(s) correspondente(s).
            @else
                Anexos complementares deste documento (opcional).
            @endif
        </p>
    </div>

    @if($anexos->isEmpty())
        <p class="text-sm text-gray-400">Nenhum arquivo anexado.</p>
    @else
        <ul class="divide-y divide-gray-100 border border-gray-100 rounded-md">
            @foreach($anexos as $anexo)
                <li class="flex items-center justify-between gap-3 px-3 py-2">
                    <div class="min-w-0">
                        <a href="{{ route('processos.pecas.anexos.download', [$processo, $peca, $anexo]) }}"
                           class="text-sm text-indigo-600 hover:underline font-medium truncate block">
                            {{ $anexo->arquivo_nome }}
                        </a>
                        <p class="text-xs text-gray-400">
                            {{ $anexo->tamanhoLegivel() }}
                            @if($anexo->remetente) · enviado por {{ $anexo->remetente->name }} @endif
                            · {{ $anexo->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    @if($podeAnexar)
                        <form action="{{ route('processos.pecas.anexos.destroy', [$processo, $peca, $anexo]) }}" method="POST"
                              data-confirm="Remover este anexo?">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 whitespace-nowrap">Remover</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($podeAnexar)
        <form action="{{ route('processos.pecas.anexos.store', [$processo, $peca]) }}" method="POST"
              enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
            @csrf
            <input type="file" name="arquivo" required
                   class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                Anexar
            </button>
            <x-input-error :messages="$errors->get('arquivo')" class="w-full mt-1" />
        </form>
        <p class="text-xs text-gray-400">Formatos: PDF, Word, Excel, JPG, PNG · até 10 MB.</p>
    @endif
</div>
