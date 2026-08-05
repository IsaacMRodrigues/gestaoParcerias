<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">
    <title>Parcerias com as OSCs — PGP · São Gonçalo do Rio Abaixo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-gray-800">

    {{-- Splash: escolha do perfil de acesso --}}
    <div class="min-h-screen flex flex-col bg-gradient-to-br from-brand-900 via-brand-900 to-brand-800 text-white">

        {{-- textura sutil --}}
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:26px 26px;"></div>

        <div class="relative flex-1 flex items-center justify-center px-4 py-14">
            <div class="w-full max-w-5xl text-center">

                <div class="flex items-center justify-center gap-3 mb-8">
                    <x-marca variant="branco" class="h-12" />
                    <span class="text-left leading-tight">
                        <span class="block font-semibold text-lg">Gestão de Parcerias</span>
                        <span class="block text-xs text-brand-300">Prefeitura de São Gonçalo do Rio Abaixo</span>
                    </span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight leading-tight">
                    PARCERIAS COM AS OSCs
                </h1>
                <p class="mt-2 text-lg sm:text-xl text-brand-200 font-medium">
                    Organizações da Sociedade Civil
                </p>

                <p class="mt-6 max-w-3xl mx-auto text-sm sm:text-base text-brand-100/90 leading-relaxed">
                    Esta é uma plataforma que permite o acesso e o controle dos repasses de recursos
                    financeiros do Município ao Terceiro Setor. Aqui encontram-se disponíveis os
                    chamamentos públicos e as parcerias celebradas, nos termos da Lei Federal
                    nº 13.019/2014.
                </p>

                {{-- Acessos que exigem login --}}
                <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl mx-auto">
                    <a href="{{ route('login') }}"
                       class="group flex items-center justify-center gap-3 px-6 py-4 rounded-full bg-white/95 text-brand-900 font-semibold shadow-lg hover:bg-white hover:-translate-y-0.5 transition">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                        </svg>
                        Acesso Prefeitura
                    </a>
                    <a href="{{ route('login') }}"
                       class="group flex items-center justify-center gap-3 px-6 py-4 rounded-full bg-white/95 text-brand-900 font-semibold shadow-lg hover:bg-white hover:-translate-y-0.5 transition">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 0 0 7.36 16.14M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        Acesso OSC
                    </a>
                </div>

                {{-- Consulta pública (sem login) --}}
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto">
                    <a href="{{ route('portal.index') }}"
                       class="px-5 py-3.5 rounded-full bg-accent-500 text-white font-semibold text-sm shadow-lg hover:bg-accent-600 hover:-translate-y-0.5 transition">
                        Acesso Cidadão
                    </a>
                    <a href="{{ route('transparencia') }}"
                       class="px-5 py-3.5 rounded-full bg-accent-500 text-white font-semibold text-sm shadow-lg hover:bg-accent-600 hover:-translate-y-0.5 transition">
                        Acesso Parlamentar
                    </a>
                    <a href="{{ route('transparencia') }}"
                       class="px-5 py-3.5 rounded-full bg-accent-500 text-white font-semibold text-sm shadow-lg hover:bg-accent-600 hover:-translate-y-0.5 transition">
                        Acesso Conselho
                    </a>
                </div>

                <p class="mt-4 text-xs text-brand-300">
                    Cidadão, Parlamentar e Conselho consultam livremente, sem necessidade de cadastro.
                </p>

                {{-- Atalhos secundários --}}
                <div class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-brand-200">
                    <a href="{{ route('portal.osc.create') }}" class="hover:text-white hover:underline">
                        Cadastrar minha OSC
                    </a>
                    <span class="text-brand-500">·</span>
                    <a href="{{ route('portal.index') }}" class="hover:text-white hover:underline">
                        Chamamentos abertos
                    </a>
                    <span class="text-brand-500">·</span>
                    <a href="{{ route('validacao.index') }}" class="hover:text-white hover:underline">
                        Validar documento
                    </a>
                </div>
            </div>
        </div>

        <footer class="relative border-t border-white/10 py-5 text-center text-xs text-brand-300">
            Plataforma de Gestão de Parcerias — Sistema público municipal · PGP {{ now()->year }}
        </footer>
    </div>
</body>
</html>
