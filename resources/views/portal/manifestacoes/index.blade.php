<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manifestações de Interesse</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Proponha uma parceria mesmo sem chamamento aberto. O Setor de Convênios e Parcerias
                    analisa, ouve a Secretaria da área e decide o encaminhamento.
                </p>
            </div>
            <a href="{{ route('portal.manifestacoes.create') }}" class="btn btn-primary shrink-0">
                + Nova manifestação
            </a>
        </div>

        <x-flash-message />

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
            @forelse($manifestacoes as $m)
                @php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$m->status] ?? 'gray'; @endphp
                <a href="{{ route('portal.manifestacoes.show', $m) }}"
                   class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $m->titulo }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            {{ $m->orgao->name }} · R$ {{ number_format($m->valor_solicitado, 2, ',', '.') }}
                        </span>
                        @if($m->status === 'indeferida' && $m->decisao_motivo)
                            <span class="block text-xs text-red-700 mt-1">Motivo: {{ $m->decisao_motivo }}</span>
                        @elseif($m->status === 'deferida')
                            <span class="block text-xs text-brand-700 mt-1">
                                Encaminhada como {{ \App\Models\ManifestacaoInteresse::ENCAMINHAMENTOS[$m->decisao] ?? $m->decisao }}
                            </span>
                        @endif
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                            {{ $m->statusLabel() }}
                        </span>
                        <span class="block text-xs text-gray-400 mt-1">{{ $m->created_at->format('d/m/Y') }}</span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-14">
                    <x-empty-state icone="pasta">
                        Nenhuma manifestação ainda — a primeira começa no botão acima.
                    </x-empty-state>
                </div>
            @endforelse
        </div>
    </div>
</x-portal-layout>
