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
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        {{-- Faixa institucional --}}
        <div class="h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-accent-500"></div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-4">
            <div class="flex flex-col items-center mb-7">
                <a href="/" class="flex items-center gap-3">
                    <x-marca class="h-12" />
                    <span class="text-gray-900 text-2xl font-bold tracking-tight">Gestão de Parcerias</span>
                </a>
                <p class="text-gray-500 text-sm mt-2">Plataforma pública municipal — Secretarias &amp; OSCs</p>
            </div>

            <div class="w-full sm:max-w-md bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="h-1 bg-brand-600"></div>
                <div class="px-6 py-8">
                    <h1 class="text-lg font-semibold text-gray-900 mb-1">Acessar o sistema</h1>
                    <p class="text-sm text-gray-500 mb-6">Use suas credenciais institucionais.</p>
                    {{ $slot }}
                </div>
            </div>

            <p class="text-gray-400 text-xs mt-8">© {{ now()->year }} Plataforma de Gestão de Parcerias</p>
        </div>
    </body>
</html>
