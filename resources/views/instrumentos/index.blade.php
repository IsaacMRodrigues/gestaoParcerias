<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Instrumentos</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OSC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vigência</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Repasse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($instrumentos as $instrumento)
                            @php $color = \App\Models\Instrumento::STATUS_COLORS[$instrumento->status] ?? 'gray'; @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="{{ route('instrumentos.show', $instrumento) }}" class="text-brand-600 hover:underline">
                                        {{ $instrumento->numero }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $instrumento->proposta->osc->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $instrumento->proposta->chamamento->programa->sigla ?? $instrumento->proposta->chamamento->programa->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $instrumento->data_inicio->format('d/m/Y') }}
                                    a {{ $instrumento->dataFimVigente()->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    R$ {{ number_format($instrumento->valor_repasse, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Instrumento::STATUS[$instrumento->status] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('instrumentos.minuta', $instrumento) }}" target="_blank"
                                       class="text-gray-500 hover:text-gray-800">Minuta</a>
                                    <a href="{{ route('instrumentos.show', $instrumento) }}" class="text-brand-600 hover:text-brand-900">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum instrumento formalizado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($instrumentos->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $instrumentos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
