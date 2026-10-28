@use('App\Support\HoraLegal')

<x-portal-layout>
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if($emitido)
            <div class="bg-white rounded-xl shadow-sm border border-brand-200 p-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">✅</span>
                    <div>
                        <h1 class="text-xl font-bold text-brand-800">Arquivo autêntico e intacto</h1>
                        <p class="text-sm text-gray-500">Este PDF foi gerado por este sistema e não mudou desde então.</p>
                    </div>
                </div>

                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Documento</dt>
                        <dd class="text-gray-900 font-medium text-right">{{ $emitido->documento_rotulo }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Gerado em</dt>
                        <dd class="text-gray-900 text-right">{{ HoraLegal::emBrasilia($emitido->emitido_em)->format('d/m/Y \à\s H:i') }} <span class="text-gray-500">(Brasília)</span></dd>
                    </div>
                    <div class="py-2">
                        <dt class="text-gray-500">Assinaturas do arquivo</dt>
                        <dd class="mt-1 space-y-1">
                            @foreach($codigos as $c)
                                <p>
                                    <a href="{{ route('validacao.mostrar', $c['codigo']) }}" class="font-mono text-brand-700 hover:underline">{{ $c['codigo'] }}</a>
                                    @if($c['anulada'])
                                        <span class="text-xs text-accent-800">— anulada em {{ HoraLegal::emBrasilia($c['anulada']->instante())->format('d/m/Y') }}
                                            (documento devolvido para correção depois deste arquivo)</span>
                                    @endif
                                </p>
                            @endforeach
                        </dd>
                    </div>
                    <div class="py-2">
                        <dt class="text-gray-500">Código do arquivo (SHA-256)</dt>
                        <dd class="mt-1 font-mono text-xs text-gray-900 break-all">{{ $hash }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-gray-500">Abra cada código acima para ver quem assinou, quando e a trilha de auditoria.</p>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-red-200 p-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">⚠️</span>
                    <div>
                        <h1 class="text-xl font-bold text-red-800">Arquivo não reconhecido</h1>
                        <p class="text-sm text-gray-600">{{ $nome }} não é nenhum dos PDFs gerados por este sistema.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-700">Pode ser que:</p>
                <ul class="mt-1 ml-5 list-disc text-sm text-gray-700 space-y-1">
                    <li>o arquivo tenha sido alterado depois de baixado;</li>
                    <li>tenha sido gerado de outra forma (impressão do navegador, digitalização, outro programa);</li>
                    <li>seja de um documento ainda sem assinatura.</li>
                </ul>
                <p class="mt-4 text-sm text-gray-700">Confira pelo <strong>código de validação</strong> impresso no documento: a
                    página do código mostra o texto assinado para comparar.</p>
                <p class="mt-4 text-xs text-gray-500">Código do arquivo enviado (SHA-256): <span class="font-mono break-all">{{ $hash }}</span></p>
            </div>
        @endif

        <a href="{{ route('validacao.index') }}" class="inline-block mt-6 text-sm text-brand-600 hover:underline">← Validar outro documento</a>
    </div>
</x-portal-layout>
