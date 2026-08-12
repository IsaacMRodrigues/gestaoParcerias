<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/png" href="https://pmsgra.net/ico.png">

        <title>Entrar — PGP · Gestão de Parcerias</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-white">

        <div class="min-h-screen lg:grid lg:grid-cols-2">

            {{-- Painel institucional: mesma linguagem do hero da tela inicial.
                 Escondido no celular, onde só o formulário importa. --}}
            <div class="relative hidden lg:flex flex-col justify-between bg-brand-900 p-12 overflow-hidden">
                <div aria-hidden="true" class="absolute inset-0">
                    <svg class="absolute inset-0 w-full h-full opacity-[0.07]">
                        <defs>
                            <pattern id="pgp-login-dots" width="28" height="28" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1.5" fill="#fff" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#pgp-login-dots)" />
                    </svg>
                    <div class="absolute -top-28 -right-28 w-[26rem] h-[26rem] rounded-full bg-brand-500/25 blur-3xl"></div>
                    <div class="absolute -bottom-36 -left-24 w-[24rem] h-[24rem] rounded-full bg-accent-500/20 blur-3xl"></div>
                </div>

                <a href="{{ route('landing') }}" class="relative inline-block w-fit rounded-lg
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <x-marca variant="branco" class="h-11" />
                </a>

                <div class="relative max-w-md">
                    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider
                                 text-white bg-white/10 ring-1 ring-inset ring-white/25 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-400"></span>
                        Lei Federal nº 13.019/2014
                    </span>

                    <h2 class="mt-6 text-3xl font-bold text-white leading-tight tracking-tight">
                        Gestão de Parcerias com as
                        <span class="text-brand-200">Organizações da Sociedade Civil</span>
                    </h2>

                    <ul class="mt-8 space-y-3.5 text-sm text-brand-50/85">
                        @foreach([
                            'Chamamentos públicos, propostas e planos de trabalho',
                            'Trâmite eletrônico dos processos entre as Secretarias',
                            'Repasses, despesas e transparência das parcerias',
                        ] as $item)
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 mt-px shrink-0 rounded-full bg-white/15 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-brand-100/75">
                    Prefeitura de São Gonçalo do Rio Abaixo · Sistema público municipal
                </p>
            </div>

            {{-- Formulário --}}
            <div class="flex flex-col min-h-screen">
                <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500 lg:hidden"></div>

                <div class="flex-1 flex flex-col justify-center px-5 py-12 sm:px-12">
                    <div class="w-full max-w-md mx-auto">

                        {{-- No celular o painel some, então a marca aparece aqui --}}
                        <a href="{{ route('landing') }}" class="lg:hidden flex items-center gap-3 mb-8">
                            <x-marca class="h-11" />
                            <span class="leading-tight">
                                <span class="block font-bold text-gray-900">Gestão de Parcerias</span>
                                <span class="block text-xs text-gray-500">Prefeitura de São Gonçalo do Rio Abaixo</span>
                            </span>
                        </a>

                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Acessar o sistema</h1>
                        <p class="text-sm text-gray-500 mt-2">
                            Use suas credenciais institucionais.
                        </p>

                        <div class="mt-8">
                            {{ $slot }}
                        </div>

                        <p class="text-xs text-gray-400 mt-10 pt-6 border-t border-gray-100">
                            © {{ now()->year }} Plataforma de Gestão de Parcerias
                            <a href="{{ route('landing') }}" class="text-brand-700 font-medium hover:underline ml-1">
                                Voltar ao início
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
