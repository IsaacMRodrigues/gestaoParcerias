@php
    $navPropostasNovas = auth()->user()->can('propostas')
        ? \App\Models\Proposta::visiveisPara(auth()->user())->where('status', 'submetida')->count()
        : 0;
    $navPendentes = auth()->user()->can('cadastros') ? \App\Models\User::pendentes()->count() : 0;

    $sec   = 'px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400';
    $link  = 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-brand-50 hover:text-brand-800 transition';
    $on    = 'bg-brand-50 text-brand-800 font-semibold';
    $soon  = 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-300 cursor-default';
    $badge = 'ml-auto px-1.5 py-0.5 text-[11px] font-semibold bg-accent-100 text-accent-800 rounded-full';
    $etapa = 'w-5 h-5 shrink-0 rounded-full border text-[11px] font-bold flex items-center justify-center';
    $etapaOn  = 'border-brand-300 bg-brand-100 text-brand-700';
    $etapaOff = 'border-gray-300 text-gray-400';
@endphp

<aside class="fixed top-1 bottom-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col
              transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    {{-- Marca --}}
    <div class="h-16 flex items-center px-4 border-b border-gray-100 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <x-marca class="h-9" />
            <span class="flex flex-col leading-none min-w-0">
                <span class="font-semibold text-gray-900 text-sm truncate">Gestão de Parcerias</span>
                <span class="text-[11px] text-gray-400 truncate">Sistema público municipal</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-2">
        <a href="{{ route('dashboard') }}" class="{{ $link }} {{ request()->routeIs('dashboard') ? $on : '' }}">
            <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Painel
        </a>
        <a href="{{ route('portal.index') }}" class="{{ $link }}">
            <svg class="shrink-0" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 010 18 M12 3a15 15 0 000 18"/>
            </svg>
            Portal Público
        </a>

        <p class="{{ $sec }}">Ciclo da Parceria</p>

        {{-- 1. Planejamento --}}
        @can('planejamento')
            @php $emPlanejamento = request()->routeIs('processos.*'); @endphp
            <a href="{{ route('processos.index') }}" class="{{ $link }} {{ $emPlanejamento ? $on : '' }}">
                <span class="{{ $etapa }} {{ $emPlanejamento ? $etapaOn : $etapaOff }}">1</span>
                Planejamento
            </a>
            @if(auth()->user()->setor)
                <a href="{{ route('processos.caixa') }}"
                   class="{{ $link }} pl-10 {{ request()->routeIs('processos.caixa') ? $on : '' }}">
                    Caixa de Entrada
                </a>
            @endif
        @else
            <span class="{{ $soon }}"><span class="{{ $etapa }} border-gray-200">1</span> Planejamento</span>
        @endcan

        {{-- 2. Seleção --}}
        @canany(['chamamentos', 'propostas'])
            @php $emSelecao = request()->routeIs('programas.*') || request()->routeIs('chamamentos.*') || request()->routeIs('propostas.*'); @endphp
            <p class="{{ $link }} !cursor-default hover:!bg-transparent hover:!text-gray-600">
                <span class="{{ $etapa }} {{ $emSelecao ? $etapaOn : $etapaOff }}">2</span>
                Seleção
                @if($navPropostasNovas > 0)<span class="{{ $badge }}">{{ $navPropostasNovas }}</span>@endif
            </p>
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
            <span class="{{ $soon }}"><span class="{{ $etapa }} border-gray-200">2</span> Seleção</span>
        @endcanany

        {{-- 3. Celebração --}}
        @can('formalizacao')
            @php $emCelebracao = request()->routeIs('instrumentos.*') || request()->routeIs('celebracao.*'); @endphp
            <a href="{{ route('instrumentos.index') }}" class="{{ $link }} {{ $emCelebracao ? $on : '' }}">
                <span class="{{ $etapa }} {{ $emCelebracao ? $etapaOn : $etapaOff }}">3</span>
                Celebração
            </a>
        @else
            <span class="{{ $soon }}"><span class="{{ $etapa }} border-gray-200">3</span> Celebração</span>
        @endcan

        {{-- 4. Execução — vive dentro de cada Instrumento --}}
        @can('formalizacao')
            <a href="{{ route('instrumentos.index') }}"
               class="{{ $link }} {{ request()->routeIs('instrumentos.execucao') || request()->routeIs('repasses.*') || request()->routeIs('despesas.*') ? $on : '' }}">
                <span class="{{ $etapa }} {{ $etapaOff }}">4</span>
                Execução
            </a>
        @else
            <span class="{{ $soon }}" title="Repasses, despesas e saldo: abra pela tela do Instrumento.">
                <span class="{{ $etapa }} border-gray-200">4</span> Execução
            </span>
        @endcan

        <span class="{{ $soon }}" title="Em breve"><span class="{{ $etapa }} border-gray-200">5</span> Monitoramento</span>
        <span class="{{ $soon }}" title="Em breve"><span class="{{ $etapa }} border-gray-200">6</span> Prestação de Contas</span>

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
    </nav>

    <div class="border-t border-gray-100 px-4 py-3 text-[11px] text-gray-400 shrink-0">
        PGP · {{ now()->year }}
    </div>
</aside>
