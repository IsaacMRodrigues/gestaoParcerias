<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">
    <title>{{ isset($title) ? $title . ' — ' : '' }}Portal de Parcerias</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    @php $portalInterno = auth()->check() && auth()->user()->temAcessoInterno(); @endphp

    @if($portalInterno)
        {{-- Usuário interno da Administração: mantém o menu do sistema no topo,
             para continuar navegando mesmo estando no portal público. --}}
        @include('layouts.navigation')
    @else
    <header class="bg-brand-800 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('portal.index') }}" class="flex items-center gap-3">
                    <x-marca variant="branco" class="h-8" />
                    <span class="font-semibold text-lg tracking-tight hidden sm:block">
                        Portal de Parcerias
                    </span>
                </a>

                <nav class="flex items-center gap-6 text-sm">
                    <a href="{{ route('portal.index') }}"
                       class="text-brand-200 hover:text-white transition {{ request()->routeIs('portal.index') ? 'text-white font-medium' : '' }}">
                        Chamamentos
                    </a>
                    <a href="{{ route('transparencia') }}"
                       class="text-brand-200 hover:text-white transition {{ request()->routeIs('transparencia') ? 'text-white font-medium' : '' }}">
                        Transparência
                    </a>

                    @auth
                        @if(auth()->user()->osc)
                            <a href="{{ route('portal.minhas-propostas') }}"
                               class="text-brand-200 hover:text-white transition {{ request()->routeIs('portal.minhas*') ? 'text-white font-medium' : '' }}">
                                Minhas Propostas
                            </a>
                        @endif

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.outside="open = false"
                                    class="flex items-center gap-1 text-brand-200 hover:text-white transition">
                                {{ auth()->user()->name }}
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
                           class="text-brand-200 hover:text-white transition">
                            Cadastrar OSC
                        </a>
                        <a href="{{ route('login') }}"
                           class="bg-white text-brand-800 px-4 py-1.5 rounded-md font-medium text-sm hover:bg-brand-50 transition">
                            Entrar
                        </a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>
    @endif

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-brand-900 text-brand-300 text-sm py-6 mt-12">
        <div class="max-w-6xl mx-auto px-4 text-center">
            Plataforma de Gestão de Parcerias — Sistema público municipal
        </div>
    </footer>

</body>
</html>
