<x-portal-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Minhas Propostas</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $osc->name }} — CNPJ: {{ $osc->cnpj }}</p>
            </div>
            <a href="{{ route('portal.index') }}"
               class="btn btn-outline">
                Ver Chamamentos
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @forelse($propostas as $proposta)
            @php $color = \App\Models\Proposta::STATUS_COLORS[$proposta->status] ?? 'gray'; @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-3 flex items-center justify-between gap-4">
                <div class="flex-1">
                    <p class="text-xs text-gray-400 mb-0.5">
                        {{ $proposta->chamamento->programa->name }} — {{ $proposta->chamamento->titulo }}
                    </p>
                    <p class="font-semibold text-gray-900">{{ $proposta->titulo }}</p>
                    <div class="flex flex-wrap gap-4 mt-1 text-xs text-gray-500">
                        <span>R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</span>
                        @if($proposta->submitted_at)
                            <span>Submetida em {{ $proposta->submitted_at->format('d/m/Y') }}</span>
                        @else
                            <span>Criada em {{ $proposta->created_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                        {{ \App\Models\Proposta::STATUS[$proposta->status] }}
                    </span>
                    @if($proposta->temTramiteCelebracao())
                        <a href="{{ route('celebracao.show', $proposta) }}"
                           class="btn btn-primary btn-sm">
                            Celebração
                            @if($proposta->celebracao_setor === 'osc' && !$proposta->celebracaoConcluida())
                                <span class="ml-1 px-1 py-0.5 bg-accent-300 text-accent-900 rounded">sua vez</span>
                            @endif
                        </a>
                    @endif
                    <a href="{{ route('portal.proposta.show', $proposta) }}"
                       class="text-sm text-brand-600 hover:text-brand-800 font-medium">
                        {{ $proposta->status === 'rascunho' ? 'Continuar' : 'Ver' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-20 text-gray-400">
                <p class="text-lg font-medium">Nenhuma proposta enviada ainda.</p>
                <a href="{{ route('portal.index') }}" class="text-brand-600 hover:underline text-sm mt-2 inline-block">
                    Ver chamamentos disponíveis
                </a>
            </div>
        @endforelse

        @if($propostas->hasPages())
            <div class="mt-4">{{ $propostas->links() }}</div>
        @endif
    </div>
</x-portal-layout>
