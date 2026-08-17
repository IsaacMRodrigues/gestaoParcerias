@php
    $navPropostasNovas = auth()->user()->can('propostas')
        ? \App\Models\Proposta::visiveisPara(auth()->user())->where('status', 'submetida')->count()
        : 0;
    $navPendentes = auth()->user()->can('cadastros') ? \App\Models\User::pendentes()->count() : 0;
    // Atravessa os três trâmites, então é item próprio do menu — não subitem do
    // Planejamento, como era quando só contava processos.
    $navCaixa = \App\Support\CaixaDeEntrada::para(auth()->user())->total();

    // Sidebar em cinza-ardósia escuro, não em verde.
    //
    // Ela ocupa 256px de altura inteira em toda tela do sistema: pintada com o
    // verde da marca, virava a maior mancha de cor da interface e puxava tudo
    // para o mesmo tom — o item ativo (branco sobre verde) mal se distinguia
    // dos demais. Neutra, a coluna recua para o papel de moldura e o verde
    // ganha uma função só: marcar onde o usuário está.
    $sec   = 'px-3 pt-5 pb-1.5 text-[12px] font-semibold uppercase tracking-wider text-slate-500';
    $link  = 'relative flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-white/5 hover:text-white transition';
    // Verde sólido = a página aberta agora. Item de seção (o pai de quem está
    // aberto) recebe só um realce discreto: antes os dois ganhavam o mesmo
    // destaque, viravam um bloco verde de duas linhas e não diziam em qual das
    // duas telas o usuário estava.
    $on      = '!text-white bg-brand-600 font-semibold shadow-sm hover:!bg-brand-600';
    $naSecao = '!text-white bg-white/[0.07] font-medium';
    $soon  = 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 cursor-default';
    $badge = 'ml-auto px-1.5 py-0.5 text-[12px] font-bold bg-accent-500 text-white rounded-full';
    $etapa = 'w-5 h-5 shrink-0 rounded-full border text-[12px] font-bold flex items-center justify-center';
    $etapaOn  = 'border-white bg-white text-brand-700';
    $etapaOff = 'border-slate-600 text-slate-400';
@endphp

