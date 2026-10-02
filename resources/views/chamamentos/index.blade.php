<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Chamamentos Públicos</h2>
            @if(\App\Models\Chamamento::cadastroPermitidoA(auth()->user()))
                <a href="{{ route('chamamentos.create') }}" class="btn btn-primary">+ Novo Chamamento</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @php
                $campo = 'block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
                $temFiltro = filled($filtros['busca'] ?? null) || filled($filtros['orgao_id'] ?? null)
                    || filled($filtros['tipo'] ?? null) || $filtros['situacao'] !== 'abertos';
            @endphp
            <form method="GET" action="{{ route('chamamentos.index') }}"
                  class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div class="lg:col-span-2">
                    <label for="busca" class="block text-xs font-medium text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" id="busca" value="{{ $filtros['busca'] ?? '' }}"
                           placeholder="Número, título ou objeto" class="{{ $campo }}">
                </div>
                <div>
                    <label for="orgao_id" class="block text-xs font-medium text-gray-500 mb-1">Secretaria</label>
                    <select name="orgao_id" id="orgao_id" class="{{ $campo }}">
                        <option value="">Todas</option>
                        @foreach($orgaos as $orgao)
                            <option value="{{ $orgao->id }}" @selected((string) ($filtros['orgao_id'] ?? '') === (string) $orgao->id)>
                                {{ $orgao->sigla ?? $orgao->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tipo" class="block text-xs font-medium text-gray-500 mb-1">Modalidade</label>
                    <select name="tipo" id="tipo" class="{{ $campo }}">
                        <option value="">Todas</option>
                        @foreach(\App\Models\Chamamento::TIPOS as $key => $label)
                            <option value="{{ $key }}" @selected(($filtros['tipo'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="situacao" class="block text-xs font-medium text-gray-500 mb-1">Situação</label>
                    <select name="situacao" id="situacao" class="{{ $campo }}">
                        @foreach(\App\Http\Controllers\ChamamentoController::SITUACOES as $key => $label)
                            <option value="{{ $key }}" @selected($filtros['situacao'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    @if($temFiltro)
                        <a href="{{ route('chamamentos.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Limpar filtros</a>
                    @endif
                    <span class="ml-auto text-xs text-gray-400">{{ $chamamentos->total() }} chamamento(s)</span>
                </div>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Nº / Título</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Secretaria</th>
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
                                    {{ $chamamento->programa?->orgao?->sigla ?? $chamamento->programa?->orgao?->name ?? '—' }}
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
                                    @if($chamamento->cadastroEditavelPor(auth()->user()))
                                        <a href="{{ route('chamamentos.edit', $chamamento) }}"
                                           class="font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                        <form action="{{ route('chamamentos.destroy', $chamamento) }}"
                                              method="POST" class="inline"
                                              data-confirm="Deseja remover este chamamento?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12">
                                    <x-empty-state icone="lista">Nenhum chamamento {{ $filtros['situacao'] === 'abertos' ? 'aberto' : 'encontrado' }}.</x-empty-state>
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
