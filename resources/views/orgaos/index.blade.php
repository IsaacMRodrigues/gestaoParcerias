<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Órgãos / Secretarias</h2>
            <a href="{{ route('orgaos.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Novo Órgão
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sigla</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orgaos as $orgao)
                            <tr>
                                <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $orgao->codigo ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $orgao->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->sigla ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->cnpj ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $orgao->email ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if($orgao->status)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Ativo</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('orgaos.edit', $orgao) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <form action="{{ route('orgaos.destroy', $orgao) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover este órgão?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum órgão cadastrado.
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
