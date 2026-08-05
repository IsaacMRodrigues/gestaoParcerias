<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Caixa de Entrada
            <span class="text-sm font-normal text-gray-500 ml-1">
                — {{ \App\Models\Processo::SETORES[$setor] ?? $setor }}
            </span>
        </h2>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recebido de</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($processos as $processo)
                            @php $atual = $processo->tramitacaoAtual(); @endphp
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
                                    {{ $atual ? (\App\Models\Processo::SETORES[$atual->de_setor] ?? $atual->de_setor) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($atual && is_null($atual->recebido_em))
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Novo — aguardando recebimento</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Em análise</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                    <a href="{{ route('processos.show', $processo) }}" class="text-brand-600 hover:text-brand-900">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum processo na sua caixa de entrada.
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