<aside class="fixed top-1.5 bottom-0 left-0 z-40 w-64 bg-slate-900 flex flex-col
              transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    {{-- Marca: empilhada porque o logotipo já traz o nome do Município e, lado a
         lado com o título, não cabe nos 256px da coluna (o texto era cortado).
         px-6 alinha o logotipo à mesma margem dos ícones do menu abaixo. --}}
    <div class="px-6 py-5 border-b border-slate-800 shrink-0">
        <a href="{{ route('landing') }}"
           class="block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
            <x-marca variant="branco" class="h-12" />
            <span class="mt-3 block text-sm font-bold text-white leading-tight">Gestão de Parcerias</span>
            <span class="block text-[12px] text-slate-500">Sistema público municipal</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-2">
        <a href="{{ route('dashboard') }}" class="{{ $link }} {{ request()->routeIs('dashboard') ? $on : '' }}">
            <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Painel
        </a>
        {{-- Caixa de entrada: vale para qualquer setor, em qualquer trâmite --}}
        @if(auth()->user()->setor)
            <a href="{{ route('caixa') }}" class="{{ $link }} {{ request()->routeIs('caixa') ? $on : '' }}">
                <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/>
                </svg>
                Caixa de Entrada
                @if($navCaixa > 0)<span class="{{ $badge }}">{{ $navCaixa }}</span>@endif
            </a>
        @endif

        <a href="{{ route('portal.index') }}" class="{{ $link }}">
            <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 010 18 M12 3a15 15 0 000 18"/>
            </svg>
            Portal Público
        </a>

        <p class="{{ $sec }}">Ciclo da Parceria</p>

        {{-- 1. Planejamento --}}
        @can('planejamento')
            @php
                $emPlanejamento = request()->routeIs('processos.*');
                // A listagem é a página do próprio item; as demais rotas de
                // processo (caixa, detalhe) só o marcam como seção.
                $ehPlanejamento = request()->routeIs('processos.index');
            @endphp
            <a href="{{ route('processos.index') }}"
               class="{{ $link }} {{ $ehPlanejamento ? $on : ($emPlanejamento ? $naSecao : '') }}">
                <span class="{{ $etapa }} {{ $emPlanejamento ? $etapaOn : $etapaOff }}">1</span>
                Planejamento
            </a>
        @else
            <span class="{{ $soon }}" title="Seu perfil não tem acesso ao Planejamento."><span class="{{ $etapa }} border-slate-700 text-slate-600">1</span> Planejamento<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
        @endcan

        {{-- 2. Seleção --}}
        @canany(['chamamentos', 'propostas'])
            @php
                $emSelecao = request()->routeIs('programas.*') || request()->routeIs('chamamentos.*') || request()->routeIs('propostas.*');
                // Era um <p>: tinha a aparência exata de um link, mas clicar não
                // fazia nada. Agora leva ao primeiro subitem a que o usuário
                // tem acesso — o @canany acima garante que existe pelo menos um.
                $urlSelecao = auth()->user()->can('chamamentos')
                    ? route('programas.index')
                    : route('propostas.index');
            @endphp
            {{-- Seleção nunca é "a página aberta": ela só encaminha ao primeiro
                 subitem, que é quem recebe o verde sólido. --}}
            <a href="{{ $urlSelecao }}" class="{{ $link }} {{ $emSelecao ? $naSecao : '' }}">
                <span class="{{ $etapa }} {{ $emSelecao ? $etapaOn : $etapaOff }}">2</span>
                Seleção
                @if($navPropostasNovas > 0)<span class="{{ $badge }}">{{ $navPropostasNovas }}</span>@endif
            </a>
            @can('chamamentos')
                <a href="{{ route('programas.index') }}"
                   class="{{ $link }} pl-10 {{ request()->routeIs('programas.*') || request()->routeIs('chamamentos.*') ? $on : '' }}">
                    Programas e Chamamentos
                </a>
            @endcan
            @can('propostas')
                <a href="{{ route('propostas.index') }}"
                   class="{{ $link }} pl-10 {{ request()->routeIs('propostas.*') ? $on : '' }}">
                    Propostas
                    @if($navPropostasNovas > 0)<span class="{{ $badge }}">{{ $navPropostasNovas }}</span>@endif
                </a>
            @endcan
        @else
            <span class="{{ $soon }}" title="Seu perfil não tem acesso à Seleção."><span class="{{ $etapa }} border-slate-700 text-slate-600">2</span> Seleção<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
        @endcanany

        {{-- 3. Celebração --}}
        @can('formalizacao')
            @php $emCelebracao = request()->routeIs('instrumentos.*') || request()->routeIs('celebracao.*'); @endphp
            <a href="{{ route('instrumentos.index') }}" class="{{ $link }} {{ $emCelebracao ? $on : '' }}">
                <span class="{{ $etapa }} {{ $emCelebracao ? $etapaOn : $etapaOff }}">3</span>
                Celebração
            </a>
        @else
            <span class="{{ $soon }}" title="Seu perfil não tem acesso à Celebração."><span class="{{ $etapa }} border-slate-700 text-slate-600">3</span> Celebração<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
        @endcan

        {{-- 4. Execução — lista as parcerias e abre a execução de cada uma --}}
        @can('execucao')
            @php $emExecucao = request()->routeIs('execucao.*') || request()->routeIs('instrumentos.execucao')
                || request()->routeIs('repasses.*') || request()->routeIs('despesas.*'); @endphp
            <a href="{{ route('execucao.index') }}" class="{{ $link }} {{ $emExecucao ? $on : '' }}">
                <span class="{{ $etapa }} {{ $emExecucao ? $etapaOn : $etapaOff }}">4</span>
                Execução
            </a>
        @else
            <span class="{{ $soon }}" title="Repasses, despesas e saldo: abra pela tela do Instrumento.">
                <span class="{{ $etapa }} border-slate-700 text-slate-600">4</span> Execução<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </span>
        @endcan

        <span class="{{ $soon }}" title="Em breve — módulo ainda não construído"><span class="{{ $etapa }} border-slate-700 text-slate-600">5</span> Monitoramento<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
        <span class="{{ $soon }}" title="Em breve — módulo ainda não construído"><span class="{{ $etapa }} border-slate-700 text-slate-600">6</span> Prestação de Contas<svg class="ml-auto w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>

        {{-- Cadastros --}}
        @can('cadastros')
            <p class="{{ $sec }}">Cadastros</p>
            <a href="{{ route('usuarios.index') }}"
               class="{{ $link }} {{ request()->routeIs('usuarios.index') || request()->routeIs('usuarios.create') || request()->routeIs('usuarios.edit') ? $on : '' }}">
                Usuários
            </a>
            <a href="{{ route('orgaos.index') }}" class="{{ $link }} {{ request()->routeIs('orgaos.*') ? $on : '' }}">
                Órgãos / Secretarias
            </a>
            <a href="{{ route('oscs.index') }}" class="{{ $link }} {{ request()->routeIs('oscs.*') ? $on : '' }}">
                OSCs
            </a>
            <a href="{{ route('usuarios.pendentes') }}" class="{{ $link }} {{ request()->routeIs('usuarios.pendentes') ? $on : '' }}">
                Aprovações pendentes
                @if($navPendentes > 0)<span class="{{ $badge }}">{{ $navPendentes }}</span>@endif
            </a>
        @endcan

        @role('responsavel_unidade_gestora')
            <a href="{{ route('subusuarios.index') }}" class="{{ $link }} {{ request()->routeIs('subusuarios.*') ? $on : '' }}">
                Meus usuários
            </a>
        @endrole

        {{-- Apoio do TI --}}
        @role('administrador_setorial')
            <p class="{{ $sec }}">Tecnologia da Informação</p>
            <a href="{{ route('modelos.index') }}" class="{{ $link }} {{ request()->routeIs('modelos.*') ? $on : '' }}">
                <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Modelos
            </a>
        @endrole
    </nav>

    <div class="border-t border-slate-800 px-4 py-3 text-[12px] text-slate-600 shrink-0">
        PGP · {{ now()->year }}
    </div>
</aside>
