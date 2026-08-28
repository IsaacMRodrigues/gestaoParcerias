<x-app-layout>
    <x-slot name="header">
        {{-- Sem botão de criar: a proposta nasce no portal, pela OSC. --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Propostas</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Apresentadas pelas OSCs no portal. Aqui elas são analisadas.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Proposta</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">OSC</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Chamamento</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Valor Solicitado</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($propostas as $proposta)
                            <tr class="{{ $proposta->status === 'submetida' ? 'bg-accent-50/60' : '' }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="{{ route('propostas.show', $proposta) }}"
                                       class="text-brand-600 hover:underline">
                                        {{ $proposta->titulo }}
                                    </a>
                                    @if($proposta->status === 'submetida')
                                        <span class="ml-1.5 px-1.5 py-0.5 text-[11px] font-semibold bg-accent-100 text-accent-700 rounded-full align-middle">nova</span>
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
                                    <a href="{{ route('propostas.show', $proposta) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">Analisar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="pasta">Nenhuma proposta apresentada até agora.</x-empty-state>
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
