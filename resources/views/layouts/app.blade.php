<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">

        <title>{{ isset($title) ? $title . ' — ' : '' }}PGP · Gestão de Parcerias</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800">
        @php
            $navUser = auth()->user();
            $navRole = $navUser?->roles->first()?->name;
            $navRoleLabel = $navRole ? (\App\Models\User::$roleLabels[$navRole] ?? \Illuminate\Support\Str::headline($navRole)) : null;
            $navInitials = \Illuminate\Support\Str::of($navUser?->name ?? '?')
                ->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
        @endphp

        {{-- Faixa institucional: as duas cores da marca --}}
        <div class="h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

        <div class="min-h-screen bg-gray-50" x-data="{ sidebarOpen: false }">

            @include('layouts.sidebar')

            {{-- Fundo escurecido quando a gaveta abre no celular --}}
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 bg-gray-900/40 z-30 lg:hidden" style="display:none"></div>

            <div class="lg:pl-64 flex flex-col min-h-screen">

                {{-- Barra superior enxuta: gaveta (mobile) + menu do usuário --}}
                <div class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center gap-3 px-4 sm:px-6">
                    <button @click="sidebarOpen = true"
                            class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex-1"></div>

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
                                @if($navRoleLabel)
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $navRoleLabel }}</div>
                                @endif
                                @if($navUser?->setor)
                                    <div class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full">
                                        {{ $navUser->setorLabel() }}
                                    </div>
                                @endif
                            </div>
                            @role('responsavel_unidade_gestora')
                            <x-dropdown-link :href="route('subusuarios.index')">Meus usuários</x-dropdown-link>
                            @endrole
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

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-gray-200">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <footer class="border-t border-gray-200 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
                        <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                        <span>PGP · {{ now()->year }}</span>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
