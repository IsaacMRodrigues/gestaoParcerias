<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                @canany(['cadastros', 'ver_usuarios'])
                    <p class="text-sm text-gray-500">
                        <a href="{{ route('orgaos.index') }}" class="hover:underline">Órgãos e usuários</a>
                    </p>
                @endcanany
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
                                @if($u->osc_id)
                                    <span>Organização: <strong class="text-gray-700">{{ $u->osc->name ?? '—' }}</strong></span>
                                @else
                                    <span>Setor: <strong class="text-gray-700">{{ \App\Models\User::LOTACOES[$u->setor] ?? '—' }}</strong></span>
                                    <span>Secretaria/UG: <strong class="text-gray-700">{{ $u->orgao->name ?? '—' }}</strong></span>
                                @endif
                                @if($u->matricula)<span>Matrícula: <strong class="text-gray-700">{{ $u->matricula }}</strong></span>@endif
                                @if($u->cpf)<span>CPF: {{ $u->cpf }}</span>@endif
                                @if($u->phone)<span>Tel.: {{ $u->phone }}</span>@endif
                                <span>Solicitado em {{ $u->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($u->criadoPor)
                                <p class="mt-1 text-xs text-brand-600">
                                    @if($u->osc_id)
                                        Cadastrado por {{ $u->criadoPor->name }} — responsável legal da organização.
                                    @else
                                        Cadastrado por {{ $u->criadoPor->name }} — chefia do setor
                                        {{ $u->criadoPor->setorLabel() ?: '—' }}.
                                    @endif
                                </p>
                            @endif
                            @if($u->solicitacao_obs)
                                <p class="mt-2 text-sm text-gray-600 bg-gray-50 rounded p-2">"{{ $u->solicitacao_obs }}"</p>
                            @endif
                        </div>
                    </div>

                    {{-- Aprovar/recusar apenas. Os perfis vêm do cadastro (pedidos pela pessoa
                         ou indicados pela chefia). Aqui a SCP ou o administrador confere a
                         identidade e decide — não redefine. --}}
                    <form action="{{ route('usuarios.aprovar', $u) }}" method="POST" class="mt-4 border-t border-gray-100 pt-4 space-y-3">
                        @csrf @method('PATCH')

                        @php
                            $perfis = $u->roles->pluck('name')->all();
                            // Pede para ser o responsável de um setor (o secretário, na UG) que já tem um ativo?
                            $jaTem = collect(\App\Models\User::RESPONSAVEL_DO_SETOR)
                                ->filter(fn ($perfil, $setor) => in_array($perfil, $perfis, true) && $u->setor === $setor)
                                ->flatMap(fn ($perfil, $setor) => \App\Models\User::role($perfil)
                                    ->where('status', true)->where('approval_status', 'aprovado')->whereKeyNot($u->id)
                                    ->when($setor === 'ug', fn ($q) => $q->where('orgao_id', $u->orgao_id))
                                    ->get(['id', 'name', 'email']));
                        @endphp

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
                                <p class="text-xs text-gray-500 mt-1.5">
                                    {{ $u->criadoPor ? 'Indicados por ' . $u->criadoPor->name . '.' : 'Pedidos pela própria pessoa no cadastro: confira a identidade antes de aprovar.' }}
                                </p>
                            @else
                                {{-- Auto-cadastro não passa por chefe: ninguém indicou perfis.
                                     Aprovar aqui libera o login; os perfis se definem depois em
                                     Cadastros → Usuários, que é onde eles moram. --}}
                                <p class="mt-1 text-sm text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                    Nenhum perfil indicado — este cadastro não veio de um responsável de setor.
                                    Ao aprovar, a pessoa entra sem acesso a nenhum módulo; os perfis se definem em
                                    @can('cadastros')<a href="{{ route('usuarios.edit', $u) }}" class="underline font-medium">Cadastros → Usuários</a>@else Cadastros → Usuários, pelo administrador @endcan.
                                </p>
                            @endif
                            @if($jaTem->isNotEmpty())
                                <p class="mt-2 text-sm text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                    {{ $u->setor === 'ug' ? ($u->orgao->name ?? 'Esta Secretaria') : (\App\Models\User::LOTACOES[$u->setor] ?? 'Este setor') }}
                                    já tem responsável ativo: {{ $jaTem->map(fn ($x) => $x->name . ' (' . $x->email . ')')->implode(', ') }}.
                                    Aprovar é uma substituição — confira com a Secretaria e, se for o caso, desative a conta anterior.
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
