<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">
    <title>{{ isset($title) ? $title . ' — ' : '' }}Portal de Parcerias</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800">

@php
    $portalInterno = auth()->check() && auth()->user()->temAcessoInterno();
    $navInitials = \Illuminate\Support\Str::of(auth()->user()?->name ?? '?')
        ->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

@if($portalInterno)
    {{-- Usuário interno da Administração: navega o portal com a mesma sidebar
         do sistema, para não perder o menu nem trocar de padrão visual. --}}
    <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        @include('layouts.sidebar')

        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-900/40 z-30 lg:hidden" style="display:none"></div>

        <div class="lg:pl-64 flex flex-col min-h-screen">
            {{-- Barra superior: gaveta (mobile), seções do portal e menu do usuário --}}
            <div class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center gap-4 px-4 sm:px-6">
                <button @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Deixa explícito que aqui é a face pública do sistema, e não a
                     área administrativa: rótulo com ícone + abas de verdade. --}}
                <span class="hidden sm:flex items-center gap-2 pr-4 mr-1 border-r border-gray-200 shrink-0">
                    <svg class="w-[18px] h-[18px] text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 010 18 M12 3a15 15 0 000 18"/>
                    </svg>
                    <span class="text-sm font-bold text-gray-900">Portal Público</span>
                </span>

                <nav class="flex items-center gap-1.5 text-sm">
                    @foreach([
                        ['portal.index', route('portal.index'), 'Chamamentos'],
                        ['transparencia', route('transparencia'), 'Transparência'],
                    ] as [$rota, $url, $rotulo])
                        <a href="{{ $url }}"
                           class="px-3 py-2 rounded-lg font-medium transition
                                  {{ request()->routeIs($rota)
                                     ? 'bg-brand-50 text-brand-800 font-semibold'
                                     : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            {{ $rotulo }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex-1"></div>

                <a href="{{ route('dashboard') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 text-sm font-medium text-gray-600
                          hover:text-brand-700 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Área administrativa
                </a>

                <x-dropdown align="right" width="72">
                    <x-slot name="trigger">
                        <button title="{{ auth()->user()->name }}"
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-100 text-brand-700 text-xs font-bold hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-brand-300 transition">
                            {{ $navInitials }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-3.5 border-b border-gray-100 bg-gray-50/70">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 shrink-0 rounded-full bg-brand-600 text-white text-sm font-bold flex items-center justify-center">
                                    {{ $navInitials }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>
                        <x-dropdown-link :href="route('dashboard')">Área Administrativa</x-dropdown-link>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Perfil') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="text-red-600">
                                {{ __('Sair') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-gray-200 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
                    <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                    <a href="{{ route('validacao.index') }}" class="hover:text-brand-700 transition">Validar documento</a>
                </div>
            </footer>
        </div>
    </div>
@else
    {{-- Visitante e OSC: portal público, com cabeçalho claro e a cor nos detalhes. --}}
    <div class="min-h-screen flex flex-col">
        <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-4">
                    <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0">
                        <x-marca class="h-9" />
                        <span class="font-semibold text-gray-900 tracking-tight hidden sm:block">
                            Portal de Parcerias
                        </span>
                    </a>

                    <nav class="flex items-center gap-5 text-sm">
                        <a href="{{ route('portal.index') }}"
                           class="{{ request()->routeIs('portal.index') ? 'text-brand-700 font-semibold' : 'text-gray-600 hover:text-brand-700' }} transition">
                            Chamamentos
                        </a>
                        <a href="{{ route('transparencia') }}"
                           class="{{ request()->routeIs('transparencia') ? 'text-brand-700 font-semibold' : 'text-gray-600 hover:text-brand-700' }} transition">
                            Transparência
                        </a>

                        @auth
                            @if(auth()->user()->ehRepresentanteOsc())
                                <a href="{{ route('portal.minhas-propostas') }}"
                                   class="{{ request()->routeIs('portal.minhas*') ? 'text-brand-700 font-semibold' : 'text-gray-600 hover:text-brand-700' }} transition">
                                    Minhas Propostas
                                </a>
                            @endif

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.outside="open = false"
                                        class="flex items-center gap-1 text-gray-600 hover:text-gray-900 transition">
                                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                    <span class="sm:hidden w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center">
                                        {{ $navInitials }}
                                    </span>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 text-gray-700">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            Sair
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('portal.osc.create') }}"
                               class="text-gray-600 hover:text-brand-700 transition hidden sm:inline">
                                Cadastrar OSC
                            </a>
                            <a href="{{ route('login') }}"
                               class="btn btn-primary">
                                Entrar
                            </a>
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-gray-900 text-gray-400 mt-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8
                        flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                <span class="flex items-center gap-5">
                    <a href="{{ route('transparencia') }}" class="hover:text-white transition">Transparência</a>
                    <a href="{{ route('validacao.index') }}" class="hover:text-white transition">Validar documento</a>
                    <span class="text-gray-500">PGP · {{ now()->year }}</span>
                </span>
            </div>
            <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>
        </footer>
    </div>
@endif

</body>
</html>
