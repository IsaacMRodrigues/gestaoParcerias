<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Processos de Planejamento</h2>
            <a href="{{ route('processos.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Novo Processo
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nº Processo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidade Gestora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setor Atual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aberto em</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($processos as $processo)
                            @php $color = \App\Models\Processo::STATUS_COLORS[$processo->status] ?? 'gray'; @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="{{ route('processos.show', $processo) }}" class="text-indigo-600 hover:underline">
                                        {{ $processo->numero }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $processo->orgao->sigla ?? $processo->orgao->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \App\Models\Processo::SETORES[$processo->setor_atual] ?? $processo->setor_atual }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Processo::STATUS[$processo->status] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $processo->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('processos.show', $processo) }}" class="text-indigo-600 hover:text-indigo-900">Abrir</a>
                                    <form action="{{ route('processos.destroy', $processo) }}" method="POST" class="inline"
                                          data-confirm="Remover este processo?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum processo de planejamento cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($processos->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $processos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
