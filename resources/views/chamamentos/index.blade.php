<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('programas.index') }}" class="hover:underline">Programas</a>
                    &rsaquo; {{ $programa->sigla ?? $programa->name }}
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Chamamentos Públicos</h2>
            </div>
            <a href="{{ route('programas.chamamentos.create', $programa) }}"
               class="btn btn-primary">
                + Novo Chamamento
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
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nº / Título</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Valor Disponível</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Inscrições</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($chamamentos as $chamamento)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($chamamento->numero)
                                        <span class="font-medium">{{ $chamamento->numero }}</span> —
                                    @endif
                                    {{ $chamamento->titulo }}
                                    @if($chamamento->processo)
                                        <a href="{{ route('processos.show', $chamamento->processo) }}"
                                           class="block text-xs text-brand-600 hover:underline mt-0.5">
                                            &larr; originado do Processo {{ $chamamento->processo->numero }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <x-selo-modalidade :tipo="$chamamento->tipo" />
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
                                       class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                    <form action="{{ route('programas.chamamentos.destroy', [$programa, $chamamento]) }}"
                                          method="POST" class="inline"
                                          data-confirm="Deseja remover este chamamento?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="lista">Nenhum chamamento cadastrado para este programa.</x-empty-state>
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
