<x-guest-layout>
    <h1 class="text-lg font-bold text-gray-900">Defina a sua senha</h1>
    <p class="text-sm text-gray-600 mt-1 mb-5">
        A senha com que você entrou foi definida por outra pessoa. Antes de continuar, escolha
        uma que só você conheça.
    </p>

    <form method="POST" action="{{ route('senha.trocar.salvar') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" value="Nova senha" />
            <x-input-senha id="password" class="mt-1.5" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirme a nova senha" />
            <x-input-senha id="password_confirmation" name="password_confirmation" class="mt-1.5" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full !py-3 text-base">Salvar e continuar</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">Sair</button>
    </form>
</x-guest-layout>
