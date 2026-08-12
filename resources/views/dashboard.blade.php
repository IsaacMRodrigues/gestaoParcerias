@php
    use App\Models\Processo;
    use App\Models\Chamamento;
    use App\Models\Proposta;
    use App\Models\Instrumento;
    use App\Models\Orgao;
    use App\Models\Osc;

    $u = auth()->user();

    $processosTotal   = Processo::count();
    $processosTramite = Processo::where('status', 'em_tramite')->count();
    $minhaCaixa       = $u->setor
        ? Processo::where('setor_atual', $u->setor)->where('status', 'em_tramite')->count()
        : null;

    $chamamentosTotal   = Chamamento::count();
    $chamamentosAbertos = Chamamento::whereIn('status', ['publicado', 'em_inscricao'])->count();

    $propostasTotal   = Proposta::count();
    $propostasAnalise = Proposta::where('status', 'em_analise')->count();

    $instrumentosTotal    = Instrumento::count();
    $instrumentosVigentes = Instrumento::where('status', 'vigente')->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">Olá, {{ \Illuminate\Support\Str::of($u->name)->explode(' ')->first() }} 👋</h2>
                <p class="text-sm text-gray-500 mt-0.5">Visão geral da Plataforma de Gestão de Parcerias.</p>
            </div>
            {{-- Só a primeira letra sobe: o 'capitalize' do CSS virava
                 "Quarta-Feira, 05 De Agosto De 2026". --}}
            <span class="inline-flex items-center gap-2 text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ \Illuminate\Support\Str::ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Caixa de entrada do setor --}}
            @if(!is_null($minhaCaixa))
                <a href="{{ route('processos.caixa') }}"
                   class="flex items-center justify-between gap-4 p-5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 text-white shadow-sm hover:from-brand-700 hover:to-brand-800 transition">
                    <div class="flex items-center gap-4">
                        <span class="w-12 h-12 rounded-lg bg-white/15 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-brand-100">Caixa de Entrada · {{ $u->setorLabel() }}</p>
                            <p class="text-lg font-semibold">
                                {{ $minhaCaixa }} {{ $minhaCaixa === 1 ? 'processo aguardando' : 'processos aguardando' }}
                            </p>
                        </div>
                    </div>
                    <span class="text-sm font-medium bg-white/15 px-3 py-1.5 rounded-lg hidden sm:inline">Abrir caixa →</span>
                </a>
            @endif

            {{-- Cards de métricas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @can('planejamento')
                    <x-stat-card label="Processos em trâmite" icon="processos" :value="$processosTramite" :sub="$processosTotal.' no total'"
                                 color="brand" :href="route('processos.index')" />
                @endcan

                @can('chamamentos')
                    <x-stat-card label="Chamamentos abertos" icon="chamamentos" :value="$chamamentosAbertos" :sub="$chamamentosTotal.' cadastrados'"
                                 color="emerald" :href="route('programas.index')" />
                @endcan

                @can('propostas')
                    <x-stat-card label="Propostas em análise" icon="propostas" :value="$propostasAnalise" :sub="$propostasTotal.' no total'"
                                 color="amber" :href="route('propostas.index')" />
                @endcan

                @can('formalizacao')
                    <x-stat-card label="Instrumentos vigentes" icon="instrumentos" :value="$instrumentosVigentes" :sub="$instrumentosTotal.' no total'"
                                 color="violet" :href="route('instrumentos.index')" />
                @endcan

                @can('cadastros')
                    <x-stat-card label="Órgãos / Secretarias" icon="orgaos" :value="Orgao::count()" sub="Unidades Gestoras"
                                 color="slate" :href="route('orgaos.index')" />
                    <x-stat-card label="OSCs cadastradas" icon="oscs" :value="Osc::count()" sub="Organizações da Sociedade Civil"
                                 color="sky" :href="route('oscs.index')" />
                @endcan
            </div>

            {{-- Atalhos rápidos --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Atalhos rápidos</h3>
                <div class="flex flex-wrap gap-3">
                    @can('planejamento')
                        <x-quick-link :href="route('processos.create')" label="Novo Processo" />
                    @endcan
                    @can('chamamentos')
                        <x-quick-link :href="route('programas.index')" label="Programas & Chamamentos" />
                    @endcan
                    @can('propostas')
                        <x-quick-link :href="route('propostas.index')" label="Propostas" />
                    @endcan
                    @can('cadastros')
                        <x-quick-link :href="route('usuarios.index')" label="Usuários" />
                    @endcan
                    <x-quick-link :href="route('portal.index')" label="Portal Público" />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
