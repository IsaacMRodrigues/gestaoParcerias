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

        {{-- Barra do portal.
             Os rótulos longos ("Chamamentos abertos", "Minhas participações")
             quebravam em duas linhas: cinco links não cabiam na largura, e cada
             um terminava com uma altura, desalinhando a barra inteira.

             Agora a barra tem três zonas de largura previsível — marca, links e
             conta —, os links não quebram (`whitespace-nowrap`) e o que é
             administração da OSC ("Meus usuários") saiu da barra para o menu da
             conta, onde configuração costuma morar. Abaixo de `md`, os links
             viram gaveta em vez de espremer. --}}
        @php
            $navItens = [
                ['url' => route('portal.index'),   'rotulo' => 'Chamamentos abertos', 'ativo' => request()->routeIs('portal.index')],
                ['url' => route('transparencia'), 'rotulo' => 'Transparência',       'ativo' => request()->routeIs('transparencia')],
            ];

            if (auth()->check() && auth()->user()->ehRepresentanteOsc()) {
                $navItens[] = ['url' => route('portal.minhas-propostas'), 'rotulo' => 'Minhas participações', 'ativo' => request()->routeIs('portal.minhas*')];
                $navItens[] = ['url' => route('portal.manifestacoes.index'), 'rotulo' => 'Manifestar interesse', 'ativo' => request()->routeIs('portal.manifestacoes.*')];
            }

            $navLink = fn (bool $ativo) => $ativo
                ? 'text-brand-700 font-semibold'
                : 'text-gray-600 hover:text-brand-700';
        @endphp

        <header class="bg-white border-b border-gray-200" x-data="{ menu: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-6">

                    <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0 shrink-0">
                        <x-marca class="h-9" />
                        <span class="font-semibold text-gray-900 tracking-tight whitespace-nowrap hidden sm:block">
                            Portal de Parcerias
                        </span>
                    </a>

                    <nav class="hidden md:flex items-center gap-6 text-sm whitespace-nowrap">
                        @foreach($navItens as $item)
                            <a href="{{ $item['url'] }}" class="{{ $navLink($item['ativo']) }} transition">
                                {{ $item['rotulo'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-3 shrink-0">
                        @auth
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.outside="open = false"
                                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition">
                                    <span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ $navInitials }}
                                    </span>
                                    <span class="hidden lg:inline max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open" x-transition x-cloak
                                     class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg ring-1 ring-black/5 z-50 py-1 text-gray-700">
                                    <div class="px-4 py-2 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                        @if(auth()->user()->oscVinculada())
                                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->oscVinculada()->name }}</p>
                                        @endif
                                    </div>

                                    {{-- Administrar a equipe é configuração, não navegação diária. --}}
                                    @if(auth()->user()->ehResponsavelLegalOsc())
                                        <a href="{{ route('portal.usuarios.index') }}"
                                           class="block px-4 py-2 text-sm hover:bg-gray-50 {{ request()->routeIs('portal.usuarios.*') ? 'text-brand-700 font-semibold' : '' }}">
                                            Usuários da OSC
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                            Sair
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('portal.osc.create') }}"
                               class="text-sm text-gray-600 hover:text-brand-700 transition whitespace-nowrap hidden sm:inline">
                                Cadastrar OSC
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-primary">Entrar</a>
                        @endauth

                        <button @click="menu = !menu" aria-label="Menu"
                                class="md:hidden p-2 -mr-2 rounded-md text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Gaveta: os mesmos itens, em coluna, quando não cabem em linha --}}
                <div x-show="menu" x-cloak x-transition class="md:hidden pb-3 -mt-1 space-y-1">
                    @foreach($navItens as $item)
                        <a href="{{ $item['url'] }}"
                           class="block px-3 py-2 rounded-lg text-sm {{ $item['ativo'] ? 'bg-brand-50 text-brand-800 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            {{ $item['rotulo'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </header>

        {{-- Sua vez. Quando a UG encaminha a Celebração à OSC, o item sai da
             caixa do município e a OSC não tinha onde ver que a bola era dela —
             o trâmite parecia ter sumido. A faixa aparece em qualquer página do
             portal, porque não dá para supor que ela vá procurar. --}}
        @auth
            @if(auth()->user()->ehRepresentanteOsc())
                @php $minhaVez = \App\Support\CaixaDeEntrada::para(auth()->user()); @endphp
                @if($minhaVez->total() > 0)
                    <div class="bg-accent-50 border-b border-accent-200">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-2">
                            @foreach($minhaVez->itens as $item)
                                <div class="flex items-center justify-between gap-4 flex-wrap">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-accent-900">
                                            É a sua vez — {{ $item['titulo'] }}
                                        </p>
                                        <p class="text-xs text-accent-800">{{ $item['subtitulo'] }}</p>
                                    </div>
                                    <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm shrink-0">
                                        Continuar →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endauth

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
