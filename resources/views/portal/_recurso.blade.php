{{-- Recurso da OSC contra o resultado provisório: protocolado, prazo aberto, encerrado ou ainda por abrir. --}}
@if($meuRecurso)
    @if($chamamento->prazo_recurso_ate)
        <p class="text-xs text-gray-500 mb-3">Prazo do edital: até {{ $chamamento->prazo_recurso_ate->format('d/m/Y') }}.</p>
    @endif
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-sm text-gray-700">
            Recurso protocolado em
            <strong>{{ $meuRecurso->protocolado_em?->format('d/m/Y H:i') }}</strong>.
            @if($meuRecurso->temArquivo())
                <a href="{{ route('recursos.download', $meuRecurso) }}" class="text-brand-600 hover:underline">
                    Baixar a peça enviada
                </a>
            @endif
        </p>

        <p class="text-xs text-gray-500 mt-2">
            A Comissão de Seleção analisará o recurso. A resposta aparecerá nos documentos da sua inscrição.
        </p>
    </div>
@elseif($chamamento->faseRecursalAberta())
    <p class="text-xs text-gray-500 mb-3">
        Prazo do edital: até {{ $chamamento->prazo_recurso_ate->format('d/m/Y') }}. Recorrer é opcional.
    </p>
    @if(! auth()->user()->ehResponsavelLegalOsc())
        {{-- Membro da OSC vê que há prazo aberto, mas o protocolo é do responsável legal. --}}
        <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-4">
            O prazo de recurso está aberto. O recurso precisa ser protocolado pelo
            <strong>responsável legal</strong> da organização.
        </p>
    @else
        <form action="{{ route('recursos.store', $chamamento) }}" method="POST"
              enctype="multipart/form-data" class="space-y-3"
              data-confirm="Protocolar o recurso? Após o envio não é possível alterá-lo.">
            @csrf
            {{-- O recurso é o arquivo que a OSC anexa, com as razões dentro dele. --}}
            <div>
                <label for="arquivo" class="block text-xs font-medium text-gray-500 mb-1">
                    Recurso assinado, com as razões (arquivo único em PDF)
                </label>
                <input type="file" name="arquivo" id="arquivo" accept=".pdf" required
                       class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                <x-input-error :messages="$errors->get('arquivo')" class="mt-1" />
            </div>
            <button type="submit" class="btn btn-primary">Protocolar recurso</button>
        </form>
    @endif
@elseif($chamamento->prazoRecursalEncerrado())
    <p class="text-sm text-gray-600">
        O prazo de recurso terminou em {{ $chamamento->prazo_recurso_ate->format('d/m/Y') }}, sem recurso da sua organização.
    </p>
@else
    <p class="text-sm text-gray-600">
        O recurso abre aqui quando a Prefeitura publicar o <strong>Resultado Provisório</strong>, pelo prazo que o
        edital prevê. Recorrer é opcional; quem protocola é o responsável legal, com um arquivo PDF.
    </p>
@endif
