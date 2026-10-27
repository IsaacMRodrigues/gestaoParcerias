@php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$manifestacao->status] ?? 'gray'; @endphp

<x-portal-layout>
    <x-slot name="title">Manifestação nº {{ $manifestacao->protocolo }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <x-flash-message />

        <div>
            <p class="text-sm text-gray-500">Manifestação de interesse social (PMIS)</p>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">Protocolo nº {{ $manifestacao->protocolo }}</h1>
            <p class="text-xs text-gray-400 mt-1">Esta página é o seu link pessoal de acompanhamento: guarde o endereço ou o e-mail que o trouxe até aqui.</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div class="col-span-2"><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Proponente</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $manifestacao->nomeDoProponente() }}
                        <span class="text-gray-400">· {{ \App\Models\ManifestacaoInteresse::TIPOS_DE_PESSOA[$manifestacao->proponente_tipo] ?? '' }}</span></dd></div>
                <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Enviada em</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $manifestacao->submetida_em?->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Situação</dt>
                    <dd class="mt-0.5">
                        <span class="inline-block px-2.5 py-1 text-xs font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 border border-{{ $cor }}-200 rounded-md">
                            {{ $manifestacao->statusLabel() }}
                        </span>
                    </dd></div>
                <div class="col-span-2 sm:col-span-4"><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Secretaria</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $manifestacao->orgao?->name }}</dd></div>
            </dl>

            <div class="pt-5 border-t border-gray-100">
                @include('manifestacao-publica._textos', ['manifestacao' => $manifestacao])
            </div>

            @if($manifestacao->arquivo_path)
                <div class="pt-5 border-t border-gray-100">
                    <a href="{{ route('manifestacao.acompanhar.arquivo', $manifestacao->token) }}" class="btn btn-outline btn-sm">
                        Baixar a proposta enviada ({{ $manifestacao->arquivo_nome }})
                    </a>
                </div>
            @endif
        </div>

        @if($manifestacao->decidida())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800">Decisão</h2>
                <p class="mt-1 text-sm font-semibold {{ $manifestacao->status === 'deferida' ? 'text-brand-700' : 'text-red-700' }}">
                    {{ $manifestacao->statusLabel() }} em {{ $manifestacao->decidida_em?->format('d/m/Y') }}
                </p>
                @if($manifestacao->decisao_motivo)
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $manifestacao->decisao_motivo }}</p>
                @endif
                @if($manifestacao->status === 'deferida')
                    <p class="text-xs text-gray-500 mt-2">
                        Acatar a manifestação não obriga a abertura de chamamento público (art. 21 da Lei nº 13.019/2014).
                    </p>
                @endif
            </div>
        @else
            <p class="text-sm text-gray-600">
                A Secretaria analisa a proposta e o Setor de Convênios e Parcerias decide se ela é acatada.
                Você recebe a decisão por e-mail, em {{ $manifestacao->proponente_email }}.
            </p>
        @endif
    </div>
</x-portal-layout>
