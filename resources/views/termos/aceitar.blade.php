<x-portal-layout>
    <x-slot name="title">Termos de Uso</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ auth()->user()->termos_versao ? 'Os Termos de Uso foram atualizados' : 'Termos de Uso' }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Antes de continuar, leia os Termos de Uso da Plataforma de Gestão de Parcerias. O uso do sistema depende
                do seu aceite, que fica registrado com a data, a hora e o endereço IP desta conexão.
            </p>

            <div class="mt-5 max-h-[52vh] overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 px-5 py-4">
                @include('termos._documento')
            </div>

            <form method="POST" action="{{ route('termos.aceitar.salvar') }}" class="mt-5 space-y-4">
                @csrf
                <label class="flex items-start gap-3 text-sm text-gray-800">
                    <input type="checkbox" name="aceite" value="1" required
                           class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span>Li e aceito os Termos de Uso (versão {{ \App\Support\TermosDeUso::VERSAO }}).</span>
                </label>
                <x-input-error :messages="$errors->get('aceite')" />

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="submit" class="btn btn-primary">Aceitar e continuar</button>
                    <a href="{{ route('termos.index') }}" target="_blank" class="text-sm text-brand-700 hover:underline">Abrir em outra aba</a>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 pt-4 border-t border-gray-100">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">Não aceito — sair</button>
            </form>
        </div>
    </div>
</x-portal-layout>
