<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('orgaos.index') }}" class="hover:underline">Órgãos e usuários</a>
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Cadastrar usuário</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-gray-600 mb-5">
                    A conta já nasce <strong>aprovada</strong>: a pessoa entra assim que receber a senha inicial.
                    Escolha o setor, a Secretaria e o que ela faz no sistema.
                </div>

                <form action="{{ route('usuarios.store') }}" method="POST" class="space-y-5">
                    @csrf

                    @include('usuarios._form')

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('orgaos.index') }}" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
