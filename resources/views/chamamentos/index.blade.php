<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('programas.index') }}" class="hover:underline">Programas</a>
                    &rsaquo; {{ $programa->sigla ?? $programa->name }}
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Chamamentos Públicos</h2>
            </div>
            <a href="{{ route('programas.chamamentos.create', $programa) }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Novo Chamamento
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nº / Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Disponível</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrições</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($chamamentos as $chamamento)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($chamamento->numero)
                                        <span class="font-medium">{{ $chamamento->numero }}</span> —
                                    @endif
                                    {{ $chamamento->titulo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \App\Models\Chamamento::TIPOS[$chamamento->tipo] ?? $chamamento->tipo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $chamamento->valor_disponivel ? 'R$ ' . number_format($chamamento->valor_disponivel, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($chamamento->data_inicio_inscricao && $chamamento->data_fim_inscricao)
                                        {{ $chamamento->data_inicio_inscricao->format('d/m/Y') }} a
                                        {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php $color = \App\Models\Chamamento::STATUS_COLORS[$chamamento->status] ?? 'gray'; @endphp
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Chamamento::STATUS[$chamamento->status] ?? $chamamento->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('chamamentos.selecao', $chamamento) }}"
                                       class="text-gray-600 hover:text-gray-900">Seleção</a>
                                    <a href="{{ route('programas.chamamentos.edit', [$programa, $chamamento]) }}"
                                       class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <form action="{{ route('programas.chamamentos.destroy', [$programa, $chamamento]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Deseja remover este chamamento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum chamamento cadastrado para este programa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($chamamentos->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $chamamentos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
