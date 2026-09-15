@php
    // Dois blocos. No chamamento público a OSC concorre com outras; fora dele
    // a iniciativa é dela — manifesta interesse, e o Município decide se a
    // parceria segue por dispensa ou inexigibilidade. Eram três blocos, mas
    // manifestação e dispensa/inexigibilidade são o mesmo caminho visto de
    // lados diferentes ("para a UG é DI e para a OSC é MI"), e separá-los
    // obrigava a OSC a procurar em dois lugares a mesma iniciativa.
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
                    <a href="{{ route('portal.manifestacoes.create') }}" class="btn btn-primary">+ Manifestar interesse</a>
                @endcan
            </div>
        </div>

        <x-flash-message />

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
                    <p class="text-sm text-gray-500">Nenhuma proposta em chamamento público.</p>
                    <a href="{{ route('portal.index') }}" class="text-sm font-semibold text-brand-700 hover:underline mt-1 inline-block">
                        Ver chamamentos abertos →
                    </a>
                </div>
            @endforelse
        </section>

        {{-- 2. Manifestação de interesse / dispensa e inexigibilidade --}}
        <section>
            <div class="flex items-center gap-3 mb-3">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                    Manifestação de interesse · Dispensa e inexigibilidade
                </h2>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                    {{ $emDispensa->count() + $manifestacoes->count() }}
                </span>
            </div>

            @if($emDispensa->isEmpty() && $manifestacoes->isEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-8 text-center">
                    <p class="text-sm text-gray-500">
                        Nada por aqui. É pela manifestação de interesse que se propõe uma parceria quando não
                        há chamamento aberto — e é dela que nascem as parcerias por dispensa ou inexigibilidade.
                    </p>
                    @can('osc_manifestacoes')
                        <a href="{{ route('portal.manifestacoes.create') }}"
                           class="text-sm font-semibold text-brand-700 hover:underline mt-1 inline-block">
                            Manifestar interesse →
                        </a>
                    @endcan
                </div>
            @else
                <div class="space-y-3">
                    {{-- Primeiro o que já virou parceria, depois o que ainda está em análise. --}}
                    @foreach($emDispensa as $proposta)
                        @include('portal._card-proposta', ['proposta' => $proposta, 'cor' => $corProposta($proposta)])
                    @endforeach

                    @if($manifestacoes->isNotEmpty())
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
                            @foreach($manifestacoes as $m)
                                @php $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$m->status] ?? 'gray'; @endphp
                                <a href="{{ route('portal.manifestacoes.show', $m) }}"
                                   class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Manifestação de interesse</span>
                                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">{{ $m->titulo }}</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            {{ $m->orgao->name }} · R$ {{ number_format($m->valor_solicitado, 2, ',', '.') }}
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
    </div>
</x-portal-layout>
