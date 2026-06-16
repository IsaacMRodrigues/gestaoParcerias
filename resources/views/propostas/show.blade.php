<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
                </p>
                <h2 class="text-xl font-semibold text-gray-800 mt-0.5">{{ $proposta->titulo }}</h2>
            </div>
            <div class="flex items-center gap-3">
                @if($proposta->status === 'rascunho')
                    <form action="{{ route('propostas.submeter', $proposta) }}" method="POST"
                          onsubmit="return confirm('Confirma a submissão desta proposta?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            Submeter Proposta
                        </button>
                    </form>
                @endif
                <a href="{{ route('propostas.edit', $proposta) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Dados da Proposta --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Dados da Proposta</h3>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Chamamento</dt>
                        <dd class="text-gray-900 font-medium">
                            {{ $proposta->chamamento->numero ? $proposta->chamamento->numero . ' — ' : '' }}
                            {{ $proposta->chamamento->titulo }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Programa</dt>
                        <dd class="text-gray-900">{{ $proposta->chamamento->programa->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">OSC</dt>
                        <dd class="text-gray-900">{{ $proposta->osc->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>
                            @php $color = \App\Models\Proposta::STATUS_COLORS[$proposta->status] ?? 'gray'; @endphp
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                {{ \App\Models\Proposta::STATUS[$proposta->status] }}
                            </span>
                            @if($proposta->submitted_at)
                                <span class="ml-2 text-gray-400 text-xs">em {{ $proposta->submitted_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Valor Solicitado</dt>
                        <dd class="text-gray-900 font-medium">R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Contrapartida</dt>
                        <dd class="text-gray-900">R$ {{ number_format($proposta->valor_proprio, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Início Previsto</dt>
                        <dd class="text-gray-900">{{ $proposta->data_inicio_prevista?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fim Previsto</dt>
                        <dd class="text-gray-900">{{ $proposta->data_fim_prevista?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    @if($proposta->objeto)
                        <div class="col-span-2">
                            <dt class="text-gray-500">Objeto</dt>
                            <dd class="text-gray-900">{{ $proposta->objeto }}</dd>
                        </div>
                    @endif
                    @if($proposta->justificativa)
                        <div class="col-span-2">
                            <dt class="text-gray-500">Justificativa</dt>
                            <dd class="text-gray-900">{{ $proposta->justificativa }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Plano de Trabalho --}}
            <div class="bg-white shadow rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Plano de Trabalho</h3>
                    <a href="{{ route('propostas.metas.create', $proposta) }}"
                       class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        + Nova Meta
                    </a>
                </div>

                @forelse($proposta->metas as $meta)
                    <div class="border-b border-gray-100 last:border-0">
                        {{-- Cabeçalho da Meta --}}
                        <div class="flex items-start justify-between px-6 py-4 bg-gray-50">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">
                                    Meta {{ $meta->numero }} — {{ $meta->descricao }}
                                </p>
                                <div class="flex gap-4 mt-1 text-xs text-gray-500">
                                    @if($meta->indicador)
                                        <span>Indicador: {{ $meta->indicador }}</span>
                                    @endif
                                    @if($meta->meta_quantitativa)
                                        <span>Meta: {{ $meta->meta_quantitativa }}</span>
                                    @endif
                                    @if($meta->data_inicio && $meta->data_fim)
                                        <span>{{ $meta->data_inicio->format('d/m/Y') }} a {{ $meta->data_fim->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3 ml-4 shrink-0">
                                <a href="{{ route('propostas.metas.etapas.create', [$proposta, $meta]) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">+ Etapa</a>
                                <a href="{{ route('propostas.metas.edit', [$proposta, $meta]) }}"
                                   class="text-xs text-gray-500 hover:text-gray-800">Editar</a>
                                <form action="{{ route('propostas.metas.destroy', [$proposta, $meta]) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Remover esta meta e todas as etapas?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                        </div>

                        {{-- Etapas da Meta --}}
                        @if($meta->etapas->isNotEmpty())
                            <div class="px-6 py-3">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-400 border-b">
                                            <th class="text-left pb-2 font-medium w-8">Nº</th>
                                            <th class="text-left pb-2 font-medium">Descrição</th>
                                            <th class="text-left pb-2 font-medium">Responsável</th>
                                            <th class="text-left pb-2 font-medium">Período</th>
                                            <th class="pb-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($meta->etapas as $etapa)
                                            <tr class="py-2">
                                                <td class="py-2 text-gray-400">{{ $etapa->numero }}</td>
                                                <td class="py-2 text-gray-800">{{ $etapa->descricao }}</td>
                                                <td class="py-2 text-gray-600">{{ $etapa->responsavel ?? '—' }}</td>
                                                <td class="py-2 text-gray-500 text-xs whitespace-nowrap">
                                                    @if($etapa->data_inicio && $etapa->data_fim)
                                                        {{ $etapa->data_inicio->format('d/m/Y') }} a {{ $etapa->data_fim->format('d/m/Y') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                                    <a href="{{ route('propostas.metas.etapas.edit', [$proposta, $meta, $etapa]) }}"
                                                       class="text-xs text-gray-400 hover:text-gray-700">Editar</a>
                                                    <form action="{{ route('propostas.metas.etapas.destroy', [$proposta, $meta, $etapa]) }}"
                                                          method="POST" class="inline"
                                                          onsubmit="return confirm('Remover esta etapa?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remover</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="px-6 py-3 text-xs text-gray-400 italic">Nenhuma etapa cadastrada.</p>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-400">
                        Nenhuma meta cadastrada. Adicione a primeira meta do Plano de Trabalho.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
