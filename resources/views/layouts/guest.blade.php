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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-brand-800 via-brand-700 to-brand-900 px-4">
            <div class="flex flex-col items-center mb-6">
                <a href="/" class="flex items-center gap-3">
                    <x-marca variant="branco" class="h-12" />
                    <span class="text-white text-2xl font-bold tracking-tight">Gestão de Parcerias</span>
                </a>
                <p class="text-brand-200 text-sm mt-2">Plataforma pública municipal — Secretarias &amp; OSCs</p>
            </div>

            <div class="w-full sm:max-w-md px-6 py-8 bg-white shadow-2xl overflow-hidden rounded-2xl ring-1 ring-black/5">
                <h1 class="text-lg font-semibold text-gray-900 mb-1">Acessar o sistema</h1>
                <p class="text-sm text-gray-500 mb-6">Use suas credenciais institucionais.</p>
                {{ $slot }}
            </div>

            <p class="text-brand-200/70 text-xs mt-8">© {{ now()->year }} Plataforma de Gestão de Parcerias</p>
        </div>
    </body>
</html>
