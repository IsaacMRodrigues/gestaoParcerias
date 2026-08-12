<x-portal-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if($doc)
            <div class="bg-white rounded-xl shadow-sm border border-green-200 p-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">✅</span>
                    <div>
                        <h1 class="text-xl font-bold text-green-800">Documento autêntico</h1>
                        <p class="text-sm text-gray-500">Assinatura eletrônica verificada com sucesso.</p>
                    </div>
                </div>

                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Documento</dt>
                        <dd class="text-gray-900 font-medium text-right">{{ $doc['tipo'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">{{ $doc['ref_label'] }}</dt>
                        <dd class="text-gray-900 text-right">{{ $doc['ref'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">{{ $doc['extra_label'] }}</dt>
                        <dd class="text-gray-900 text-right">{{ $doc['extra'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Assinado por</dt>
                        <dd class="text-gray-900 font-medium text-right">{{ $doc['assinante'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Data da assinatura</dt>
                        <dd class="text-gray-900 text-right">{{ $doc['assinado_em']->format('d/m/Y \à\s H:i') }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Código de validação</dt>
                        <dd class="text-gray-900 font-mono text-right">{{ $doc['codigo'] }}</dd>
                    </div>
                </dl>

                @if(!empty($doc['conteudo']))
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-700 mb-3">Cópia do documento</h2>
                        <div class="documento-copia border border-gray-200 rounded-lg bg-white p-6 sm:p-8 overflow-x-auto text-[14px] text-gray-900 leading-relaxed">
                            {!! $doc['conteudo'] !!}
                        </div>
                        <p class="mt-3 text-xs text-gray-400">
                            Cópia fiel do documento assinado eletronicamente (código {{ $doc['codigo'] }}).
                        </p>
                    </div>
                @endif

                <a href="{{ route('validacao.index') }}" class="inline-block mt-6 text-sm text-brand-600 hover:underline">
                    ← Validar outro documento
                </a>
            </div>

            <style>
                .documento-copia p { margin: .4rem 0; line-height: 1.6; text-align: justify; }
                .documento-copia ul, .documento-copia ol { padding-left: 1.5rem; }
                .documento-copia img { max-width: 120px; height: auto; }
                .documento-copia strong { font-weight: 700; }
                .documento-copia table { border-collapse: collapse; }
                .documento-copia table:not([style*="border:none"]) { width: 100%; }
                .documento-copia th, .documento-copia td { border: 1px solid #555; padding: 4px 8px; font-size: 12px; }
                .documento-copia table[style*="border:none"] td { border: none; }
            </style>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-red-200 p-8 text-center">
                <span class="text-3xl">⚠️</span>
                <h1 class="text-xl font-bold text-red-800 mt-2">Documento não encontrado</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Nenhum documento assinado corresponde ao código
                    <strong class="font-mono">{{ $codigo }}</strong>.
                    Confira o código e tente novamente.
                </p>
                <a href="{{ route('validacao.index') }}"
                   class="inline-block mt-6 px-5 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700">
                    Tentar novamente
                </a>
            </div>
        @endif
    </div>
</x-portal-layout>
