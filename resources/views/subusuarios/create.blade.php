<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('subusuarios.index') }}" class="hover:underline">Meus usuários</a>
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
                Cadastrar usuário
                <span class="text-sm font-normal text-gray-500 ml-1">— {{ auth()->user()->setorLabel() }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                {{-- Os perfis são escolhidos aqui, por quem conhece a função da pessoa; a SCP aprova ou recusa. --}}
                <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-gray-600 mb-5">
                    A pessoa entra no seu setor{{ auth()->user()->orgao ? ' e na sua Secretaria' : '' }} e fica
                    <strong>pendente</strong> até o Setor de Convênios e Parcerias (SCP) aprovar, com os perfis que
                    você escolher abaixo.
                </div>

                <form action="{{ route('subusuarios.store') }}" method="POST" class="space-y-5">
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

                    <div>
                        <x-input-label for="solicitacao_obs" value="Função no setor" />
                        <textarea id="solicitacao_obs" name="solicitacao_obs" rows="2" required
                                  class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500 placeholder:text-gray-400"
                                  placeholder="Ex.: coordena o almoxarifado da Secretaria">{{ old('solicitacao_obs') }}</textarea>
                        <x-input-error :messages="$errors->get('solicitacao_obs')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Perfis de acesso" />
                        <p class="text-xs text-gray-500 mt-0.5 mb-2">
                            O que a pessoa faz no setor. Administrador, auditores e Gabinete são concedidos só pelo administrador.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 border border-gray-200 rounded-lg p-3">
                            @foreach($perfis as $slug => $rotulo)
                                <label class="flex items-start gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="perfis[]" value="{{ $slug }}"
                                           @checked(in_array($slug, old('perfis', [])))
                                           class="mt-0.5 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                    <span>{{ $rotulo }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <strong>Gestor da Parceria, Comissão de Seleção e Comissão de Monitoramento e Avaliação</strong>
                            não se acumulam: a mesma pessoa só pode ter um desses encargos.
                        </p>
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
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Repasse à pessoa: no primeiro acesso, o sistema pede que ela a troque por uma sua.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('subusuarios.index') }}" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
