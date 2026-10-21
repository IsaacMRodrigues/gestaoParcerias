<x-portal-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <p class="text-sm text-gray-500">
            <a href="{{ route('portal.usuarios.index') }}" class="hover:underline">Usuários da Organização</a>
        </p>
        <h1 class="text-2xl font-bold text-gray-900 mt-0.5 mb-6">Cadastrar usuário</h1>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-gray-600 mb-5">
                O cadastro fica <strong>pendente</strong> até o Setor de Convênios e Parcerias (SCP) da Prefeitura
                aprovar. Depois de liberada, a pessoa atua em nome de <strong>{{ $osc->name }}</strong>.
            </div>

            <form action="{{ route('portal.usuarios.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Nome completo" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-1">É com este e-mail que a pessoa entra no portal.</p>
                </div>

                <div>
                    <x-input-label for="cargo" value="Função na organização (opcional)" />
                    <x-text-input id="cargo" name="cargo" type="text" class="block mt-1 w-full" :value="old('cargo')"
                                  placeholder="Ex.: Coordenadora de projetos" />
                    <x-input-error :messages="$errors->get('cargo')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="cpf" value="CPF (opcional)" />
                        <x-text-input id="cpf" name="cpf" type="text" class="block mt-1 w-full" :value="old('cpf')" />
                        <x-input-error :messages="$errors->get('cpf')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telefone (opcional)" />
                        <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" :value="old('phone')" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                </div>


                {{-- Perfil ≠ função. O perfil diz o que a pessoa É na
                     organização e sai impresso como papel de assinatura; as
                     funções, logo abaixo, dizem o que ela pode fazer. --}}
                <div class="pt-2">
                    <x-input-label value="Perfil" />
                    <p class="text-xs text-gray-500 mt-0.5 mb-2">
                        O que a pessoa é na organização. É este nome que aparece como
                        <strong>papel de assinatura</strong> nos documentos que ela assinar.
                        Pode marcar mais de um.
                    </p>
                    <div class="space-y-2 border border-gray-200 rounded-lg p-3">
                        @foreach($perfis as $chave => $perfil)
                            <label class="flex items-start gap-2.5 text-sm text-gray-700">
                                <input type="checkbox" name="perfis[]" value="{{ $chave }}"
                                       @checked(($perfil['fixo'] ?? false) || in_array($chave, old('perfis', [])))
                                       @disabled($perfil['fixo'] ?? false)
                                       class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 disabled:opacity-60">
                                <span>
                                    {{ $perfil['rotulo'] }}
                                    <span class="block text-xs text-gray-500">{{ $perfil['ajuda'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('perfis')" class="mt-1" />
                </div>

                <div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="password" value="Senha inicial" />
                            <x-input-senha id="password" class="mt-1" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Confirmar senha" />
                            <x-input-senha id="password_confirmation" class="mt-1" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Repasse à pessoa: no primeiro acesso, o sistema pede que ela a troque por uma sua.</p>
                </div>

                <p class="text-xs text-gray-500 pt-1">
                    A pessoa entra podendo trabalhar em tudo o que a organização faz no portal — dá para
                    restringir depois, no "Alterar" da lista da equipe. <strong>Submeter proposta,
                    protocolar recurso e assinar o Termo</strong> continuam só com você: são atos que
                    vinculam juridicamente a organização.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('portal.usuarios.index') }}" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>

    </div>
</x-portal-layout>
