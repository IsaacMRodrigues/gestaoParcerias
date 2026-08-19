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

    // Antes contava só Processo::where('setor_atual', ...), então a faixa servia
    // apenas aos quatro setores do Planejamento e mentia para todos os demais:
    // quem tinha trabalho parado na Seleção ou na Celebração lia "nenhum
    // processo aguardando". Agora vem dos três trâmites.
    $minhaCaixa = $u->setor ? \App\Support\CaixaDeEntrada::para($u) : null;

    $chamamentosTotal   = Chamamento::count();
    $chamamentosAbertos = Chamamento::whereIn('status', ['publicado', 'em_inscricao'])->count();

    $propostasTotal = Proposta::count();
    // Contava só 'em_analise', então a proposta recém-submetida — o trabalho
    // que mais espera alguém — ficava fora do número e o card marcava zero com
    // proposta parada na fila. As duas situações são pendência da UG.
    $propostasAnalise = Proposta::whereIn('status', ['submetida', 'em_analise'])->count();

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

            {{-- Caixa de entrada do setor.
                 A cor conta o estado, em vez de gritar sempre igual: laranja
                 (pendência que espera por você) só quando há processo parado;
                 sem fila, a faixa recua para um aviso branco e discreto. Antes
                 era o mesmo bloco verde-vivo dizendo "0 processos aguardando" —
                 destaque máximo para a ausência de trabalho, e mais uma mancha
                 verde ao lado de uma sidebar já verde. --}}
            @if(!is_null($minhaCaixa))
                @if($minhaCaixa->total() > 0)
                    <a href="{{ route('caixa') }}"
                       class="flex items-center justify-between gap-4 p-5 rounded-xl bg-gradient-to-r from-accent-500 to-accent-600
                              text-white shadow-sm hover:from-accent-600 hover:to-accent-700 transition">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm text-accent-50">Caixa de Entrada · {{ $u->setorLabel() }}</p>
                                <p class="text-lg font-semibold">
                                    {{ $minhaCaixa->total() }}
                                    {{ $minhaCaixa->total() === 1 ? 'item aguardando você' : 'itens aguardando você' }}
                                </p>
                                {{-- Diz de quais trâmites vêm, senão o número sozinho
                                     não indica onde procurar. --}}
                                <p class="text-[12px] text-accent-50/90 mt-0.5">
                                    {{ collect($minhaCaixa->porTramite())->map(fn ($n, $t) => "{$n} em {$t}")->implode(' · ') }}
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold bg-white/20 px-3 py-1.5 rounded-lg hidden sm:inline shrink-0">Abrir caixa →</span>
                    </a>
                @else
                    <a href="{{ route('caixa') }}"
                       class="flex items-center justify-between gap-4 p-5 rounded-xl bg-white border border-gray-200
                              hover:border-gray-300 hover:shadow-sm transition">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm text-gray-500">Caixa de Entrada · {{ $u->setorLabel() }}</p>
                                <p class="text-lg font-semibold text-gray-900">Nada aguardando você</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-500 hidden sm:inline shrink-0">Ver caixa →</span>
                    </a>
                @endif
            @endif

            {{-- Cards de métricas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- A cor diz o estado da métrica, não o módulo — é o que a paleta
                     da Prefeitura (verde, laranja, cinzas) permite dizer, e é a
                     informação mais útil: laranja é trabalho parado esperando
                     alguém, verde é o que está rodando, cinza é cadastro que só
                     se consulta. A fileira separa "preciso agir" de "está bem". --}}
                @can('planejamento')
                    <x-stat-card label="Processos em trâmite" icon="processos" :value="$processosTramite" :sub="$processosTotal.' no total'"
                                 color="accent" :href="route('processos.index')" />
                @endcan

                @can('chamamentos')
                    <x-stat-card label="Chamamentos abertos" icon="chamamentos" :value="$chamamentosAbertos" :sub="$chamamentosTotal.' cadastrados'"
                                 color="brand" :href="route('programas.index')" />
                @endcan

                @can('propostas')
                    <x-stat-card label="Propostas a analisar" icon="propostas" :value="$propostasAnalise" :sub="$propostasTotal.' no total'"
                                 color="accent" :href="route('propostas.index')" />
                @endcan

                @can('formalizacao')
                    <x-stat-card label="Instrumentos vigentes" icon="instrumentos" :value="$instrumentosVigentes" :sub="$instrumentosTotal.' no total'"
                                 color="brand" :href="route('instrumentos.index')" />
                @endcan

                @can('cadastros')
                    <x-stat-card label="Órgãos / Secretarias" icon="orgaos" :value="Orgao::count()" sub="Unidades Gestoras"
                                 color="slate" :href="route('orgaos.index')" />
                    <x-stat-card label="OSCs cadastradas" icon="oscs" :value="Osc::count()" sub="Organizações da Sociedade Civil"
                                 color="slate" :href="route('oscs.index')" />
                @endcan
            </div>

            {{-- Atalhos rápidos --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Atalhos rápidos</h3>
                <div class="flex flex-wrap gap-3">
                    {{-- "Novo Processo" é o único que cria algo; os demais só levam a
                         uma listagem. O laranja marca essa diferença. --}}
                    @can('planejamento')
                        <x-quick-link :href="route('processos.create')" label="Novo Processo" color="accent" />
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
