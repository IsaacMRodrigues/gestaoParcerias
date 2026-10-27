@php
    // Três blocos: chamamento público (a OSC concorre), manifestação de interesse e Nova Proposta.
    $corProposta = fn ($p) => \App\Models\Proposta::STATUS_COLORS[$p->status] ?? 'gray';
@endphp

<x-portal-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Minhas inscrições</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $osc->name }} — CNPJ: {{ $osc->cnpj }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('portal.index') }}" class="btn btn-outline">Chamamentos abertos</a>
                @can('osc_manifestacoes')
                    <a href="{{ route('manifestacao.publica.create') }}" class="btn btn-primary">+ Manifestar interesse</a>
                @endcan
            </div>
        </div>

        <x-flash-message />

        <div class="space-y-2">
            <x-campo-pesquisa :action="route('portal.minhas-propostas')"
                              placeholder="Pesquisar por título, objeto, nº do chamamento, protocolo ou Secretaria" />
            @if($busca !== '')
                <p class="text-sm text-gray-500">
                    {{ $emChamamento->count() + $emDispensa->count() + $manifestacoes->count() }} resultado(s) para <strong>"{{ $busca }}"</strong>.
                </p>
            @endif
        </div>

        {{-- 1. Chamamento público --}}
        <section>
            <div class="flex items-center gap-3 mb-3">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Chamamentos públicos</h2>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $emChamamento->count() }}</span>
            </div>

            @forelse($emChamamento as $proposta)
                @include('portal._card-proposta', ['proposta' => $proposta, 'cor' => $corProposta($proposta)])
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-8 text-center">
                    <p class="text-sm text-gray-500">{{ $busca !== '' ? 'Nenhuma proposta em chamamento público com essa pesquisa.' : 'Nenhuma proposta em chamamento público.' }}</p>
                    <a href="{{ route('portal.index') }}" class="text-sm font-semibold text-brand-700 hover:underline mt-1 inline-block">
                        Ver chamamentos abertos →
                    </a>
                </div>
            @endforelse
        </section>

        {{-- 2 e 3. Manifestações de interesse e Novas Propostas, cada uma no seu bloco; a parceria por dispensa ou
             inexigibilidade aparece no bloco de onde nasceu. --}}
        @php
            $textos = [
                'manifestacao' => [
                    'titulo' => 'Manifestações de interesse',
                    'vazio'  => 'Nada por aqui. É pela manifestação de interesse que se propõe uma parceria quando não há chamamento aberto.',
                    'link'   => ['manifestacao.publica.create', 'Manifestar interesse →'],
                ],
                'proposta' => [
                    'titulo' => 'Novas Propostas · Dispensa e inexigibilidade',
                    'vazio'  => 'Nada por aqui. A Nova Proposta é o pedido de parceria por dispensa ou inexigibilidade, sem chamamento.',
                    'link'   => ['portal.novas-propostas.create', 'Nova Proposta →'],
                ],
            ];
        @endphp
        @foreach($blocos as $tipo => $bloco)
            @php $total = $bloco['propostas']->count() + $bloco['manifestacoes']->count(); @endphp
            <section>
                <div class="flex items-center gap-3 mb-3">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ $textos[$tipo]['titulo'] }}</h2>
                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $total }}</span>
                </div>

                @if($total === 0 && $busca !== '')
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">Nada encontrado com essa pesquisa.</p>
                    </div>
                @elseif($total === 0)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">{{ $textos[$tipo]['vazio'] }}</p>
                        @can('osc_manifestacoes')
                            <a href="{{ route($textos[$tipo]['link'][0]) }}"
                               class="text-sm font-semibold text-brand-700 hover:underline mt-1 inline-block">
                                {{ $textos[$tipo]['link'][1] }}
                            </a>
                        @endcan
                    </div>
                @else
                    <div class="space-y-3">
                        {{-- Primeiro o que já virou parceria, depois o que ainda está em análise. --}}
                        @foreach($bloco['propostas'] as $proposta)
                            @include('portal._card-proposta', ['proposta' => $proposta, 'cor' => $corProposta($proposta)])
                        @endforeach

                        @if($bloco['manifestacoes']->isNotEmpty())
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
                                @foreach($bloco['manifestacoes'] as $m)
                                    @php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$m->status] ?? 'gray'; @endphp
                                    <a href="{{ route('portal.manifestacoes.show', $m) }}"
                                       class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ \App\Models\ManifestacaoInteresse::TIPOS[$m->tipo] ?? 'Manifestação de interesse' }}</span>
                                            <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $m->tituloParaExibir() }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">
                                                {{ $m->orgao?->name ?? 'Secretaria a definir pela SCP' }}
                                                @unless($m->ehPmis())· R$ {{ number_format($m->valor_solicitado, 2, ',', '.') }}@endunless
                                            </span>
                                            @if($m->status === 'deferida')
                                                <span class="block text-xs text-brand-700 mt-1">
                                                    Deferida como {{ \App\Models\ManifestacaoInteresse::ENCAMINHAMENTOS[$m->decisao] ?? $m->decisao }}
                                                </span>
                                            @elseif($m->status === 'indeferida')
                                                <span class="block text-xs text-red-700 mt-1">Indeferida — abra para ler o motivo</span>
                                            @endif
                                        </span>
                                        <span class="shrink-0 text-right">
                                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                                                {{ $m->statusLabel() }}
                                            </span>
                                            <span class="block text-xs text-gray-400 mt-1">{{ $m->created_at->format('d/m/Y') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</x-portal-layout>
