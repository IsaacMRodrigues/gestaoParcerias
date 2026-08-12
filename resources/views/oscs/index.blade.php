<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">OSCs</h2>
            <a href="{{ route('oscs.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                + Nova OSC
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
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">CNPJ</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Responsável</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($oscs as $osc)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $osc->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $osc->cnpj }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \App\Models\Osc::TIPOS[$osc->tipo] ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $osc->resp_nome ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if($osc->status)
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-green-50 text-green-800 border border-green-200 rounded-md">Ativa</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-red-50 text-red-800 border border-red-200 rounded-md">Inativa</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('oscs.edit', $osc) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                    <form action="{{ route('oscs.destroy', $osc) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover esta OSC?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="pessoas">Nenhuma OSC cadastrada.</x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($oscs->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $oscs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
