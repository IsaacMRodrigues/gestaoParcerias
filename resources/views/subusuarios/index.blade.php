<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Meus usuários</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Usuários da sua Secretaria. Cada cadastro é liberado após aprovação do administrador.
                </p>
            </div>
            <a href="{{ route('subusuarios.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Novo usuário
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white shadow rounded-lg overflow-hidden">
                @php
                    $cores = ['pendente' => 'amber', 'aprovado' => 'green', 'recusado' => 'red'];
                @endphp
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3">E-mail</th>
                            <th class="px-6 py-3">Perfis</th>
                            <th class="px-6 py-3">Situação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($subusuarios as $su)
                            @php $cor = $cores[$su->approval_status] ?? 'gray'; @endphp
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ $su->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $su->email }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $su->roles->pluck('name')->map(fn($r) => \App\Models\User::$roleLabels[$r] ?? $r)->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2.5 py-0.5 text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 rounded-full">
                                        {{ \App\Models\User::APPROVAL[$su->approval_status] ?? $su->approval_status }}
                                    </span>
                                    @if($su->isRecusado() && $su->rejeitado_motivo)
                                        <p class="text-xs text-red-500 mt-1">{{ $su->rejeitado_motivo }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Você ainda não cadastrou nenhum usuário.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
