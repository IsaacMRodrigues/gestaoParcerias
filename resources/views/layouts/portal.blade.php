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
                @unless(request()->routeIs('portal.index'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
                        <x-botao-voltar :inicio="route('portal.index')" />
                    </div>
                @endunless
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
@elseif(auth()->check())
    {{-- OSC logada: menu na barra lateral, como na área interna; a conta fica no canto superior. --}}
    @php
        $icone = [
            'megafone' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46',
            'lista' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
            'ideia' => 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
            'documento' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
            'lapis' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
            'calculadora' => 'M8.25 6h7.5v2.25h-7.5V6zM8.25 12h.008v.008H8.25V12zm0 3h.008v.008H8.25V15zm0 3h.008v.008H8.25V18zm3.75-6h.008v.008H12V12zm0 3h.008v.008H12V15zm0 3h.008v.008H12V18zm3.75-6h.008v.008h-.008V12zm0 3h.008v.008h-.008V15zm0 3h.008v.008h-.008V18zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z',
            'pasta' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
            'ajuda' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'grafico' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        ];
        $navItens = [
            ['secao' => 'Participar', 'url' => route('portal.index'), 'rotulo' => 'Chamamentos abertos', 'ativo' => request()->routeIs('portal.index') || request()->routeIs('portal.chamamento*'), 'icone' => $icone['megafone']],
        ];
        if (auth()->user()->ehRepresentanteOsc()) {
            // O layout não recebe as variáveis da tela: a manifestação vem da rota.
            $daRota = request()->routeIs('portal.manifestacoes.show') ? request()->route('manifestacao') : null;
            $daRota = $daRota instanceof \App\Models\ManifestacaoInteresse ? $daRota : ($daRota ? \App\Models\ManifestacaoInteresse::find($daRota) : null);
            $novaProposta = (bool) $daRota?->ehNovaProposta();
            array_push($navItens,
                ['url' => route('portal.minhas-propostas'), 'rotulo' => 'Minhas inscrições', 'ativo' => request()->routeIs('portal.minhas*', 'portal.proposta.*', 'portal.dossie*'), 'icone' => $icone['lista']],
                ['rotulo' => 'Propor parceria', 'filhos' => [
                    ['url' => route('portal.manifestacoes.index'), 'rotulo' => 'Manifestar interesse', 'ativo' => request()->routeIs('portal.manifestacoes.*') && ! $novaProposta, 'icone' => $icone['ideia']],
                    ['url' => route('portal.novas-propostas.index'), 'rotulo' => 'Nova Proposta', 'ativo' => request()->routeIs('portal.novas-propostas.*') || $novaProposta, 'icone' => $icone['documento']],
                ]],
                ['rotulo' => 'Execução', 'filhos' => [
                    ['url' => route('alteracoes.index'), 'rotulo' => 'Alterações', 'ativo' => request()->routeIs('alteracoes.*'), 'icone' => $icone['lapis']],
                    ['url' => route('prestacao-contas.index'), 'rotulo' => 'Prestação de contas', 'ativo' => request()->routeIs('prestacao-contas.*', 'lancamentos.*') && ! request()->routeIs('prestacao-contas.relatorios'), 'icone' => $icone['calculadora']],
                    ['url' => route('prestacao-contas.relatorios'), 'rotulo' => 'Relatórios', 'ativo' => request()->routeIs('prestacao-contas.relatorios'), 'icone' => $icone['grafico']],
                ]],
                ['secao' => 'Organização', 'url' => route('portal.arquivos.index'), 'rotulo' => 'Arquivos da OSC', 'ativo' => request()->routeIs('portal.arquivos.*'), 'icone' => $icone['pasta']],
            );
        } else {
            $navItens[] = ['url' => route('transparencia'), 'rotulo' => 'Transparência', 'ativo' => request()->routeIs('transparencia'), 'icone' => $icone['grafico']];
        }
        $navItens[] = ['secao' => 'Ajuda', 'url' => route('suporte.index'), 'rotulo' => 'Suporte', 'ativo' => request()->routeIs('suporte.*'), 'icone' => $icone['ajuda']];
    @endphp

    <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        @include('layouts.sidebar-osc', ['navItens' => $navItens])

        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-900/40 z-30 lg:hidden" style="display:none"></div>

        <div class="lg:pl-64 flex flex-col min-h-screen">
            {{-- Barra superior: gaveta (celular) e a conta --}}
            <div class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center gap-4 px-4 sm:px-6">
                <button @click="sidebarOpen = true" aria-label="Menu"
                        class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex-1"></div>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition">
                        <span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">
                            {{ $navInitials }}
                        </span>
                        <span class="hidden sm:inline max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
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
                        <a href="{{ route('portal.perfil.edit') }}"
                           class="block px-4 py-2 text-sm hover:bg-gray-50 {{ request()->routeIs('portal.perfil.*') ? 'text-brand-700 font-semibold' : '' }}">
                            Meus dados e senha
                        </a>
                        {{-- Administrar a equipe é configuração, não navegação diária. --}}
                        @if(auth()->user()->ehResponsavelLegalOsc())
                            <a href="{{ route('portal.usuarios.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-50 {{ request()->routeIs('portal.usuarios.*') ? 'text-brand-700 font-semibold' : '' }}">
                                Meus usuários
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Sair</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sua vez: a faixa aparece em qualquer página do portal quando o trâmite espera a OSC. --}}
            @if(auth()->user()->ehRepresentanteOsc())
                @php $minhaVez = \App\Support\CaixaDeEntrada::para(auth()->user()); @endphp
                @if($minhaVez->total() > 0)
                    <div class="bg-accent-50 border-b border-accent-200">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-2">
                            @foreach($minhaVez->itens as $item)
                                <div class="flex items-center justify-between gap-4 flex-wrap">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-accent-900">É a sua vez — {{ $item['titulo'] }}</p>
                                        <p class="text-xs text-accent-800">{{ $item['subtitulo'] }}</p>
                                    </div>
                                    <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm shrink-0">Continuar →</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            {{-- Conta de OSC sem organização: diz por que os itens de inscrição não aparecem. --}}
            @elseif(auth()->user()->hasAnyRole(\App\Models\User::PAPEIS_OSC))
                <div class="bg-accent-50 border-b border-accent-200">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-accent-900">Sua conta não está vinculada a nenhuma organização</p>
                            <p class="text-xs text-accent-800">
                                Por isso as inscrições e a manifestação de interesse não aparecem. Peça à
                                Prefeitura para regularizar o vínculo.
                            </p>
                        </div>
                        <a href="{{ route('suporte.index') }}" class="btn btn-primary btn-sm shrink-0">Abrir chamado →</a>
                    </div>
                </div>
            @endif

            <main class="flex-1">
                @unless(request()->routeIs('portal.index'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
                        <x-botao-voltar :inicio="route('portal.index')" />
                    </div>
                @endunless
                {{ $slot }}
            </main>

            <footer class="border-t border-gray-200 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
                    <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                    <span class="flex items-center gap-5">
                        <a href="{{ route('transparencia') }}" class="hover:text-brand-700 transition">Transparência</a>
                        <a href="{{ route('validacao.index') }}" class="hover:text-brand-700 transition">Validar documento</a>
                    </span>
                </div>
            </footer>
        </div>
    </div>
@else
    {{-- Visitante: portal público, com cabeçalho claro e a cor nos detalhes. --}}
    @php
        $navItens = [
            ['url' => route('portal.index'),   'rotulo' => 'Chamamentos abertos', 'ativo' => request()->routeIs('portal.index')],
            ['url' => route('transparencia'), 'rotulo' => 'Transparência',       'ativo' => request()->routeIs('transparencia')],
        ];
        $navLink = fn (bool $ativo) => $ativo ? 'text-brand-700 font-semibold' : 'text-gray-600 hover:text-brand-700';
    @endphp
    <div class="min-h-screen flex flex-col">
        <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

        <header class="bg-white border-b border-gray-200" x-data="{ menu: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-6">
                    <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0 shrink-0">
                        <x-marca class="h-9" />
                        <span class="font-semibold text-gray-900 tracking-tight whitespace-nowrap hidden sm:block">Portal de Parcerias</span>
                    </a>

                    <nav class="hidden lg:flex items-center gap-6 text-sm whitespace-nowrap">
                        @foreach($navItens as $item)
                            <a href="{{ $item['url'] }}" class="{{ $navLink($item['ativo']) }} transition">{{ $item['rotulo'] }}</a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('portal.osc.create') }}"
                           class="text-sm text-gray-600 hover:text-brand-700 transition whitespace-nowrap hidden sm:inline">Cadastrar OSC</a>
                        <a href="{{ route('login', ['acesso' => 'osc']) }}" class="btn btn-primary">Entrar</a>
                        <button @click="menu = !menu" aria-label="Menu"
                                class="lg:hidden p-2 -mr-2 rounded-md text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Gaveta: os mesmos itens, em coluna, quando não cabem em linha --}}
                <div x-show="menu" x-cloak x-transition class="lg:hidden pb-3 -mt-1 space-y-1">
                    @foreach($navItens as $item)
                        <a href="{{ $item['url'] }}"
                           class="block px-3 py-2 rounded-lg text-sm {{ $item['ativo'] ? 'bg-brand-50 text-brand-800 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            {{ $item['rotulo'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </header>

        <main class="flex-1">
            @unless(request()->routeIs('portal.index'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
                    <x-botao-voltar :inicio="route('portal.index')" />
                </div>
            @endunless
            {{ $slot }}
        </main>

        {{-- Claro, como o rodapé da área interna e da página inicial. --}}
        <footer class="bg-white border-t border-gray-200 text-gray-500 mt-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6
                        flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                <span class="flex items-center gap-5">
                    <a href="{{ route('transparencia') }}" class="hover:text-brand-700 transition">Transparência</a>
                    <a href="{{ route('validacao.index') }}" class="hover:text-brand-700 transition">Validar documento</a>
                    <span class="text-gray-400">PGP · {{ now()->year }}</span>
                </span>
            </div>
            <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>
        </footer>
    </div>
@endif

</body>
</html>
