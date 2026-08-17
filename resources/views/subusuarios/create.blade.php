<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('subusuarios.index') }}" class="hover:underline">Meus usuários</a>
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Novo usuário da Secretaria</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-5">
                    O usuário herda a sua Secretaria/Unidade Gestora e fica <strong>pendente</strong> até o
                    administrador do sistema aprovar e definir os perfis. Informe uma senha inicial para repassar a ele.
                </p>

                <form action="{{ route('subusuarios.store') }}" method="POST" class="space-y-4">
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
                    </div>

                    <div>
                        <x-input-label for="matricula" value="Matrícula" />
                        <x-text-input id="matricula" name="matricula" type="text" class="block mt-1 w-full" :value="old('matricula')" required />
                        <x-input-error :messages="$errors->get('matricula')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="cpf" value="CPF" />
                            <x-text-input id="cpf" name="cpf" type="text" class="block mt-1 w-full" :value="old('cpf')" />
                            <x-input-error :messages="$errors->get('cpf')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Telefone" />
                            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" :value="old('phone')" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="password" value="Senha inicial" />
                            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Confirmar senha" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="solicitacao_obs" value="Função / observação" />
                        <textarea id="solicitacao_obs" name="solicitacao_obs" rows="2" required
                                  class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"
                                  placeholder="Ex.: função do usuário, perfis sugeridos...">{{ old('solicitacao_obs') }}</textarea>
                        <x-input-error :messages="$errors->get('solicitacao_obs')" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('subusuarios.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit"
                                class="btn btn-primary">
                            Criar usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
