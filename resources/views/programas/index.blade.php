<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Programas Governamentais</h2>
            <a href="{{ route('programas.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                + Novo Programa
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            {{-- Filtros --}}
            @php $temFiltro = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(); @endphp
            <form method="GET" action="{{ route('programas.index') }}"
                  class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div class="lg:col-span-2">
                    <label for="busca" class="block text-xs font-medium text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" id="busca" value="{{ $filtros['busca'] ?? '' }}"
                           placeholder="Nome ou sigla do programa"
                           class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="orgao_id" class="block text-xs font-medium text-gray-500 mb-1">Órgão</label>
                    <select name="orgao_id" id="orgao_id"
                            class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todos</option>
                        @foreach($orgaos as $orgao)
                            <option value="{{ $orgao->id }}" {{ (string) ($filtros['orgao_id'] ?? '') === (string) $orgao->id ? 'selected' : '' }}>
                                {{ $orgao->sigla ?? $orgao->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tipo" class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                    <select name="tipo" id="tipo"
                            class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Programa::TIPOS as $key => $label)
                            <option value="{{ $key }}" {{ ($filtros['tipo'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status" id="status"
                            class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Programa::STATUS as $key => $label)
                            <option value="{{ $key }}" {{ ($filtros['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                        Filtrar
                    </button>
                    @if($temFiltro)
                        <a href="{{ route('programas.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Limpar filtros</a>
                    @endif
                    <span class="ml-auto text-xs text-gray-400">{{ $programas->total() }} programa(s)</span>
                </div>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Programa</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Órgão</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Valor Total</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($programas as $programa)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $programa->name }}
                                    @if($programa->sigla)
                                        <span class="ml-1 text-xs text-gray-400">({{ $programa->sigla }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $programa->orgao->sigla ?? $programa->orgao->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \App\Models\Programa::TIPOS[$programa->tipo] ?? $programa->tipo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $programa->valor_total ? 'R$ ' . number_format($programa->valor_total, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $colors = ['ativo' => 'green', 'encerrado' => 'brand', 'suspenso' => 'red'];
                                        $color = $colors[$programa->status] ?? 'gray';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                        {{ \App\Models\Programa::STATUS[$programa->status] ?? $programa->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3 whitespace-nowrap">
                                    <a href="{{ route('programas.chamamentos.index', $programa) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-white bg-brand-600 rounded-md hover:bg-brand-700">
                                        Chamamentos
                                        <span class="bg-brand-500 text-white rounded px-1">{{ $programa->chamamentos_count ?? 0 }}</span>
                                    </a>
                                    <a href="{{ route('programas.edit', $programa) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                    <form action="{{ route('programas.destroy', $programa) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover este programa?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <x-empty-state icone="lista">{{ $temFiltro ? 'Nenhum programa encontrado com os filtros aplicados.' : 'Nenhum programa cadastrado.' }}</x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($programas->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $programas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
