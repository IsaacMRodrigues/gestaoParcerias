<x-portal-layout>
    <x-slot name="title">Manifestações de interesse</x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manifestações de interesse recebidas</h1>
                <p class="text-sm text-gray-500 mt-1 max-w-2xl">
                    Propostas apresentadas pela sociedade civil pelo Procedimento de Manifestação de Interesse Social
                    (PMIS), tornadas públicas como prevê o art. 20 da Lei nº 13.019/2014.
                </p>
            </div>
            <a href="{{ route('manifestacao.publica.create') }}" class="btn btn-primary shrink-0">Manifestar interesse</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
            @forelse($manifestacoes as $m)
                @php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$m->status] ?? 'gray'; @endphp
                <a href="{{ route('manifestacao.publica.show', explode('/', $m->protocolo)) }}"
                   class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">
                            Protocolo nº {{ $m->protocolo }} — {{ $m->orgao?->name }}
                        </span>
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $m->nomeDoProponente() }}</span>
                        <span class="block text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($m->interesse_publico, 220) }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                            {{ $m->statusLabel() }}
                        </span>
                        <span class="block text-xs text-gray-400 mt-1">{{ $m->submetida_em?->format('d/m/Y') }}</span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-14">
                    <x-empty-state icone="pasta">Nenhuma manifestação de interesse recebida até agora.</x-empty-state>
                </div>
            @endforelse
        </div>

        {{ $manifestacoes->links() }}
    </div>
</x-portal-layout>
