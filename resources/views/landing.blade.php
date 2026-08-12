<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">
    <title>Parcerias com as OSCs — PGP · São Gonçalo do Rio Abaixo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-gray-800 bg-gray-50">

    {{-- Faixa institucional: as duas cores da marca --}}
    <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

    @php
        // Quem já está logado vai direto à sua área: mandar para o login só faria
        // o middleware 'guest' rebatê-lo.
        $usuario = auth()->user();
        $acessoPrefeitura = $usuario?->temAcessoInterno()
            ? route('dashboard') : route('login');
        $acessoOsc = $usuario?->hasRole('responsavel_legal')
            ? route('portal.index') : route('login');
    @endphp

    <div class="min-h-screen flex flex-col">

        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 rounded-lg
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                    <x-marca class="h-11" />
                    <span class="leading-tight">
                        <span class="block font-semibold text-gray-900">Gestão de Parcerias</span>
                        <span class="block text-xs text-gray-500">Prefeitura de São Gonçalo do Rio Abaixo</span>
                    </span>
                </a>

                <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-gray-600">
                    <a href="{{ route('portal.index') }}" class="hover:text-brand-700 transition">Chamamentos</a>
                    <a href="{{ route('transparencia') }}" class="hover:text-brand-700 transition">Transparência</a>
                    <a href="{{ route('validacao.index') }}" class="hover:text-brand-700 transition">Validar documento</a>
                </nav>
            </div>
        </header>

        <main class="flex-1">

            {{-- Hero institucional: o verde da marca ocupando espaço de verdade --}}
            <section class="relative overflow-hidden bg-brand-900">
                {{-- Textura: malha de pontos + halos nas duas cores da marca --}}
                <div aria-hidden="true" class="absolute inset-0">
                    <svg class="absolute inset-0 w-full h-full opacity-[0.07]">
                        <defs>
                            <pattern id="pgp-dots" width="28" height="28" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1.5" fill="#fff" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#pgp-dots)" />
                    </svg>
                    <div class="absolute -top-32 -right-24 w-[28rem] h-[28rem] rounded-full bg-brand-500/25 blur-3xl"></div>
                    <div class="absolute -bottom-40 -left-24 w-[26rem] h-[26rem] rounded-full bg-accent-500/20 blur-3xl"></div>
                </div>

                <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24 sm:pt-20 sm:pb-32">
                    <div class="max-w-3xl">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider
                                     text-white bg-white/10 ring-1 ring-inset ring-white/25 px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-accent-400"></span>
                            Lei Federal nº 13.019/2014
                        </span>

                        <h1 class="mt-6 text-4xl sm:text-5xl font-bold tracking-tight text-white leading-[1.1]">
                            Parcerias com as<br class="hidden sm:block">
                            <span class="text-brand-200">Organizações da Sociedade Civil</span>
                        </h1>

                        <p class="mt-6 text-lg text-brand-50/85 leading-relaxed max-w-2xl">
                            Plataforma de acesso e controle dos repasses de recursos financeiros do Município
                            ao Terceiro Setor. Aqui ficam disponíveis os chamamentos públicos e as parcerias
                            celebradas.
                        </p>
                    </div>
                </div>
            </section>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

                {{-- Acesso ao sistema: cartões avançando sobre o hero, para dar profundidade --}}
                <section class="relative z-10 -mt-14">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-white/70 mb-4">Acesso ao sistema</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <a href="{{ $acessoPrefeitura }}"
                           class="group relative bg-white rounded-2xl p-6 flex items-start gap-5 overflow-hidden
                                  shadow-lg shadow-brand-900/10 ring-1 ring-gray-900/5
                                  hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/15 transition duration-200
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                            <span class="absolute inset-y-0 left-0 w-1.5 bg-brand-600"></span>
                            <span class="w-12 h-12 shrink-0 rounded-xl bg-brand-600 text-white flex items-center justify-center
                                         shadow-sm group-hover:scale-105 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-lg font-bold text-gray-900">Prefeitura</span>
                                <span class="block text-sm text-gray-600 mt-1 leading-relaxed">
                                    Servidores das Secretarias, SCP, SEPLAN e Procuradoria.
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 mt-3">
                                    Entrar
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </span>
                        </a>

                        <a href="{{ $acessoOsc }}"
                           class="group relative bg-white rounded-2xl p-6 flex items-start gap-5 overflow-hidden
                                  shadow-lg shadow-brand-900/10 ring-1 ring-gray-900/5
                                  hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/15 transition duration-200
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                            <span class="absolute inset-y-0 left-0 w-1.5 bg-brand-600"></span>
                            <span class="w-12 h-12 shrink-0 rounded-xl bg-brand-600 text-white flex items-center justify-center
                                         shadow-sm group-hover:scale-105 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 0 0 7.36 16.14M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-lg font-bold text-gray-900">OSC</span>
                                <span class="block text-sm text-gray-600 mt-1 leading-relaxed">
                                    Propostas, plano de trabalho e acompanhamento da parceria.
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 mt-3">
                                    Entrar
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </span>
                        </a>
                    </div>
                </section>

                {{-- Consulta pública: laranja como cor de ação, sem cadastro --}}
                <section class="mt-14">
                    <div class="flex items-baseline justify-between gap-3 pb-3 border-b border-gray-200">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900">Consulta pública</h2>
                        <span class="text-xs font-medium text-accent-700 bg-accent-50 px-2.5 py-1 rounded-full">
                            sem necessidade de cadastro
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-5">
                        @foreach([
                            ['Cidadão', 'Chamamentos abertos e parcerias em andamento.', route('portal.index')],
                            ['Parlamentar', 'Repasses, valores e vigência das parcerias.', route('transparencia')],
                            ['Conselho', 'Acompanhamento das parcerias da política setorial.', route('transparencia')],
                        ] as [$titulo, $desc, $url])
                            <a href="{{ $url }}"
                               class="group bg-white rounded-xl p-5 border border-gray-200 border-t-[3px] border-t-accent-500
                                      hover:border-accent-200 hover:border-t-accent-500 hover:shadow-md hover:-translate-y-0.5
                                      transition duration-200
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                                <span class="block font-bold text-gray-900 group-hover:text-accent-700 transition">
                                    {{ $titulo }}
                                </span>
                                <span class="block text-sm text-gray-600 mt-1.5 leading-relaxed">{{ $desc }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wide text-accent-600 mt-4">
                                    Consultar
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Chamada para a OSC ainda não cadastrada --}}
                <section class="mt-14 mb-16">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8
                                flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div class="max-w-xl">
                            <h2 class="text-lg font-bold text-gray-900">Sua organização ainda não tem cadastro?</h2>
                            <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                                O cadastro da OSC é o primeiro passo para participar dos chamamentos públicos
                                e celebrar parcerias com o Município.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <a href="{{ route('portal.osc.create') }}"
                               class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white
                                      font-semibold text-sm px-5 py-3 rounded-xl shadow-sm hover:shadow transition
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                Cadastrar minha OSC
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <a href="{{ route('validacao.index') }}"
                               class="inline-flex items-center text-sm font-semibold text-gray-700 hover:text-brand-700
                                      px-4 py-3 rounded-xl border border-gray-300 hover:border-brand-300 transition">
                                Validar documento
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <footer class="bg-gray-900 text-gray-400 mt-auto">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8
                        flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                <span class="text-gray-500">PGP · {{ now()->year }}</span>
            </div>
            <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>
        </footer>
    </div>
</body>
</html>
