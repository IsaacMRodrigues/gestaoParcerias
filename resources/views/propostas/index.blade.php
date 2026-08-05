<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Propostas</h2>
            <a href="{{ route('propostas.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-md hover:bg-brand-700">
                + Nova Proposta
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OSC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chamamento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Solicitado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($propostas as $proposta)
                            <tr class="{{ $proposta->status === 'submetida' ? 'bg-amber-50/60' : '' }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="{{ route('propostas.show', $proposta) }}"
                                       class="text-brand-600 hover:underline">
                                        {{ $proposta->titulo }}
                                    </a>
                                    @if($proposta->status === 'submetida')
                                        <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700 rounded-full align-middle">nova</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $proposta->osc->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $proposta->chamamento->numero ?? '' }}
                                    {{ $proposta->chamamento->titulo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php $color = \App\Models\Proposta::STATUS_COLORS[$proposta->status] ?? 'gray'; @endphp
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Proposta::STATUS[$proposta->status] ?? $proposta->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('propostas.show', $proposta) }}" class="text-gray-500 hover:text-gray-800">Ver</a>
                                    <a href="{{ route('propostas.edit', $proposta) }}" class="text-brand-600 hover:text-brand-900">Editar</a>
                                    <form action="{{ route('propostas.destroy', $proposta) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover esta proposta?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhuma proposta cadastrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($propostas->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $propostas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
