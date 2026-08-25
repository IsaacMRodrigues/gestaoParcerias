@php
    $rascunho = $manifestacao->ehRascunho();
    $cor = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$manifestacao->status] ?? 'gray';
    $pendencias = $rascunho ? $manifestacao->pendenciasParaSubmeter() : [];

    /* Montar a manifestação e cuidar da habilitação são funções distintas na
       equipe da OSC (ver User::FUNCOES_OSC): o rascunho abre a porta, a função
       marcada pelo responsável legal diz quem entra por ela. */
    $podeEditar = $rascunho && auth()->user()->can('osc_manifestacoes');
    $podeAnexar = $rascunho && auth()->user()->can('osc_documentos');
@endphp

<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div>
            <p class="text-sm text-brand-600">
                <a href="{{ route('portal.manifestacoes.index') }}" class="hover:underline">← Manifestações de Interesse</a>
            </p>
            <div class="flex items-start justify-between gap-4 mt-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $manifestacao->titulo }}</h1>
                <span class="shrink-0 px-2.5 py-1 text-xs font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded-md">
                    {{ $manifestacao->statusLabel() }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ $manifestacao->orgao->name }}</p>
        </div>

        <x-flash-message />

        {{-- Rascunho aberto e a pessoa sem a função: melhor dizer aqui do que
             deixá-la procurar um botão de editar que não vai aparecer. --}}
        @if($rascunho && ! ($podeEditar && $podeAnexar))
            <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-4">
                Esta manifestação ainda é rascunho.
                @unless($podeEditar) Sua conta não tem a função <strong>Manifestações de interesse</strong>. @endunless
                @unless($podeAnexar) Sua conta não tem a função <strong>Documentos da organização</strong>. @endunless
                Peça ao responsável legal da OSC em <em>Usuários da Organização</em>.
            </p>
        @endif

        {{-- Decisão do município: o que a OSC precisa saber primeiro --}}
        @if($manifestacao->decidida())
            <div class="rounded-xl border p-5 {{ $manifestacao->status === 'deferida' ? 'bg-brand-50 border-brand-200' : 'bg-red-50 border-red-200' }}">
                <p class="text-sm font-bold {{ $manifestacao->status === 'deferida' ? 'text-brand-800' : 'text-red-800' }}">
                    @if($manifestacao->status === 'deferida')
                        Deferida — {{ \App\Models\ManifestacaoInteresse::ENCAMINHAMENTOS[$manifestacao->decisao] ?? $manifestacao->decisao }}
                    @else
                        Indeferida
                    @endif
                </p>
                <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $manifestacao->decisao_motivo }}</p>
                @if($manifestacao->proposta)
                    <a href="{{ route('portal.proposta.show', $manifestacao->proposta) }}"
                       class="inline-block mt-3 text-sm font-semibold text-brand-700 hover:underline">
                        Acompanhar a proposta gerada →
                    </a>
                @endif
            </div>
        @endif

        {{-- 1. Dados gerais --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Dados da proposta</h2>
            @if($podeEditar)
                <form action="{{ route('portal.manifestacoes.update', $manifestacao) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    @include('portal.manifestacoes._campos')
                    <button type="submit" class="btn btn-secondary btn-sm">Salvar dados</button>
                </form>
            @else
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-gray-500">Objeto</dt>
                        <dd class="text-gray-800 mt-0.5 whitespace-pre-line">{{ $manifestacao->objeto }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-500">Justificativa</dt>
                        <dd class="text-gray-800 mt-0.5 whitespace-pre-line">{{ $manifestacao->justificativa }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-500">Valor solicitado</dt>
                        <dd class="text-gray-800 mt-0.5">R$ {{ number_format($manifestacao->valor_solicitado, 2, ',', '.') }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-500">Vigência prevista</dt>
                        <dd class="text-gray-800 mt-0.5">
                            {{ $manifestacao->data_inicio_prevista?->format('d/m/Y') ?? '—' }}
                            a {{ $manifestacao->data_fim_prevista?->format('d/m/Y') ?? '—' }}
                        </dd></div>
                </dl>
            @endif
        </div>

        {{-- 2. Plano de trabalho --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">Plano de trabalho</h2>
            <p class="text-xs text-gray-400 mt-0.5 mb-4">Metas e, dentro delas, as etapas de execução.</p>

            <div class="space-y-4">
                @forelse($manifestacao->metas as $meta)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">Meta {{ $meta->numero }} — {{ $meta->descricao }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ collect([$meta->indicador, $meta->meta_quantitativa])->filter()->implode(' · ') ?: 'Sem indicador informado' }}
                                </p>
                            </div>
                            @if($podeEditar)
                                <form action="{{ route('portal.manifestacoes.metas.destroy', [$manifestacao, $meta]) }}" method="POST"
                                      data-confirm="Remover a meta {{ $meta->numero }} e suas etapas?">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-gray-400 hover:text-red-700 transition shrink-0">Remover</button>
                                </form>
                            @endif
                        </div>

                        <ul class="mt-3 space-y-1">
                            @foreach($meta->etapas as $etapa)
                                <li class="text-xs text-gray-600 flex items-start justify-between gap-2 border-l-2 border-gray-200 pl-3">
                                    <span>{{ $etapa->numero }}. {{ $etapa->descricao }}
                                        @if($etapa->responsavel)<span class="text-gray-400"> · {{ $etapa->responsavel }}</span>@endif
                                    </span>
                                    @if($podeEditar)
                                        <form action="{{ route('portal.manifestacoes.etapas.destroy', [$manifestacao, $meta, $etapa]) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="text-gray-400 hover:text-red-700 transition">×</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if($podeEditar)
                            <form action="{{ route('portal.manifestacoes.etapas.store', [$manifestacao, $meta]) }}" method="POST"
                                  class="mt-3 flex flex-wrap gap-2">
                                @csrf
                                <input type="text" name="descricao" required maxlength="255" placeholder="Nova etapa desta meta"
                                       class="flex-1 min-w-[14rem] border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                <input type="text" name="responsavel" maxlength="255" placeholder="Responsável"
                                       class="w-40 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                <button class="btn btn-secondary btn-sm">Adicionar etapa</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Nenhuma meta cadastrada.</p>
                @endforelse
            </div>

            @if($podeEditar)
                <form action="{{ route('portal.manifestacoes.metas.store', $manifestacao) }}" method="POST"
                      class="mt-4 pt-4 border-t border-gray-100 grid sm:grid-cols-2 gap-3">
                    @csrf
                    <div class="sm:col-span-2">
                        <x-input-label for="descricao" value="Nova meta *" />
                        <x-text-input id="descricao" name="descricao" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('descricao')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="indicador" value="Indicador de verificação" />
                        <x-text-input id="indicador" name="indicador" type="text" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="meta_quantitativa" value="Meta quantitativa" />
                        <x-text-input id="meta_quantitativa" name="meta_quantitativa" type="text" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <button class="btn btn-secondary btn-sm">Adicionar meta</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- 3. Documentos --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">Documentos de habilitação</h2>
            <p class="text-xs text-gray-400 mt-0.5 mb-4">Estatuto, ata de eleição, CNPJ e certidões de regularidade.</p>

            <ul class="divide-y divide-gray-100">
                @forelse($manifestacao->documentos as $doc)
                    <li class="py-2 flex items-center justify-between gap-3">
                        <span class="min-w-0">
                            <a href="{{ route('portal.manifestacoes.documentos.download', [$manifestacao, $doc]) }}"
                               class="text-sm text-brand-700 hover:underline">{{ $doc->nome_original }}</a>
                            <span class="block text-xs text-gray-400">{{ \App\Models\Documento::TIPOS[$doc->tipo] ?? $doc->tipo }}</span>
                        </span>
                        @if($podeAnexar)
                            <form action="{{ route('portal.manifestacoes.documentos.destroy', [$manifestacao, $doc]) }}" method="POST"
                                  data-confirm="Remover este documento?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-400">Nenhum documento anexado.</li>
                @endforelse
            </ul>

            @if($podeAnexar)
                <form action="{{ route('portal.manifestacoes.documentos.store', $manifestacao) }}" method="POST"
                      enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="tipo" value="Tipo" />
                        <select name="tipo" id="tipo" required
                                class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            @foreach(\App\Models\Documento::TIPOS as $k => $rotulo)
                                <option value="{{ $k }}">{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[14rem]">
                        <x-input-label for="arquivo" value="Arquivo (PDF, Word, Excel, JPG, PNG — até 10 MB)" />
                        <input type="file" name="arquivo" id="arquivo" required class="mt-1 block w-full text-sm text-gray-600">
                        <x-input-error :messages="$errors->get('arquivo')" class="mt-1" />
                    </div>
                    <button class="btn btn-secondary btn-sm">Anexar</button>
                </form>
            @endif
        </div>

        {{-- 4. Submissão --}}
        @if($rascunho)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                @if($pendencias)
                    <p class="text-sm font-semibold text-accent-800">Falta para submeter:</p>
                    <ul class="mt-1 text-sm text-accent-700 list-disc list-inside">
                        @foreach($pendencias as $p)<li>{{ $p }}</li>@endforeach
                    </ul>
                @endif

                @if(auth()->user()->ehResponsavelLegalOsc())
                    <form action="{{ route('portal.manifestacoes.submeter', $manifestacao) }}" method="POST" class="mt-4"
                          data-confirm="Submeter a manifestação? Depois disso ela não poderá mais ser editada.">
                        @csrf @method('PATCH')
                        <button type="submit" @disabled($pendencias) class="btn btn-primary">
                            Submeter ao município
                        </button>
                    </form>
                @else
                    {{-- Mesma régua da proposta: a equipe monta, o responsável legal apresenta. --}}
                    <p class="mt-4 text-sm text-gray-500">
                        A equipe monta a manifestação; <strong>submeter é ato do responsável legal</strong> da OSC.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <x-atalhos-rolagem />
</x-portal-layout>
