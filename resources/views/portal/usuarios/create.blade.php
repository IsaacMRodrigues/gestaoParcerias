<x-portal-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <p class="text-sm text-gray-500">
            <a href="{{ route('portal.usuarios.index') }}" class="hover:underline">Usuários da Organização</a>
        </p>
        <h1 class="text-2xl font-bold text-gray-900 mt-0.5 mb-6">Cadastrar usuário</h1>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-500 mb-5">
                O acesso vale imediatamente — não depende de aprovação da Prefeitura. Defina uma senha
                inicial e repasse à pessoa; ela atuará em nome de <strong>{{ $osc->name }}</strong>
                dentro do que você marcar abaixo.
            </p>

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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="password" value="Senha inicial" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar senha" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>
                </div>

                {{-- Marcadas por padrão as duas do dia a dia: é o que qualquer
                     integrante podia fazer antes de as funções existirem, e o
                     cadastro não vira armadilha para quem passar direto. --}}
                <div class="pt-2">
                    <x-input-label value="Funções no sistema" />
                    <p class="text-xs text-gray-500 mt-0.5 mb-2">
                        Ver as propostas e o andamento é de toda a equipe. O que estiver marcado aqui é o
                        que esta pessoa poderá <strong>fazer</strong>.
                    </p>
                    <div class="space-y-2">
                        @foreach($funcoes as $chave => $funcao)
                            <label class="flex items-start gap-2.5 text-sm text-gray-700">
                                <input type="checkbox" name="funcoes[]" value="{{ $chave }}"
                                       @checked(in_array($chave, old('funcoes', ['osc_propostas', 'osc_documentos'])))
                                       class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>
                                    {{ $funcao['rotulo'] }}
                                    <span class="block text-xs text-gray-500">{{ $funcao['ajuda'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('funcoes')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-2">
                        Sem nenhuma marcada, a pessoa apenas acompanha — vê as propostas da organização e
                        não altera nada. <strong>Submeter proposta, protocolar recurso e assinar o Termo</strong>
                        continuam só com você: são atos que vinculam juridicamente a organização.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('portal.usuarios.index') }}" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>

    </div>
</x-portal-layout>
