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
                {{-- Submeter é ato da OSC, no portal, e só do responsável legal:
                     é o que vincula a entidade ao que foi proposto. Rascunho
                     aqui significa que ela ainda não apresentou. --}}
                @if($proposta->status === 'rascunho')
                    <span class="text-sm text-gray-500">Rascunho da OSC — ainda não apresentada</span>
                @endif
                @if($proposta->temTramiteCelebracao())
                    <a href="{{ route('celebracao.show', $proposta) }}"
                       class="btn btn-primary">
                        Celebração
                    </a>
                @endif
                @if($proposta->instrumento)
                    <a href="{{ route('instrumentos.show', $proposta->instrumento) }}"
                       class="btn btn-outline">
                        Ver Instrumento
                    </a>
                @endif
                {{-- Curadoria do dossiê (3.3): o que a organização enxerga do
                     processo. A régua era uma lista cravada no código. --}}
                <a href="{{ route('dossie.curadoria', $proposta) }}" class="btn btn-outline">
                    Documentos visíveis à OSC
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Recurso contra o resultado provisório, para a Comissão de Seleção da Secretaria ler.
                 A resposta é peça da etapa 3 da Seleção. --}}
            @foreach($proposta->recursos as $rec)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Recurso contra o resultado provisório</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Protocolado pela OSC em {{ $rec->protocolado_em?->format('d/m/Y H:i') }}
                                @if($rec->temArquivo())
                                    · <a href="{{ route('recursos.download', $rec) }}" class="text-brand-600 hover:underline">{{ $rec->arquivo_nome }} ({{ $rec->tamanhoFormatado() }})</a>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($rec->temArquivo())
                        <a href="{{ route('recursos.download', $rec) }}" class="mt-3 inline-flex btn btn-outline btn-sm">
                            Baixar o recurso da OSC (PDF)
                        </a>
                    @endif

                    {{-- A resposta é a peça "Resposta ao recurso" (opcional) da etapa 3 da Seleção. --}}
                    <p class="mt-3 text-xs text-gray-500">
                        A resposta, se houver, é o documento "Resposta ao recurso" da etapa 3 da Seleção, emitido pela Comissão de Seleção.
                        @if(auth()->user()->can('chamamentos') || $rec->comissaoPodeVer(auth()->user()))
                            <a href="{{ route('chamamentos.selecao', $rec->chamamento_id) }}" class="text-brand-600 hover:underline">Abrir a Seleção</a>
                        @endif
                    </p>
                </div>
            @endforeach

            {{-- Dispensa/inexigibilidade não passa pela Seleção: a UG decide aqui. --}}
            @if($proposta->aguardaDecisaoDaUg())
                @php
                    $docsSemConferencia = $proposta->documentos->filter->pendenteDeAnalise()->count();
                    $docsRecusados      = $proposta->documentos->filter->recusado()->count();
                @endphp
                <div class="bg-white rounded-xl border border-accent-200 shadow-sm p-6" x-data="{ reprovar: {{ $errors->has('motivo') ? 'true' : 'false' }} }">
                    <h3 class="text-base font-semibold text-gray-800">Decisão da proposta</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Proposta não passa pela Seleção:
                        a Unidade Gestora aprova, e a Celebração começa, ou reprova, dizendo à OSC o motivo.
                    </p>

                    @if($proposta->podeSerDecididaPor(auth()->user()))
                        @if($docsSemConferencia || $docsRecusados)
                            <p class="mt-3 text-sm text-accent-800 bg-accent-50 border border-accent-200 rounded-lg px-3 py-2">
                                @if($docsSemConferencia){{ $docsSemConferencia }} documento(s) ainda sem conferência. @endif
                                @if($docsRecusados){{ $docsRecusados }} documento(s) recusado(s). @endif
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-start gap-3">
                            <form action="{{ route('propostas.decidir', $proposta) }}" method="POST"
                                  data-confirm="Aprovar a proposta e iniciar a Celebração?">
                                @csrf
                                <input type="hidden" name="decisao" value="aprovar">
                                <button type="submit" class="btn btn-primary">Aprovar e iniciar a Celebração</button>
                            </form>
                            <button type="button" x-show="! reprovar" @click="reprovar = true" class="btn btn-outline">Reprovar</button>
                        </div>

                        <form x-show="reprovar" x-cloak action="{{ route('propostas.decidir', $proposta) }}" method="POST" class="mt-4 space-y-2"
                              data-confirm="Reprovar a proposta? A OSC recebe o motivo.">
                            @csrf
                            <input type="hidden" name="decisao" value="reprovar">
                            <x-input-label for="motivo" value="Motivo da reprovação (vai para a OSC) *" />
                            <textarea id="motivo" name="motivo" rows="3"
                                      class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('motivo') }}</textarea>
                            <x-input-error :messages="$errors->get('motivo')" />
                            <div class="flex items-center gap-3">
                                <button type="submit" class="btn btn-danger">Reprovar a proposta</button>
                                <button type="button" @click="reprovar = false" class="text-sm text-gray-500 hover:text-gray-800">Cancelar</button>
                            </div>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-gray-600">Aguardando a decisão do Responsável da Unidade Gestora da Secretaria.</p>
                    @endif
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
                        <dt class="text-gray-500">Secretaria</dt>
                        <dd class="text-gray-900">{{ $proposta->chamamento->programa?->orgao?->name ?? '—' }}</dd>
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
                            @if($proposta->decidida_em)
                                <span class="ml-2 text-gray-400 text-xs">
                                    por {{ $proposta->decididaPor?->name ?? '—' }} em {{ $proposta->decidida_em->format('d/m/Y H:i') }}
                                </span>
                            @elseif($proposta->submitted_at)
                                <span class="ml-2 text-gray-400 text-xs">em {{ $proposta->submitted_at->format('d/m/Y H:i') }}</span>
                            @endif
                            @if($proposta->decisao_motivo)
                                <p class="mt-1 text-xs text-red-800">Motivo: {{ $proposta->decisao_motivo }}</p>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Valor pleiteado</dt>
                        <dd class="text-gray-900 font-medium">R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</dd>
                    </div>
                    {{-- Contrapartida em dinheiro saiu do plano (não consta do modelo
                         da cliente); aparece só em proposta antiga que a declarou. --}}
                    @if((float) $proposta->valor_proprio > 0)
                        <div>
                            <dt class="text-gray-500">Contrapartida</dt>
                            <dd class="text-gray-900">R$ {{ number_format($proposta->valor_proprio, 2, ',', '.') }}</dd>
                        </div>
                    @endif
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

                {{-- Sem formulário de envio: os documentos são da OSC e só ela
                     os apresenta. Ao município cabe conferir — baixar, aprovar
                     ou recusar. --}}
                <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-500">
                        Enviados pela OSC. Confira cada documento: aprovar o mantém na instrução do
                        processo; recusar devolve à OSC com o motivo, para reenvio.
                    </p>
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
                                @if($doc->analisado_em)
                                    <p class="text-xs {{ $doc->recusado() ? 'text-red-600' : 'text-gray-400' }} mt-0.5">
                                        {{ $doc->aprovado() ? 'Aprovado' : 'Recusado' }} por
                                        {{ $doc->analista?->name ?? '—' }} em {{ $doc->analisado_em->format('d/m/Y H:i') }}
                                        @if($doc->recusado() && $doc->analise_motivo)
                                            — {{ $doc->analise_motivo }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @php $corAnalise = \App\Models\Documento::ANALISE_COLORS[$doc->analise_status] ?? 'gray'; @endphp
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $corAnalise }}-100 text-{{ $corAnalise }}-800 rounded-full whitespace-nowrap">
                                {{ \App\Models\Documento::ANALISE[$doc->analise_status] ?? $doc->analise_status }}
                            </span>
                            <a href="{{ route('documentos.download', $doc) }}"
                               class="text-xs text-brand-600 hover:text-brand-800">Baixar</a>

                            @if($doc->pendenteDeAnalise())
                                <form action="{{ route('documentos.analisar', [$proposta, $doc]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decisao" value="aprovado">
                                    <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-800">Aprovar</button>
                                </form>
                                {{-- Recusa exige motivo: é o que diz à OSC o que corrigir. --}}
                                <details class="relative">
                                    <summary class="text-xs text-red-500 hover:text-red-700 cursor-pointer select-none marker:content-none">Recusar</summary>
                                    <form action="{{ route('documentos.analisar', [$proposta, $doc]) }}" method="POST"
                                          class="absolute right-0 z-10 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-2">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="decisao" value="recusado">
                                        <textarea name="motivo" rows="3" required
                                                  placeholder="O que a OSC precisa corrigir?"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm text-xs focus:ring-brand-500 focus:border-brand-500"></textarea>
                                        <button type="submit" class="btn btn-danger btn-sm w-full">Confirmar recusa</button>
                                    </form>
                                </details>
                            @else
                                <form action="{{ route('documentos.analisar', [$proposta, $doc]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="decisao" value="{{ $doc->aprovado() ? 'recusado' : 'aprovado' }}">
                                    <input type="hidden" name="motivo" value="{{ $doc->aprovado() ? 'Revisão da análise anterior.' : '' }}">
                                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700">
                                        {{ $doc->aprovado() ? 'Reverter' : 'Aprovar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-gray-400 text-center">Nenhum documento anexado.</p>
                @endforelse
            </div>

            {{-- Arquivos da OSC: anexados uma vez pela organização;
                 a análise é desta parceria, sobre a versão que se viu. --}}
            <div id="arquivos-osc" style="scroll-margin-top:7rem">
                <div class="mb-3">
                    <h3 class="text-base font-semibold text-gray-800">Arquivos da OSC</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Certidões, documentos institucionais e declarações que a organização anexou uma vez. Aprove ou recuse
                        cada um nesta parceria; versão nova pede análise nova.
                    </p>
                </div>
                @include('arquivos-osc._lista', [
                    'osc'          => $proposta->osc->load('arquivos.remetente'),
                    'podeEditar'   => false,
                    'proposta'     => $proposta,
                    'podeAnalisar' => auth()->user()->can('propostas'),
                ])
            </div>

            @php
                // Na Celebração, a UG edita o plano até o documento dele ser
                // assinado.
                $ugEditaPlano = \App\Http\Controllers\PlanoTrabalhoController::ugPodeEditar($proposta, auth()->user());
            @endphp

            @if($ugEditaPlano)
                <div class="bg-accent-50 border border-accent-200 rounded-xl px-6 py-3 text-sm text-accent-800">
                    Celebração em curso: o plano de trabalho pode ser editado pela Unidade Gestora e pela OSC até o
                    documento "Plano de Trabalho" da Celebração ser assinado.
                </div>
                @include('plano._editor', [
                    'dono'       => $proposta,
                    'rota'       => 'propostas.plano',
                    'podeEditar' => true,
                ])
            @else
            {{-- Plano de Trabalho --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                {{-- Só leitura fora da Celebração: metas e etapas são o que a OSC
                     se comprometeu a fazer. Na Celebração, até o plano ser
                     assinado, a UG usa o editor completo (acima). --}}
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Plano de Trabalho</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Metas e atividades como a organização as propôs. Na Celebração, o plano fica editável
                        até o documento dele ser assinado.
                    </p>
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
                        A organização ainda não cadastrou metas neste plano de trabalho.
                    </div>
                @endforelse
            </div>

            {{-- O resto do Plano de Trabalho: endereços de execução, plano de
                 aplicação dos recursos, quadro de fontes e desembolso. É o que
                 o Parecer Financeiro e o Jurídico dizem ter analisado. --}}
            @include('plano._editor', [
                'dono'         => $proposta,
                'rota'         => 'portal.proposta.plano',
                'podeEditar'   => false,
                'mostrarMetas' => false,
            ])
            @endif

        </div>
    </div>
</x-app-layout>
