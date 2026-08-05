<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso não autorizado — PGP</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    @php
        $msg = $exception?->getMessage();
        $generico = ! $msg || in_array($msg, [
            'Forbidden',
            'This action is unauthorized.',
            'User does not have the right permissions.',
        ], true);
    @endphp
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4 text-center">
        <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
            </svg>
        </div>

        <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Acesso restrito</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Você não tem acesso a esta área</h1>
        <p class="mt-2 text-gray-500 max-w-md">
            @if($generico)
                Seu perfil não tem permissão para acessar esta página. Se você acredita que deveria ter acesso, fale com o administrador do sistema.
            @else
                {{ $msg }}
            @endif
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="javascript:history.back()"
               class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                ← Voltar
            </a>
            <a href="{{ url('/dashboard') }}"
               class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition">
                Ir para o início
            </a>
        </div>

        <p class="mt-10 text-xs text-gray-400">PGP · Plataforma de Gestão de Parcerias</p>
    </div>
</body>
</html>
