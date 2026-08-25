@php
    $user  = auth()->user();
    $setor = $user->setorNoTramite();
    $cor   = \App\Models\ManifestacaoInteresse::STATUS_COLORS[$manifestacao->status] ?? 'gray';

    // Quem age agora: a SCP conduz e decide; a Secretaria da área opina.
    $souScp = $setor === 'scp' && $manifestacao->setor_atual === 'scp';
    $souUg  = $setor === 'ug' && $manifestacao->setor_atual === 'ug'
        && $user->orgao_id === $manifestacao->orgao_id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">
                <a href="{{ route('manifestacoes.index') }}" class="hover:underline">Manifestações de Interesse</a>
                &rsaquo; Análise
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $manifestacao->titulo }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Identificação --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">OSC</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $manifestacao->osc->name }}</dd></div>
                    <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Secretaria</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $manifestacao->orgao->name }}</dd></div>
                    <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Valor solicitado</dt>
                        <dd class="text-gray-800 mt-0.5">R$ {{ number_format($manifestacao->valor_solicitado, 2, ',', '.') }}</dd></div>
                    <div><dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Situação</dt>
                        <dd class="mt-0.5">
                            <span class="inline-block px-2.5 py-1 text-xs font-semibold leading-snug bg-{{ $cor }}-50 text-{{ $cor }}-800 border border-{{ $cor }}-200 rounded-md">
                                {{ $manifestacao->statusLabel() }}
                            </span>
                        </dd></div>
                </dl>
                <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Objeto</p>
                        <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $manifestacao->objeto }}</p></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Justificativa</p>
                        <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $manifestacao->justificativa }}</p></div>
                    @if($manifestacao->publico_alvo)
                        <div><p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Público atendido</p>
                            <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $manifestacao->publico_alvo }}</p></div>
                    @endif
                </div>
            </div>

            {{-- Plano de trabalho e habilitação --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800">Plano de trabalho</h3>
                <div class="mt-3 space-y-3">
                    @forelse($manifestacao->metas as $meta)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-900">Meta {{ $meta->numero }} — {{ $meta->descricao }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ collect([$meta->indicador, $meta->meta_quantitativa])->filter()->implode(' · ') ?: 'Sem indicador informado' }}
                            </p>
                            @if($meta->etapas->isNotEmpty())
                                <ul class="mt-2 space-y-1">
                                    @foreach($meta->etapas as $etapa)
                                        <li class="text-xs text-gray-600 border-l-2 border-gray-200 pl-3">
                                            {{ $etapa->numero }}. {{ $etapa->descricao }}
                                            @if($etapa->responsavel)<span class="text-gray-400"> · {{ $etapa->responsavel }}</span>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Sem metas cadastradas.</p>
                    @endforelse
                </div>

                <h3 class="text-base font-semibold text-gray-800 mt-6 pt-6 border-t border-gray-100">Documentos</h3>
                <ul class="mt-2 divide-y divide-gray-100">
                    @forelse($manifestacao->documentos as $doc)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <span class="text-sm text-gray-700">{{ $doc->nome_original }}
                                <span class="text-xs text-gray-400">· {{ \App\Models\Documento::TIPOS[$doc->tipo] ?? $doc->tipo }}</span>
                            </span>
                            <a href="{{ route('manifestacoes.documentos.download', [$manifestacao, $doc]) }}"
                               class="text-xs font-semibold text-brand-700 hover:underline shrink-0">Baixar</a>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-400">Nenhum documento anexado.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Manifestação técnica da Secretaria --}}
            @if($manifestacao->parecer_em)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-800">Manifestação técnica — {{ $manifestacao->orgao->name }}</h3>
                    <p class="mt-1 text-sm font-semibold {{ $manifestacao->parecer_favoravel ? 'text-brand-700' : 'text-red-700' }}">
                        {{ $manifestacao->parecer_favoravel ? 'Favorável' : 'Desfavorável' }}
                    </p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $manifestacao->parecer_ug }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ $manifestacao->parecerPor?->name }} · {{ $manifestacao->parecer_em->format('d/m/Y H:i') }}
                    </p>
                </div>
            @endif

            {{-- Decisão --}}
            @if($manifestacao->decidida())
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-800">Decisão do SCP</h3>
                    <p class="mt-1 text-sm font-semibold {{ $manifestacao->status === 'deferida' ? 'text-brand-700' : 'text-red-700' }}">
                        @if($manifestacao->status === 'deferida')
                            Deferida — {{ \App\Models\ManifestacaoInteresse::ENCAMINHAMENTOS[$manifestacao->decisao] ?? $manifestacao->decisao }}
                        @else
                            Indeferida
                        @endif
                    </p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $manifestacao->decisao_motivo }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ $manifestacao->decididaPor?->name }} · {{ $manifestacao->decidida_em?->format('d/m/Y H:i') }}
                    </p>
                    @if($manifestacao->proposta)
                        <a href="{{ route('propostas.show', $manifestacao->proposta) }}"
                           class="inline-block mt-3 text-sm font-semibold text-brand-700 hover:underline">
                            Abrir a proposta gerada →
                        </a>
                    @endif
                </div>

            {{-- Ações de quem está com a vez --}}
            @elseif($souScp || $souUg)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
                    @if($souScp && $manifestacao->status === 'submetida')
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Ouvir a Secretaria</h3>
                            <p class="text-xs text-gray-500 mt-0.5 mb-3">
                                A {{ $manifestacao->orgao->name }} dirá se há interesse público e orçamento — é o que
                                fundamenta o encaminhamento.
                            </p>
                            <form action="{{ route('manifestacoes.encaminhar', $manifestacao) }}" method="POST">
                                @csrf
                                <button class="btn btn-primary">Encaminhar à {{ $manifestacao->orgao->sigla ?: 'Secretaria' }}</button>
                            </form>
                        </div>
                    @endif

                    @if($souUg)
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Manifestação técnica</h3>
                            <form action="{{ route('manifestacoes.parecer', $manifestacao) }}" method="POST" class="mt-3 space-y-3">
                                @csrf
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="radio" name="parecer_favoravel" value="1" required class="text-brand-600 focus:ring-brand-500">
                                        Favorável
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="radio" name="parecer_favoravel" value="0" class="text-red-600 focus:ring-red-500">
                                        Desfavorável
                                    </label>
                                </div>
                                <textarea name="parecer_ug" rows="4" required
                                          placeholder="Interesse público, aderência às políticas da Secretaria e disponibilidade orçamentária"
                                          class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                                <x-input-error :messages="$errors->get('parecer_ug')" class="mt-1" />
                                <button class="btn btn-primary">Registrar e devolver à SCP</button>
                            </form>
                        </div>
                    @endif

                    @if($souScp && $manifestacao->status === 'analisada')
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Decidir o encaminhamento</h3>
                            <p class="text-xs text-gray-500 mt-0.5 mb-3">
                                O deferimento cria o chamamento do tipo escolhido e a proposta, levando o plano de
                                trabalho e os documentos que a OSC já entregou.
                            </p>
                            <form action="{{ route('manifestacoes.deferir', $manifestacao) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="decisao" value="Enquadramento *" />
                                        <select name="decisao" id="decisao" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                            @foreach(\App\Models\ManifestacaoInteresse::ENCAMINHAMENTOS as $k => $rotulo)
                                                <option value="{{ $k }}">{{ $rotulo }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="programa_id" value="Programa *" />
                                        <select name="programa_id" id="programa_id" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                            <option value="">Selecione…</option>
                                            @foreach($programas as $programa)
                                                <option value="{{ $programa->id }}">{{ $programa->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('programa_id')" class="mt-1" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="numero" value="Número do chamamento (opcional)" />
                                    <x-text-input id="numero" name="numero" type="text" class="mt-1 block w-full sm:w-64" />
                                </div>
                                <div>
                                    <x-input-label for="fundamento" value="Fundamento do enquadramento *" />
                                    <textarea name="fundamento" id="fundamento" rows="3" required
                                              placeholder="Art. 30 (dispensa) ou art. 31 (inexigibilidade) da Lei 13.019/2014 e as razões de fato"
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('fundamento') }}</textarea>
                                    <x-input-error :messages="$errors->get('fundamento')" class="mt-1" />
                                </div>
                                <button class="btn btn-primary">Deferir e criar o chamamento</button>
                            </form>
                        </div>
                    @endif

                    @if($souScp)
                        <div class="pt-4 border-t border-gray-100">
                            <form action="{{ route('manifestacoes.indeferir', $manifestacao) }}" method="POST" class="space-y-2"
                                  data-confirm="Indeferir esta manifestação?">
                                @csrf
                                <textarea name="decisao_motivo" rows="2" required placeholder="Motivo do indeferimento (a OSC lerá no portal)"
                                          class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500"></textarea>
                                <button class="btn btn-danger-outline">Indeferir</button>
                            </form>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-xs text-gray-500">
                    A manifestação está com <strong>{{ $manifestacao->setor_atual === 'ug' ? $manifestacao->orgao->name : 'o Setor de Convênios e Parcerias' }}</strong>.
                </p>
            @endif
        </div>
    </div>

    <x-atalhos-rolagem />
</x-app-layout>
