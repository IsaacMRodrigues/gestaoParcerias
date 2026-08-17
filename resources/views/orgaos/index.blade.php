<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Órgãos / Secretarias</h2>
            <a href="{{ route('orgaos.create') }}"
               class="btn btn-primary">
                + Novo Órgão
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Sigla</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">CNPJ</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($orgaos as $orgao)
                            <tr>
                                <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $orgao->codigo ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $orgao->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->sigla ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->cnpj ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->email ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if($orgao->status)
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200 rounded-md">Ativo</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-red-50 text-red-800 border border-red-200 rounded-md">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('orgaos.edit', $orgao) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                    <form action="{{ route('orgaos.destroy', $orgao) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover este órgão?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12">
                                    <x-empty-state icone="lista">Nenhum órgão cadastrado.</x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($orgaos->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $orgaos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
