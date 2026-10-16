<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Solicitar acesso</h2>
        <p class="text-sm text-gray-500 mt-1">
            Para servidores da Prefeitura, inclusive secretários. Escolha o seu setor e o que você faz nele;
            o acesso é liberado depois que o <strong>Setor de Convênios e Parcerias (SCP)</strong> conferir os seus dados.
            OSCs devem usar o
            <a href="{{ route('portal.osc.create') }}" class="text-brand-600 hover:underline">cadastro do portal</a>.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ setor: @js(old('setor', '')) }">
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
            <select id="setor" name="setor" required x-model="setor"
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

        <!-- Matrícula -->
        <div class="mt-4">
            <x-input-label for="matricula" value="Matrícula" />
            <x-text-input id="matricula" class="block mt-1 w-full" type="text" name="matricula" :value="old('matricula')" autocomplete="off" />
            <x-input-error :messages="$errors->get('matricula')" class="mt-2" />
        </div>

        <!-- Perfis do setor: a lista muda com o setor escolhido -->
        <div class="mt-4">
            <x-input-label value="O que você faz no sistema" />
            <p class="text-xs text-gray-500 mt-0.5" x-show="! setor">Escolha o setor para ver os perfis.</p>
            @foreach($perfisPorSetor as $setor => $perfis)
                @php
                    // O responsável do setor (o secretário, na UG) vem primeiro.
                    $responsavel = \App\Models\User::RESPONSAVEL_DO_SETOR[$setor] ?? null;
                    $perfis = collect($perfis)->sortBy(fn ($r, $slug) => $slug === $responsavel ? 0 : 1);
                @endphp
                <template x-if="setor === @js($setor)">
                    <div class="mt-1.5 grid sm:grid-cols-2 gap-x-4 gap-y-1.5">
                        @foreach($perfis as $slug => $rotulo)
                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="perfis[]" value="{{ $slug }}" @checked(in_array($slug, old('perfis', []), true))
                                       class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>
                                    {{ $rotulo }}
                                    @if($slug === $responsavel)<span class="block text-xs text-gray-500">o secretário ou responsável pelo setor</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </template>
            @endforeach
            <p class="text-xs text-gray-500 mt-2" x-show="setor">
                Gestor da Parceria e as Comissões são encargos por portaria: peça só se já foi designado.
            </p>
            <x-input-error :messages="$errors->get('perfis')" class="mt-2" />
            <x-input-error :messages="$errors->get('perfis.*')" class="mt-1" />
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
