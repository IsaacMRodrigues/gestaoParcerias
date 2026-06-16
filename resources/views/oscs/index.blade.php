<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">OSCs</h2>
            <a href="{{ route('oscs.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                + Nova OSC
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
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
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Ativa</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Inativa</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('oscs.edit', $osc) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <form action="{{ route('oscs.destroy', $osc) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Deseja remover esta OSC?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhuma OSC cadastrada.
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
