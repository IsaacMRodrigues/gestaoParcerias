<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Programas Governamentais</h2>
            <a href="{{ route('programas.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Novo Programa
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Órgão</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($programas as $programa)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $programa->name }}
                                    @if($programa->sigla)
                                        <span class="ml-1 text-xs text-gray-400">({{ $programa->sigla }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $programa->orgao->sigla ?? $programa->orgao->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \App\Models\Programa::TIPOS[$programa->tipo] ?? $programa->tipo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $programa->valor_total ? 'R$ ' . number_format($programa->valor_total, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $colors = ['ativo' => 'green', 'encerrado' => 'indigo', 'suspenso' => 'red'];
                                        $color = $colors[$programa->status] ?? 'gray';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Programa::STATUS[$programa->status] ?? $programa->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('programas.chamamentos.index', $programa) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                        Chamamentos
                                        <span class="bg-indigo-500 text-white rounded px-1">{{ $programa->chamamentos_count ?? 0 }}</span>
                                    </a>
                                    <a href="{{ route('programas.edit', $programa) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <form action="{{ route('programas.destroy', $programa) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Deseja remover este programa?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum programa cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($programas->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $programas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
