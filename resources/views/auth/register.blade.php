<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Solicitar acesso</h2>
        <p class="text-sm text-gray-500 mt-1">
            Para servidores internos. Seu acesso será liberado após a <strong>aprovação do administrador</strong>,
            que também definirá seus perfis. OSCs devem usar o
            <a href="{{ route('portal.osc.create') }}" class="text-brand-600 hover:underline">cadastro do portal</a>.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Nome -->
        <div>
            <x-input-label for="name" value="Nome completo" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- E-mail -->
        <div class="mt-4">
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- CPF + Telefone -->
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div>
                <x-input-label for="cpf" value="CPF" />
                <x-text-input id="cpf" class="block mt-1 w-full" type="text" name="cpf" :value="old('cpf')" autocomplete="off" />
                <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <!-- Setor de lotação -->
        <div class="mt-4">
            <x-input-label for="setor" value="Setor de lotação" />
            <select id="setor" name="setor" required
                    class="block mt-1 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                <option value="">Selecione...</option>
                @foreach($setores as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(old('setor') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('setor')" class="mt-2" />
        </div>

        <!-- Secretaria / Unidade Gestora -->
        <div class="mt-4">
            <x-input-label for="orgao_id" value="Secretaria / Unidade Gestora" />
            <select id="orgao_id" name="orgao_id"
                    class="block mt-1 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                <option value="">Selecione (obrigatório para Unidade Gestora)...</option>
                @foreach($orgaos as $orgao)
                    <option value="{{ $orgao->id }}" @selected((int) old('orgao_id') === $orgao->id)>
                        {{ $orgao->sigla ? $orgao->sigla . ' — ' : '' }}{{ $orgao->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-4">
            <x-input-label for="password" value="Senha" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar senha -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar senha" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Justificativa -->
        <div class="mt-4">
            <x-input-label for="solicitacao_obs" value="Justificativa / observação (opcional)" />
            <textarea id="solicitacao_obs" name="solicitacao_obs" rows="2"
                      class="block mt-1 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm text-sm"
                      placeholder="Ex.: função, motivo do acesso...">{{ old('solicitacao_obs') }}</textarea>
            <x-input-error :messages="$errors->get('solicitacao_obs')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500" href="{{ route('login') }}">
                Já tem cadastro?
            </a>

            <x-primary-button class="ms-4">
                Solicitar acesso
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
