<x-portal-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meus dados e senha</h1>
            <p class="text-sm text-gray-500 mt-1">
                Sua conta em
                <strong>{{ $usuario->oscVinculada()?->name ?? 'sua organização' }}</strong>.
            </p>
        </div>

        <x-flash-message />

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">Dados</h2>
            <p class="text-xs text-gray-500 mt-0.5 mb-4">
                O nome aqui é o que sai como assinatura nos documentos que você assinar daqui em
                diante — o que já foi assinado guarda o nome de então.
            </p>

            <form method="POST" action="{{ route('portal.perfil.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" value="Nome completo" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                                  :value="old('name', $usuario->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="phone" value="Telefone" />
                    <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full"
                                  :value="old('phone', $usuario->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <div>
                    <x-input-label value="E-mail de acesso" />
                    <p class="mt-1 text-sm text-gray-600">{{ $usuario->email }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        É por ele que você entra. Para trocar, fale com quem administra os usuários
                        da organização.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">Senha</h2>
            <p class="text-xs text-gray-500 mt-0.5 mb-4">
                Se a senha foi definida por quem cadastrou você, troque-a: ela é conhecida por outra
                pessoa.
            </p>

            <form method="POST" action="{{ route('portal.perfil.senha') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Senha atual" />
                    <x-text-input id="current_password" name="current_password" type="password"
                                  class="block mt-1 w-full" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="password" value="Nova senha" />
                        <x-text-input id="password" name="password" type="password"
                                      class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar nova senha" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                      class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Alterar senha</button>
                </div>
            </form>
        </div>

    </div>
</x-portal-layout>
