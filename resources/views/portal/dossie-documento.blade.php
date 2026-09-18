{{-- Um documento do processo, aberto pela OSC.

     Mesmo carimbo de assinatura das telas internas: é o mesmo documento, e a
     organização precisa poder conferir quem assinou e validar o código. --}}
<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div>
            <p class="text-sm text-brand-600">
                <a href="{{ route('portal.proposta.show', $proposta) }}" class="hover:underline">
                    &larr; {{ $proposta->titulo }}
                </a>
            </p>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $peca->rotuloDoDossie() }}</h1>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8">
            {{-- Mesma classe do checklist interno: é o estilo que o sistema já
                 dá ao texto dos documentos. --}}
            <div class="documento-html text-sm text-gray-800">
                {!! $peca->conteudo !!}
            </div>

            @if($peca->assinado())
                @include('processos._carimbo', ['peca' => $peca, 'qrValidacao' => null])
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('portal.proposta.show', $proposta) }}" class="btn btn-secondary">
                &larr; Voltar
            </a>
            @if($peca->assinado() && $peca->codigo_validacao)
                <a href="{{ route('validacao.mostrar', $peca->codigo_validacao) }}"
                   class="btn btn-secondary">Verificar autenticidade</a>
            @endif
            <button onclick="window.print()" class="btn btn-primary">Imprimir / PDF</button>
        </div>
    </div>
</x-portal-layout>
