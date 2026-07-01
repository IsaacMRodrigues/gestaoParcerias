<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('usuarios.index') }}" class="hover:underline">Usuários</a>
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Cadastros pendentes de aprovação</h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-amber-50 text-amber-700 rounded-full">
                {{ $pendentes->total() }} pendente(s)
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @forelse($pendentes as $u)
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-semibold text-gray-900">{{ $u->name }}</p>
                            <p class="text-sm text-gray-500">{{ $u->email }}</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span>Setor: <strong class="text-gray-700">{{ \App\Models\User::LOTACOES[$u->setor] ?? '—' }}</strong></span>
                                <span>Secretaria/UG: <strong class="text-gray-700">{{ $u->orgao->name ?? '—' }}</strong></span>
                                @if($u->matricula)<span>Matrícula: <strong class="text-gray-700">{{ $u->matricula }}</strong></span>@endif
                                @if($u->cpf)<span>CPF: {{ $u->cpf }}</span>@endif
                                @if($u->phone)<span>Tel.: {{ $u->phone }}</span>@endif
                                <span>Solicitado em {{ $u->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($u->criadoPor)
                                <p class="mt-1 text-xs text-indigo-600">Subusuário criado por {{ $u->criadoPor->name }} (UG).</p>
                            @endif
                            @if($u->solicitacao_obs)
                                <p class="mt-2 text-sm text-gray-600 bg-gray-50 rounded p-2">"{{ $u->solicitacao_obs }}"</p>
                            @endif
                        </div>
                    </div>

                    {{-- Aprovar: atribuir perfis + confirmar setor/UG --}}
                    <form action="{{ route('usuarios.aprovar', $u) }}" method="POST" class="mt-4 border-t border-gray-100 pt-4 space-y-3">
                        @csrf @method('PATCH')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Setor de lotação" />
                                <select name="setor" class="mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">—</option>
                                    @foreach(\App\Models\User::LOTACOES as $valor => $rotulo)
                                        <option value="{{ $valor }}" @selected($u->setor === $valor)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Secretaria / Unidade Gestora" />
                                <select name="orgao_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">—</option>
                                    @foreach($orgaos as $orgao)
                                        <option value="{{ $orgao->id }}" @selected($u->orgao_id === $orgao->id)>
                                            {{ $orgao->sigla ? $orgao->sigla . ' — ' : '' }}{{ $orgao->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Perfis a atribuir" />
                            <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
                                @foreach($roles as $role)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ \App\Models\User::$roleLabels[$role->name] ?? $role->name }}
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('roles')" class="mt-1" />
                        </div>

                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            ✓ Aprovar e liberar acesso
                        </button>
                    </form>

                    {{-- Recusar --}}
                    <form action="{{ route('usuarios.recusar', $u) }}" method="POST" class="mt-3 pt-3 border-t border-gray-100"
                          onsubmit="return confirm('Recusar este cadastro?')">
                        @csrf @method('PATCH')
                        <x-input-label value="Recusar (informe o motivo)" />
                        <div class="flex gap-2 mt-1">
                            <input type="text" name="rejeitado_motivo"
                                   class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"
                                   placeholder="Motivo da recusa...">
                            <button type="submit"
                                    class="px-3 py-1.5 text-sm font-medium text-red-700 border border-red-300 rounded-md hover:bg-red-50 whitespace-nowrap">
                                Recusar
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('rejeitado_motivo')" class="mt-1" />
                    </form>
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-10 text-center">
                    <span class="text-3xl">✅</span>
                    <p class="mt-2 text-gray-600">Nenhum cadastro pendente de aprovação.</p>
                </div>
            @endforelse

            {{ $pendentes->links() }}
        </div>
    </div>
</x-app-layout>
