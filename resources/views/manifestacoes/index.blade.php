<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manifestações de Interesse</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Propostas apresentadas por OSCs sem chamamento aberto — a SCP ouve a Secretaria e
                decide o encaminhamento.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="flex flex-wrap gap-2">
                @php $filtro = request('status'); @endphp
                <a href="{{ route('manifestacoes.index') }}"
                   class="px-2.5 py-1 text-xs font-semibold rounded-md ring-1 {{ $filtro ? 'bg-white text-gray-600 ring-gray-200' : 'bg-slate-100 text-slate-800 ring-slate-200' }}">
                    Todas
                </a>
                @foreach(['submetida', 'em_analise', 'analisada', 'deferida', 'indeferida'] as $st)
                    <a href="{{ route('manifestacoes.index', ['status' => $st]) }}"
                       class="px-2.5 py-1 text-xs font-semibold rounded-md ring-1 {{ $filtro === $st ? 'bg-slate-100 text-slate-800 ring-slate-200' : 'bg-white text-gray-600 ring-gray-200' }}">
                        {{ \App\Models\ManifestacaoInteresse::STATUS[$st] }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
                @forelse($manifestacoes as $m)
                    @php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$m->status] ?? 'gray'; @endphp
                    <a href="{{ route('manifestacoes.show', $m) }}"
                       class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $m->titulo }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                {{ $m->osc->name }} · {{ $m->orgao->sigla ?: $m->orgao->name }}
                                · R$ {{ number_format($m->valor_solicitado, 2, ',', '.') }}
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                                {{ $m->statusLabel() }}
                            </span>
                            <span class="block text-xs text-gray-400 mt-1">
                                {{ $m->submetida_em?->diffForHumans() ?? $m->created_at->diffForHumans() }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="px-6 py-14">
                        <x-empty-state icone="pasta">Nenhuma manifestação de interesse recebida.</x-empty-state>
                    </div>
                @endforelse
            </div>

            {{ $manifestacoes->links() }}
        </div>
    </div>
</x-app-layout>
