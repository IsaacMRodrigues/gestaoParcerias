<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('usuarios.index') }}" class="hover:underline">Usuários</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Cadastros pendentes de aprovação</h2>
            </div>
            <span class="px-3 py-1.5 text-sm font-medium bg-accent-50 text-accent-700 rounded-full">
                {{ $pendentes->total() }} pendente(s)
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @forelse($pendentes as $u)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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
                                <p class="mt-1 text-xs text-brand-600">Subusuário criado por {{ $u->criadoPor->name }} (UG).</p>
                            @endif
                            @if($u->solicitacao_obs)
                                <p class="mt-2 text-sm text-gray-600 bg-gray-50 rounded p-2">"{{ $u->solicitacao_obs }}"</p>
                            @endif
                        </div>
                    </div>

                    {{-- Aprovar/recusar apenas. Os perfis vêm de quem cadastrou
                         (que sabe a função da pessoa) e o setor, do cadastro. Aqui
                         o administrador confere e decide — não redefine. --}}
                    <form action="{{ route('usuarios.aprovar', $u) }}" method="POST" class="mt-4 border-t border-gray-100 pt-4 space-y-3">
                        @csrf @method('PATCH')

                        @php $perfis = $u->roles->pluck('name')->all(); @endphp

                        <div>
                            <x-input-label value="Perfis solicitados" />
                            @if($perfis)
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @foreach($perfis as $perfil)
                                        <span class="px-2 py-1 text-xs font-medium bg-brand-50 text-brand-800 ring-1 ring-brand-100 rounded-full">
                                            {{ \App\Models\User::$roleLabels[$perfil] ?? $perfil }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($u->criadoPor)
                                    <p class="text-xs text-gray-500 mt-1.5">Indicados por {{ $u->criadoPor->name }}.</p>
                                @endif
                            @else
                                {{-- Auto-cadastro não passa por chefe: ninguém indicou perfis.
                                     Aprovar aqui libera o login; os perfis se definem depois em
                                     Cadastros → Usuários, que é onde eles moram. --}}
                                <p class="mt-1 text-sm text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                    Nenhum perfil indicado — este cadastro não veio de um responsável de setor.
                                    Ao aprovar, a pessoa entra sem acesso a nenhum módulo; defina os perfis em
                                    <a href="{{ route('usuarios.edit', $u) }}" class="underline font-medium">Cadastros → Usuários</a>.
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('roles')" class="mt-1" />
                        </div>

                        <button type="submit"
                                class="btn btn-primary">
                            ✓ Aprovar e liberar acesso
                        </button>
                    </form>

                    {{-- Recusar --}}
                    <form action="{{ route('usuarios.recusar', $u) }}" method="POST" class="mt-3 pt-3 border-t border-gray-100"
                          data-confirm="Recusar este cadastro?">
                        @csrf @method('PATCH')
                        <x-input-label value="Recusar (informe o motivo)" />
                        <div class="flex gap-2 mt-1">
                            <input type="text" name="rejeitado_motivo"
                                   class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"
                                   placeholder="Motivo da recusa...">
                            <button type="submit"
                                    class="btn btn-danger-outline btn-sm">
                                Recusar
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('rejeitado_motivo')" class="mt-1" />
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
                    <span class="text-3xl">✅</span>
                    <p class="mt-2 text-gray-600">Nenhum cadastro pendente de aprovação.</p>
                </div>
            @endforelse

            {{ $pendentes->links() }}
        </div>
    </div>
</x-app-layout>
