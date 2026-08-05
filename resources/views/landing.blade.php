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

    {{-- Faixa institucional: as duas cores da marca, sem dominar a tela --}}
    <div class="h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

    <div class="min-h-screen flex flex-col">

        <header class="bg-white border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center gap-3">
                <x-marca class="h-11" />
                <span class="leading-tight">
                    <span class="block font-semibold text-gray-900">Gestão de Parcerias</span>
                    <span class="block text-xs text-gray-500">Prefeitura de São Gonçalo do Rio Abaixo</span>
                </span>
            </div>
        </header>

        <main class="flex-1">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">

                <div class="max-w-3xl">
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-brand-700 bg-brand-50 px-2.5 py-1 rounded">
                        Lei Federal nº 13.019/2014
                    </span>
                    <h1 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                        Parcerias com as Organizações da Sociedade Civil
                    </h1>
                    <p class="mt-4 text-gray-600 leading-relaxed">
                        Plataforma de acesso e controle dos repasses de recursos financeiros do Município
                        ao Terceiro Setor. Aqui ficam disponíveis os chamamentos públicos e as parcerias
                        celebradas.
                    </p>
                </div>

                {{-- Acesso ao sistema --}}
                <section class="mt-12">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Acesso ao sistema</h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a href="{{ route('login') }}"
                           class="group bg-white border border-gray-200 rounded-xl p-5 flex items-start gap-4
                                  hover:border-brand-300 hover:shadow-sm transition">
                            <span class="w-11 h-11 shrink-0 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 group-hover:text-brand-700 transition">Prefeitura</span>
                                <span class="block text-sm text-gray-500 mt-0.5">
                                    Servidores das Secretarias, SCP, SEPLAN e Procuradoria.
                                </span>
                            </span>
                        </a>

                        <a href="{{ route('login') }}"
                           class="group bg-white border border-gray-200 rounded-xl p-5 flex items-start gap-4
                                  hover:border-brand-300 hover:shadow-sm transition">
                            <span class="w-11 h-11 shrink-0 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 0 0 7.36 16.14M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 group-hover:text-brand-700 transition">OSC</span>
                                <span class="block text-sm text-gray-500 mt-0.5">
                                    Propostas, plano de trabalho e acompanhamento da parceria.
                                </span>
                            </span>
                        </a>
                    </div>
                </section>

                {{-- Consulta pública --}}
                <section class="mt-10">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Consulta pública</h2>
                        <span class="text-xs text-gray-400">sem necessidade de cadastro</span>
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach([
                            ['Cidadão', 'Chamamentos abertos e parcerias em andamento.', route('portal.index')],
                            ['Parlamentar', 'Repasses, valores e vigência das parcerias.', route('transparencia')],
                            ['Conselho', 'Acompanhamento das parcerias da política setorial.', route('transparencia')],
                        ] as [$titulo, $desc, $url])
                            <a href="{{ $url }}"
                               class="group bg-white border border-gray-200 border-t-2 border-t-accent-500 rounded-xl p-5
                                      hover:border-accent-300 hover:border-t-accent-500 hover:shadow-sm transition">
                                <span class="block font-semibold text-gray-900 group-hover:text-accent-700 transition">
                                    {{ $titulo }}
                                </span>
                                <span class="block text-sm text-gray-500 mt-1">{{ $desc }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-accent-600 mt-3">
                                    Consultar
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Atalhos --}}
                <div class="mt-10 pt-6 border-t border-gray-200 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <a href="{{ route('portal.osc.create') }}" class="text-brand-700 hover:underline font-medium">
                        Cadastrar minha OSC
                    </a>
                    <a href="{{ route('portal.index') }}" class="text-gray-600 hover:text-gray-900">Chamamentos abertos</a>
                    <a href="{{ route('validacao.index') }}" class="text-gray-600 hover:text-gray-900">Validar documento</a>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
                <span>Plataforma de Gestão de Parcerias — Sistema público municipal</span>
                <span>PGP · {{ now()->year }}</span>
            </div>
        </footer>
    </div>
</body>
</html>
