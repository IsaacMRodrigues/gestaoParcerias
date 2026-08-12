<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Usuários</h2>
            <a href="{{ route('usuarios.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                + Novo Usuário
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <x-flash-message />

            @if(($pendentesCount ?? 0) > 0)
                <a href="{{ route('usuarios.pendentes') }}"
                   class="flex items-center justify-between mb-4 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm hover:bg-amber-100">
                    <span class="text-amber-800">
                        <strong>{{ $pendentesCount }}</strong> cadastro(s) aguardando aprovação.
                    </span>
                    <span class="text-amber-700 font-medium">Revisar →</span>
                </a>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">CPF</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Perfil</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $user->cpf ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @php $role = $user->roles->first() @endphp
                                    @if($role)
                                        {{-- Perfil em cinza: a cor fica reservada ao status,
                                             que é o que precisa saltar aos olhos. --}}
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200 rounded-md">
                                            {{ \App\Models\User::$roleLabels[$role->name] ?? $role->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($user->status)
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-green-50 text-green-800 border border-green-200 rounded-md">Ativo</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-red-50 text-red-800 border border-red-200 rounded-md">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('usuarios.edit', $user) }}"
                                       class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>

                                    <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover este usuário?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="pessoas">Nenhum usuário cadastrado.</x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
