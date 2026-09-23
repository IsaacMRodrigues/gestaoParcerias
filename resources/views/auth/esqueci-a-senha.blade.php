<x-guest-layout>
    <h1 class="text-lg font-bold text-gray-900">Esqueci minha senha</h1>
    <p class="text-sm text-gray-600 mt-1 mb-5">
        O pedido vai para a equipe de suporte. Ela confirma que é você pelo contato informado e
        passa uma senha provisória, que você troca no primeiro acesso.
    </p>

    <form method="POST" action="{{ route('senha.pedido') }}" class="space-y-4">
        @csrf

        {{-- Campo-isca: fora da tela e fora da navegação por teclado. Só robô
             preenche — ver PedidoDeSenhaController. --}}
        <div class="absolute -left-[9999px]" aria-hidden="true">
            <label for="site">Não preencha</label>
            <input id="site" type="text" name="site" tabindex="-1" autocomplete="off">
        </div>

        <div>
            <x-input-label for="identificacao" value="E-mail ou usuário com que você entra" />
            <x-text-input id="identificacao" class="block mt-1.5 w-full" type="text" name="identificacao"
                          :value="old('identificacao')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('identificacao')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nome" value="Seu nome" />
            <x-text-input id="nome" class="block mt-1.5 w-full" type="text" name="nome"
                          :value="old('nome')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('nome')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="contato" value="Telefone ou e-mail para contato" />
            <x-text-input id="contato" class="block mt-1.5 w-full" type="text" name="contato"
                          :value="old('contato')" required placeholder="(31) 90000-0000" />
            <p class="text-xs text-gray-500 mt-1">É por ele que a equipe confirma que o pedido é seu.</p>
            <x-input-error :messages="$errors->get('contato')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="mensagem" value="Observação (opcional)" />
            <textarea id="mensagem" name="mensagem" rows="3" maxlength="1000"
                      class="mt-1.5 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('mensagem') }}</textarea>
        </div>

        <x-primary-button class="w-full !py-3 text-base">Enviar pedido</x-primary-button>
    </form>

    <p class="text-sm text-gray-500 text-center mt-6">
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Voltar para a entrada</a>
    </p>
</x-guest-layout>
