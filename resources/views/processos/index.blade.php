<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Processos de Planejamento</h2>
            <a href="{{ route('processos.create') }}"
               class="btn btn-primary">
                + Novo Processo
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
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nº Processo</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Unidade Gestora</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Setor Atual</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Aberto em</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($processos as $processo)
                            @php $color = \App\Models\Processo::STATUS_COLORS[$processo->status] ?? 'gray'; @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="{{ route('processos.show', $processo) }}" class="text-brand-600 hover:underline">
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
                                    <a href="{{ route('processos.show', $processo) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">Abrir</a>
                                    <form action="{{ route('processos.destroy', $processo) }}" method="POST" class="inline"
                                          data-confirm="Remover este processo?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="pasta">Nenhum processo de planejamento cadastrado.</x-empty-state>
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
