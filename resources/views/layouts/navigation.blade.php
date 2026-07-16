@php
    $navUser = auth()->user();
    $navRole = $navUser?->roles->first()?->name;
    $navRoleLabel = $navRole ? (\App\Models\User::$roleLabels[$navRole] ?? \Illuminate\Support\Str::headline($navRole)) : null;
    $navInitials = \Illuminate\Support\Str::of($navUser?->name ?? '?')
        ->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                        <span class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-black text-sm shadow-sm">PGP</span>
                        <span class="hidden lg:flex flex-col leading-none">
                            <span class="font-semibold text-gray-900 text-sm">Gestão de Parcerias</span>
                            <span class="text-[11px] text-gray-400">Sistema público municipal</span>
                        </span>
                    </a>
                </div>

                @php
                    $navItem = 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';
                    $navPropostasNovas = auth()->user()->can('propostas')
                        ? \App\Models\Proposta::visiveisPara(auth()->user())->where('status', 'submetida')->count()
                        : 0;
                @endphp

                {{-- Navegação por etapa do ciclo da parceria:
                     Planejamento → Seleção → Celebração → Execução → Monitoramento → Prestação de Contas --}}
                <div class="hidden lg:flex lg:-my-px lg:ms-6 xl:ms-8 space-x-3 xl:space-x-5">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Painel
                    </x-nav-link>
                    <x-nav-link :href="route('portal.index')" :active="false">
                        Portal Público
                    </x-nav-link>

                    {{-- 1. Planejamento --}}
                    @can('planejamento')
                    <x-nav-dropdown label="Planejamento"
                                    :active="request()->routeIs('processos.*')">
                        <a href="{{ route('processos.index') }}" class="{{ $navItem }}">Processos</a>
                        @if(auth()->user()->setor)
                            <a href="{{ route('processos.caixa') }}" class="{{ $navItem }}">Caixa de Entrada</a>
                        @endif
                    </x-nav-dropdown>
                    @endcan

                    {{-- 2. Seleção --}}
                    @canany(['chamamentos', 'propostas'])
                    <x-nav-dropdown label="Seleção"
                                    :active="request()->routeIs('programas.*') || request()->routeIs('chamamentos.*') || request()->routeIs('propostas.*') || request()->routeIs('metas.*') || request()->routeIs('etapas.*')">
                        <x-slot name="badge">
                            @if($navPropostasNovas > 0)
                                <span class="ml-1 px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPropostasNovas }}</span>
                            @endif
                        </x-slot>
                        @can('chamamentos')
                            <a href="{{ route('programas.index') }}" class="{{ $navItem }}">Programas e Chamamentos</a>
                        @endcan
                        @can('propostas')
                            <a href="{{ route('propostas.index') }}"
                               class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span>Propostas</span>
                                @if($navPropostasNovas > 0)
                                    <span class="px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPropostasNovas }}</span>
                                @endif
                            </a>
                        @endcan
                    </x-nav-dropdown>
                    @endcanany

                    {{-- 3. Celebração --}}
                    @can('formalizacao')
                    <x-nav-dropdown label="Celebração" :active="request()->routeIs('instrumentos.*')">
                        <a href="{{ route('instrumentos.index') }}" class="{{ $navItem }}">Instrumentos / Termos</a>
                    </x-nav-dropdown>
                    @endcan

                    {{-- 4. Execução — existe, mas dentro de cada Instrumento --}}
                    @can('execucao')
                    <x-nav-soon label="Execução" hint="Abra pela tela do Instrumento (repasses, despesas e saldo)." />
                    @endcan

                    {{-- 5 e 6. Etapas ainda não implementadas --}}
                    @can('monitoramento')
                    <x-nav-soon label="Monitoramento" hint="Em breve" />
                    @endcan
                    @can('prestacao_contas')
                    <x-nav-soon label="Prestação de Contas" hint="Em breve" />
                    @endcan

                    {{-- Cadastros (transversal, não é etapa) --}}
                    @can('cadastros')
                    @php $navPendentes = \App\Models\User::pendentes()->count(); @endphp
                    <x-nav-dropdown label="Cadastros"
                                    :active="request()->routeIs('usuarios.*') || request()->routeIs('orgaos.*') || request()->routeIs('oscs.*')">
                        <x-slot name="badge">
                            @if($navPendentes > 0)
                                <span class="ml-1 px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPendentes }}</span>
                            @endif
                        </x-slot>
                        <a href="{{ route('usuarios.index') }}" class="{{ $navItem }}">Usuários</a>
                        <a href="{{ route('orgaos.index') }}" class="{{ $navItem }}">Órgãos / Secretarias</a>
                        <a href="{{ route('oscs.index') }}" class="{{ $navItem }}">OSCs</a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('usuarios.pendentes') }}"
                           class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span>Aprovações pendentes</span>
                            @if($navPendentes > 0)
                                <span class="px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPendentes }}</span>
                            @endif
                        </a>
                    </x-nav-dropdown>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-4">
                <x-dropdown align="right" width="64">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 border border-gray-200 rounded-full text-sm font-medium text-gray-600 bg-white hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ $navInitials }}</span>
                            <span class="flex flex-col items-start leading-none">
                                <span class="text-gray-900 font-semibold text-[13px]">{{ Auth::user()->name }}</span>
                                @if($navRoleLabel)
                                    <span class="hidden xl:block text-[11px] text-gray-400">{{ $navRoleLabel }}</span>
                                @endif
                            </span>
                            <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                            @if($navUser?->setor)
                                <div class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">
                                    {{ $navUser->setorLabel() }}
                                </div>
                            @endif
                        </div>
                        @role('responsavel_unidade_gestora')
                        <x-dropdown-link :href="route('subusuarios.index')">
                            Meus usuários
                        </x-dropdown-link>
                        @endrole
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                    class="text-red-600">
                                {{ __('Sair') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        @php $navSecao = 'px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400'; @endphp
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Painel
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('portal.index')" :active="false">
                Portal Público
            </x-responsive-nav-link>

            @can('planejamento')
            <p class="{{ $navSecao }}">1 · Planejamento</p>
            <x-responsive-nav-link :href="route('processos.index')" :active="request()->routeIs('processos.index')">
                Processos
            </x-responsive-nav-link>
            @if(auth()->user()->setor)
                <x-responsive-nav-link :href="route('processos.caixa')" :active="request()->routeIs('processos.caixa')">
                    Caixa de Entrada
                </x-responsive-nav-link>
            @endif
            @endcan

            @canany(['chamamentos', 'propostas'])
            <p class="{{ $navSecao }}">2 · Seleção</p>
            @can('chamamentos')
            <x-responsive-nav-link :href="route('programas.index')" :active="request()->routeIs('programas.*')">
                Programas e Chamamentos
            </x-responsive-nav-link>
            @endcan
            @can('propostas')
            <x-responsive-nav-link :href="route('propostas.index')" :active="request()->routeIs('propostas.*')">
                Propostas
                @if($navPropostasNovas > 0)
                    <span class="ml-1 px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPropostasNovas }}</span>
                @endif
            </x-responsive-nav-link>
            @endcan
            @endcanany
            @can('formalizacao')
            <p class="{{ $navSecao }}">3 · Celebração</p>
            <x-responsive-nav-link :href="route('instrumentos.index')" :active="request()->routeIs('instrumentos.*')">
                Instrumentos / Termos
            </x-responsive-nav-link>
            @endcan

            @can('execucao')
            <p class="{{ $navSecao }}">4 · Execução</p>
            <p class="px-4 py-2 text-sm text-gray-400">Abra pela tela do Instrumento.</p>
            @endcan

            @canany(['monitoramento', 'prestacao_contas'])
            <p class="{{ $navSecao }}">Próximas etapas</p>
            @can('monitoramento')
                <p class="px-4 py-1 text-sm text-gray-400">Monitoramento / Avaliação — em breve</p>
            @endcan
            @can('prestacao_contas')
                <p class="px-4 py-1 text-sm text-gray-400">Prestação de Contas — em breve</p>
            @endcan
            @endcanany

            @can('cadastros')
            <p class="{{ $navSecao }}">Cadastros</p>
            <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                Usuários
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('orgaos.index')" :active="request()->routeIs('orgaos.*')">
                Órgãos / Secretarias
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('oscs.index')" :active="request()->routeIs('oscs.*')">
                OSCs
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('usuarios.pendentes')" :active="request()->routeIs('usuarios.pendentes')">
                Aprovações pendentes
                @if($navPendentes ?? 0)
                    <span class="ml-1 px-1.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-700 rounded-full">{{ $navPendentes }}</span>
                @endif
            </x-responsive-nav-link>
            @endcan

            @role('responsavel_unidade_gestora')
            <x-responsive-nav-link :href="route('subusuarios.index')" :active="request()->routeIs('subusuarios.*')">
                Meus usuários
            </x-responsive-nav-link>
            @endrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Sair') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
