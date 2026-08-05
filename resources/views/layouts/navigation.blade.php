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
                        <x-marca class="h-9" />
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
                <div class="hidden lg:flex lg:-my-px lg:ms-4 xl:ms-6 space-x-2.5 xl:space-x-4">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Painel
                    </x-nav-link>
                    <x-nav-link :href="route('portal.index')" :active="false">
                        Portal Público
                    </x-nav-link>

                    {{-- Trilha completa do ciclo: as 6 etapas aparecem sempre. Sem
                         permissão (ou sem tela ainda), a etapa fica em cinza, sem link. --}}

                    {{-- 1. Planejamento --}}
                    @can('planejamento')
                    <x-nav-dropdown label="Planejamento"
                                    :active="request()->routeIs('processos.*')">
                        <a href="{{ route('processos.index') }}" class="{{ $navItem }}">Processos</a>
                        @if(auth()->user()->setor)
                            <a href="{{ route('processos.caixa') }}" class="{{ $navItem }}">Caixa de Entrada</a>
                        @endif
                    </x-nav-dropdown>
                    @else
                    <x-nav-soon label="Planejamento" hint="Você não tem acesso a esta etapa." />
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
                    @else
                    <x-nav-soon label="Seleção" hint="Você não tem acesso a esta etapa." />
                    @endcanany

                    {{-- 3. Celebração --}}
                    @can('formalizacao')
                    <x-nav-dropdown label="Celebração" :active="request()->routeIs('instrumentos.*')">
                        <a href="{{ route('instrumentos.index') }}" class="{{ $navItem }}">Instrumentos / Termos</a>
                    </x-nav-dropdown>
                    @else
                    <x-nav-soon label="Celebração" hint="Você não tem acesso a esta etapa." />
                    @endcan

                    {{-- 4. Execução — os repasses/despesas ficam dentro de cada Instrumento --}}
                    @can('formalizacao')
                    <x-nav-link :href="route('instrumentos.index')"
                                :active="request()->routeIs('instrumentos.execucao') || request()->routeIs('repasses.*') || request()->routeIs('despesas.*')">
                        Execução
                    </x-nav-link>
                    @else
                    <x-nav-soon label="Execução"
                                hint="Repasses, despesas e saldo: abra pela tela do Instrumento." />
                    @endcan

                    {{-- 5 e 6. Etapas ainda não implementadas --}}
                    <x-nav-soon label="Monitoramento & Avaliação" hint="Em breve" />
                    <x-nav-soon label="Prestação de Contas" hint="Em breve" />

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
                        <button title="{{ Auth::user()->name }}"
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-100 text-brand-700 text-xs font-bold hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-brand-300 transition">
                            {{ $navInitials }}
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                            @if($navUser?->setor)
                                <div class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full">
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
            @else
            <p class="{{ $navSecao }}">1 · Planejamento</p>
            <p class="px-4 py-1 text-sm text-gray-400">Você não tem acesso a esta etapa.</p>
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
            @else
            <p class="{{ $navSecao }}">2 · Seleção</p>
            <p class="px-4 py-1 text-sm text-gray-400">Você não tem acesso a esta etapa.</p>
            @endcanany

            @can('formalizacao')
            <p class="{{ $navSecao }}">3 · Celebração</p>
            <x-responsive-nav-link :href="route('instrumentos.index')" :active="request()->routeIs('instrumentos.*')">
                Instrumentos / Termos
            </x-responsive-nav-link>
            @else
            <p class="{{ $navSecao }}">3 · Celebração</p>
            <p class="px-4 py-1 text-sm text-gray-400">Você não tem acesso a esta etapa.</p>
            @endcan

            <p class="{{ $navSecao }}">4 · Execução</p>
            @can('formalizacao')
            <x-responsive-nav-link :href="route('instrumentos.index')"
                :active="request()->routeIs('instrumentos.execucao') || request()->routeIs('repasses.*') || request()->routeIs('despesas.*')">
                Instrumentos / Execução
            </x-responsive-nav-link>
            @else
            <p class="px-4 py-1 text-sm text-gray-400">Repasses, despesas e saldo — abra pela tela do Instrumento.</p>
            @endcan

            <p class="{{ $navSecao }}">5 · Monitoramento &amp; Avaliação</p>
            <p class="px-4 py-1 text-sm text-gray-400">Em breve.</p>

            <p class="{{ $navSecao }}">6 · Prestação de Contas</p>
            <p class="px-4 py-1 text-sm text-gray-400">Em breve.</p>

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
