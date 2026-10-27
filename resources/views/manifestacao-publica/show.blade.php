@php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$manifestacao->status] ?? 'gray'; @endphp

<x-portal-layout>
    <x-slot name="title">Manifestação nº {{ $manifestacao->protocolo }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('manifestacao.publica.index') }}" class="hover:underline">Manifestações de interesse</a>
            </p>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">Protocolo nº {{ $manifestacao->protocolo }}</h1>
        </div>

        {{-- Público: os textos da proposta. O e-mail e o PDF, que traz o documento de identidade de quem assina, não. --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div class="col-span-2"><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Proponente</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $manifestacao->nomeDoProponente() }}</dd></div>
                <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Recebida em</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $manifestacao->submetida_em?->format('d/m/Y') }}</dd></div>
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

            @if($manifestacao->decidida() && $manifestacao->decisao_motivo)
                <div class="pt-5 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Decisão — {{ $manifestacao->statusLabel() }}</p>
                    <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $manifestacao->decisao_motivo }}</p>
                </div>
            @endif
        </div>
    </div>
</x-portal-layout>
