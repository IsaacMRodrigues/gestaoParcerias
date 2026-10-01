<x-portal-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Arquivos da OSC</h1>
            <p class="text-sm text-gray-500 mt-1">
                Anexe aqui, uma vez só, as certidões, os documentos institucionais e as declarações da organização.
                Eles valem para todas as suas propostas e parcerias; a Prefeitura os analisa em cada uma. Para trocar
                um documento, envie nova versão — as anteriores ficam no histórico.
            </p>
        </div>

        <x-flash-message />

        @if($pendencias = $osc->pendenciasDosArquivos())
            <div class="bg-accent-50 border border-accent-200 rounded-xl p-4 text-sm text-accent-800">
                <p class="font-semibold">Para enviar manifestação de interesse ou Nova Proposta, e na Celebração, a área precisa estar completa e em dia:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5">
                    @foreach($pendencias as $p)<li>{{ $p }}</li>@endforeach
                </ul>
            </div>
        @endif

        @unless($podeEditar)
            <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                Sua conta não tem a função <strong>Documentos da organização</strong>: você vê os arquivos, mas não envia.
            </p>
        @endunless

        @include('arquivos-osc._lista', ['osc' => $osc, 'podeEditar' => $podeEditar])
    </div>
</x-portal-layout>
