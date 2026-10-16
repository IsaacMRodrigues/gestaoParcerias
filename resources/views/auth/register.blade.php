<x-guest-layout>
    <x-slot name="titulo">Solicitar acesso</x-slot>
    <x-slot name="subtitulo">Para servidores da Prefeitura, inclusive secretários.</x-slot>

    <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-gray-600 mb-6">
        Escolha o seu setor e o que você faz nele. O acesso é liberado depois que o
        <strong>Setor de Convênios e Parcerias (SCP)</strong> conferir os seus dados.
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ setor: @js(old('setor', '')) }" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nome completo" />
            <x-text-input id="name" class="block mt-1.5 w-full py-2.5" type="text" name="name" :value="old('name')"
                          required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1.5 w-full py-2.5" type="email" name="email" :value="old('email')"
                          required autocomplete="username" placeholder="nome@saogoncalo.mg.gov.br" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-input-label for="cpf" value="CPF" />
                <x-text-input id="cpf" class="block mt-1.5 w-full py-2.5" type="text" name="cpf" :value="old('cpf')" autocomplete="off" />
                <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" class="block mt-1.5 w-full py-2.5" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <div class="pt-5 border-t border-gray-100">
            <x-input-label for="setor" value="Setor de lotação" />
            <select id="setor" name="setor" required x-model="setor"
                    class="block mt-1.5 w-full py-2.5 border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm">
                <option value="">Selecione...</option>
                @foreach($setores as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(old('setor') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('setor')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="orgao_id" value="Secretaria / Unidade Gestora" />
            <select id="orgao_id" name="orgao_id"
                    class="block mt-1.5 w-full py-2.5 border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm">
                <option value="">Selecione (obrigatório para Unidade Gestora)...</option>
                @foreach($orgaos as $orgao)
                    <option value="{{ $orgao->id }}" @selected((int) old('orgao_id') === $orgao->id)>
                        {{ $orgao->sigla ? $orgao->sigla . ' — ' : '' }}{{ $orgao->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="matricula" value="Matrícula" />
            <x-text-input id="matricula" class="block mt-1.5 w-full py-2.5" type="text" name="matricula" :value="old('matricula')" autocomplete="off" />
            <x-input-error :messages="$errors->get('matricula')" class="mt-2" />
        </div>

        {{-- Perfis do setor: a lista muda com o setor escolhido. --}}
        <div>
            <x-input-label value="O que você faz no sistema" />
            <p class="text-xs text-gray-500 mt-1" x-show="! setor">Escolha o setor para ver os perfis.</p>
            @foreach($perfisPorSetor as $setor => $perfis)
                @php
                    // O responsável do setor (o secretário, na UG) vem primeiro.
                    $responsavel = \App\Models\User::RESPONSAVEL_DO_SETOR[$setor] ?? null;
                    $perfis = collect($perfis)->sortBy(fn ($r, $slug) => $slug === $responsavel ? 0 : 1);
                @endphp
                <template x-if="setor === @js($setor)">
                    <div class="mt-2 grid sm:grid-cols-2 gap-x-4 gap-y-2">
                        @foreach($perfis as $slug => $rotulo)
                            <label class="flex items-start gap-2.5 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="perfis[]" value="{{ $slug }}" @checked(in_array($slug, old('perfis', []), true))
                                       class="mt-0.5 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
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

        <div class="pt-5 border-t border-gray-100">
            <x-input-label for="password" value="Senha" />
            <x-input-senha id="password" class="mt-1.5" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar senha" />
            <x-input-senha id="password_confirmation" class="mt-1.5" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="solicitacao_obs" value="Observação (opcional)" />
            <textarea id="solicitacao_obs" name="solicitacao_obs" rows="2"
                      class="block mt-1.5 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-sm placeholder:text-gray-400"
                      placeholder="Ex.: função, motivo do acesso...">{{ old('solicitacao_obs') }}</textarea>
            <x-input-error :messages="$errors->get('solicitacao_obs')" class="mt-2" />
        </div>

        <x-primary-button class="w-full !py-3 text-base">
            Solicitar acesso
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </x-primary-button>
    </form>

    <p class="text-sm text-gray-500 text-center mt-6">
        Já tem cadastro?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Entrar</a>
    </p>
    <p class="text-sm text-gray-500 text-center mt-2">
        É uma organização da sociedade civil?
        <a href="{{ route('portal.osc.create') }}" class="font-semibold text-brand-700 hover:underline">Cadastre sua OSC</a>
    </p>
</x-guest-layout>
