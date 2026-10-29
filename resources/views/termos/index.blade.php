<x-portal-layout>
    <x-slot name="title">Termos de Uso e Adesão</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <h1 class="text-2xl font-bold text-gray-900">Termos de Uso e Adesão</h1>
            <p class="text-sm text-gray-500 mt-1 mb-4">Plataforma de Gestão de Parcerias — Município de São Gonçalo do Rio Abaixo/MG</p>
            @include('termos._documento')

            @auth
                @if(auth()->user()->termos_aceitos_em)
                    <p class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-500">
                        Você aceitou a versão {{ auth()->user()->termos_versao }} em
                        {{ \App\Support\HoraLegal::emBrasilia(auth()->user()->termos_aceitos_em)->format('d/m/Y \à\s H:i') }} (Brasília).
                    </p>
                @endif
            @endauth
        </div>
    </div>
</x-portal-layout>
