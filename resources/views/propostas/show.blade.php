<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $proposta->titulo }}</h2>
            </div>
            <div class="flex items-center gap-3">
                @if($proposta->status === 'rascunho')
                    <form action="{{ route('propostas.submeter', $proposta) }}" method="POST"
                          data-confirm="Confirma a submissão desta proposta?">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="btn btn-primary">
                            Submeter Proposta
                        </button>
                    </form>
                @endif
                @if($proposta->temTramiteCelebracao())
                    <a href="{{ route('celebracao.show', $proposta) }}"
                       class="btn btn-primary">
                        Celebração
                    </a>
                @endif
                @if($proposta->status === 'aprovada' && !$proposta->instrumento)
                    <a href="{{ route('instrumentos.create', $proposta) }}"
                       class="btn btn-primary">
                        Formalizar Instrumento
                    </a>
                @endif
                @if($proposta->instrumento)
                    <a href="{{ route('instrumentos.show', $proposta->instrumento) }}"
                       class="btn btn-outline">
                        Ver Instrumento
                    </a>
                @endif
                <a href="{{ route('propostas.edit', $proposta) }}"
                   class="btn btn-secondary">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @php
                $parecerTecnico  = $proposta->parecer('tecnico');
                $parecerJuridico = $proposta->parecer('juridico');
                $parecerDecisao  = $proposta->parecer('decisao');
                $aprovados       = ['aprovado', 'aprovado_ressalvas'];
            @endphp

            {{-- Seção de Análise --}}
            @if(in_array($proposta->status, ['submetida', 'em_analise', 'em_negociacao', 'aprovada', 'reprovada']))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Análise da Proposta</h3>
                    <div class="flex gap-2">
                        @if(!$parecerTecnico && in_array($proposta->status, ['submetida', 'em_analise']))
                            <a href="{{ route('propostas.pareceres.create', [$proposta, 'tipo' => 'tecnico']) }}"
                               class="btn btn-primary btn-sm">
                                + Parecer Técnico
                            </a>
                        @endif
                        @if($parecerTecnico && in_array($parecerTecnico->resultado, $aprovados) && !$parecerJuridico)
                            <a href="{{ route('propostas.pareceres.create', [$proposta, 'tipo' => 'juridico']) }}"
                               class="px-3 py-1.5 text-sm font-medium text-white bg-brand-600 rounded-md hover:bg-brand-700">
                                + Parecer Jurídico
                            </a>
                        @endif
                        @if($parecerJuridico && in_array($parecerJuridico->resultado, $aprovados) && !$parecerDecisao)
                            <a href="{{ route('propostas.pareceres.create', [$proposta, 'tipo' => 'decisao']) }}"
                               class="btn btn-primary btn-sm">
                                + Decisão Final
                            </a>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($proposta->pareceres as $parecer)
                        @php $color = \App\Models\Parecer::RESULTADO_COLORS[$parecer->resultado] ?? 'gray'; @endphp
                        <div class="px-6 py-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ \App\Models\Parecer::TIPOS[$parecer->tipo] }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $parecer->responsavel ?? 'Sem responsável' }}
                                        &middot; {{ $parecer->data_parecer->format('d/m/Y') }}
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                    {{ \App\Models\Parecer::RESULTADOS[$parecer->resultado] }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $parecer->texto }}</p>
                            @if($parecer->observacoes)
                                <p class="mt-1 text-xs text-gray-500 italic">{{ $parecer->observacoes }}</p>
                            @endif

                            {{-- Diligências vinculadas ao parecer --}}
                            @foreach($parecer->diligencias as $diligencia)
                                @php $dc = \App\Models\Diligencia::STATUS_COLORS[$diligencia->status] ?? 'gray'; @endphp
                                <div class="mt-3 pl-4 border-l-2 border-slate-300">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-medium text-slate-700">
                                            Diligência — prazo: {{ $diligencia->prazo->format('d/m/Y') }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 text-xs bg-{{ $dc }}-100 text-{{ $dc }}-700 rounded">
                                                {{ \App\Models\Diligencia::STATUS[$diligencia->status] }}
                                            </span>
                                            <a href="{{ route('propostas.diligencias.show', [$proposta, $diligencia]) }}"
                                               class="text-xs text-brand-600 hover:underline">
                                                {{ $diligencia->status === 'pendente' ? 'Responder' : 'Ver' }}
                                            </a>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">{{ $diligencia->descricao }}</p>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-gray-400 text-center">
                            Nenhum parecer registrado ainda.
                        </p>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- Dados da Proposta --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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

            {{-- Documentos --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Documentos</h3>
                    <span class="text-xs text-gray-400">Máx. 10 MB — PDF, Word, Excel, JPG, PNG</span>
                </div>

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <form action="{{ route('documentos.store', $proposta) }}" method="POST" enctype="multipart/form-data"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                            <select name="tipo" required
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                                @foreach(\App\Models\Documento::TIPOS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Arquivo</label>
                            <input type="file" name="arquivo" required
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        </div>
                        <button type="submit"
                                class="btn btn-primary btn-sm">
                            Enviar
                        </button>
                    </form>
                    @error('arquivo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                </div>

                @forelse($proposta->documentos as $doc)
                    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-brand-50 rounded flex items-center justify-center text-xs font-bold text-brand-600">
                                {{ strtoupper(pathinfo($doc->nome_original, PATHINFO_EXTENSION)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $doc->nome_original }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ \App\Models\Documento::TIPOS[$doc->tipo] ?? $doc->tipo }}
                                    &middot; {{ $doc->tamanhoFormatado() }}
                                    &middot; {{ $doc->created_at->format('d/m/Y H:i') }}
                                    @if($doc->uploader) &middot; {{ $doc->uploader->name }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('documentos.download', $doc) }}"
                               class="text-xs text-brand-600 hover:text-brand-800">Baixar</a>
                            <form action="{{ route('documentos.destroy', [$proposta, $doc]) }}" method="POST"
                                  data-confirm="Remover este documento?">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-gray-400 text-center">Nenhum documento anexado.</p>
                @endforelse
            </div>

            {{-- Plano de Trabalho --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Plano de Trabalho</h3>
                    <a href="{{ route('propostas.metas.create', $proposta) }}"
                       class="btn btn-primary btn-sm">
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
                                   class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition font-medium">+ Etapa</a>
                                <a href="{{ route('propostas.metas.edit', [$proposta, $meta]) }}"
                                   class="text-xs text-gray-500 hover:text-gray-800">Editar</a>
                                <form action="{{ route('propostas.metas.destroy', [$proposta, $meta]) }}"
                                      method="POST" class="inline"
                                      data-confirm="Remover esta meta e todas as etapas?">
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
                                                          data-confirm="Remover esta etapa?">
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
