<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <p class="text-sm text-brand-600 mb-2">
            <a href="{{ route('portal.index') }}" class="hover:underline">← Chamamentos</a>
        </p>

        @if(session('info'))
            <div class="mb-4 bg-accent-50 border border-accent-200 text-accent-800 px-4 py-3 rounded-lg text-sm">
                {{ session('info') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">

            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <x-selo-modalidade :tipo="$chamamento->tipo" />
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">
                        @if($chamamento->numero) {{ $chamamento->numero }} — @endif
                        {{ $chamamento->titulo }}
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">
                        {{ $chamamento->programa->orgao->name }}
                        &middot; Programa: {{ $chamamento->programa->name }}
                    </p>
                </div>

                <div class="shrink-0">
                    @if($chamamento->aceitaPropostas())
                        @auth
                            @if(auth()->user()->ehRepresentanteOsc() && auth()->user()->can('osc_propostas'))
                                <a href="{{ route('portal.participar', $chamamento) }}"
                                   class="btn btn-primary">
                                    Submeter Proposta
                                </a>
                            @elseif(auth()->user()->ehRepresentanteOsc())
                                {{-- Integrante sem a função de propostas: o botão levaria
                                     a um bloqueio, e o motivo não está no chamamento. --}}
                                <span class="inline-block px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 border border-gray-200 rounded-lg">
                                    Sua conta não monta propostas — fale com o responsável legal
                                </span>
                            @elseif(auth()->user()->temAcessoInterno())
                                <span class="inline-block px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 border border-gray-200 rounded-lg">
                                    Usuário do sistema — a submissão é feita por OSCs
                                </span>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="btn btn-primary">
                                Entrar para Participar
                            </a>
                        @endauth
                    @elseif($chamamento->ehDispensa())
                        <span class="inline-block px-5 py-2.5 text-sm font-semibold text-brand-700 bg-brand-50 border border-brand-200 rounded-lg">
                            Publicado
                        </span>
                    @elseif($chamamento->status === 'publicado')
                        <span class="inline-block px-5 py-2.5 text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg">
                            Inscrições em breve
                        </span>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm border-t border-gray-100 pt-6">
                @if($chamamento->valor_disponivel)
                    <div>
                        <dt class="text-gray-500">Valor Disponível</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">
                            R$ {{ number_format($chamamento->valor_disponivel, 2, ',', '.') }}
                        </dd>
                    </div>
                @endif
                @if($chamamento->data_inicio_inscricao)
                    <div>
                        <dt class="text-gray-500">Período de Inscrição</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            {{ $chamamento->data_inicio_inscricao->format('d/m/Y') }}
                            a {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif
                @if($chamamento->data_inicio_vigencia)
                    <div>
                        <dt class="text-gray-500">Vigência Prevista</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            {{ $chamamento->data_inicio_vigencia->format('d/m/Y') }}
                            @if($chamamento->data_fim_vigencia) a {{ $chamamento->data_fim_vigencia->format('d/m/Y') }} @endif
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd class="mt-0.5">
                        @php $color = \App\Models\Chamamento::STATUS_COLORS[$chamamento->status] ?? 'gray'; @endphp
                        <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                            {{ \App\Models\Chamamento::STATUS[$chamamento->status] }}
                        </span>
                    </dd>
                </div>
            </dl>

            @if($chamamento->objeto)
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Objeto</h2>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $chamamento->objeto }}</p>
                </div>
            @endif

            @if($chamamento->descricao ?? null)
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Informações Gerais</h2>
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $chamamento->descricao }}</div>
                </div>
            @endif

            @if($chamamento->requisitos ?? null)
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Requisitos</h2>
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $chamamento->requisitos }}</div>
                </div>
            @endif

            @if($documentosPublicos->isNotEmpty())
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Documentos do Chamamento</h2>
                    <ul class="space-y-2">
                        @foreach($documentosPublicos as $doc)
                            <li>
                                <a href="{{ route('validacao.mostrar', $doc->codigo_validacao) }}" target="_blank"
                                   class="inline-flex items-center gap-2 text-sm text-brand-600 hover:underline font-medium">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ \App\Models\ProcessoPeca::TIPOS[$doc->tipo] ?? $doc->tipo }} (ler documento)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs text-gray-400 mt-2">Documentos assinados eletronicamente — abrem na página oficial de validação.</p>
                </div>
            @endif

            {{-- Fase recursal: a OSC participante protocola o seu recurso --}}
            @auth
                @if($meuRecurso || ($participei && $chamamento->faseRecursalAberta()))
                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <h2 class="text-sm font-semibold text-gray-700 mb-3">Recurso contra o resultado provisório</h2>

                        @if($meuRecurso)
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <p class="text-sm text-gray-700">
                                    Recurso protocolado em
                                    <strong>{{ $meuRecurso->protocolado_em?->format('d/m/Y H:i') }}</strong>.
                                    @if($meuRecurso->temArquivo())
                                        <a href="{{ route('recursos.download', $meuRecurso) }}" class="text-brand-600 hover:underline">
                                            Baixar a peça enviada
                                        </a>
                                    @endif
                                </p>

                                @if($meuRecurso->respondido())
                                    @php $cor = \App\Models\Recurso::RESULTADO_COLORS[$meuRecurso->resultado] ?? 'gray'; @endphp
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <span class="px-2 py-1 text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 rounded-full">
                                            {{ $meuRecurso->resultadoLabel() }}
                                        </span>
                                        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ $meuRecurso->resposta }}</p>
                                        <p class="text-xs text-gray-400 mt-2">
                                            Resposta em {{ $meuRecurso->respondido_em->format('d/m/Y H:i') }}
                                            @if($meuRecurso->codigo_validacao)
                                                · código <strong class="font-mono">{{ $meuRecurso->codigo_validacao }}</strong>
                                            @endif
                                        </p>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500 mt-2">
                                        Aguardando a análise da Unidade Gestora. A resposta aparecerá aqui.
                                    </p>
                                @endif
                            </div>
                        @elseif(! auth()->user()->ehResponsavelLegalOsc())
                            {{-- Membro da OSC vê que há prazo aberto, mas o
                                 protocolo é do responsável legal. --}}
                            <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-4">
                                A fase recursal está aberta. O recurso precisa ser protocolado pelo
                                <strong>responsável legal</strong> da organização.
                            </p>
                        @else
                            <form action="{{ route('recursos.store', $chamamento) }}" method="POST"
                                  enctype="multipart/form-data" class="space-y-3"
                                  data-confirm="Protocolar o recurso? Após o envio não é possível alterá-lo.">
                                @csrf
                                <div>
                                    <label for="fundamentacao" class="block text-xs font-medium text-gray-500 mb-1">
                                        Fundamentação do recurso
                                    </label>
                                    <textarea name="fundamentacao" id="fundamentacao" rows="4" required
                                              placeholder="Descreva as razões do recurso"
                                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('fundamentacao') }}</textarea>
                                    <x-input-error :messages="$errors->get('fundamentacao')" class="mt-1" />
                                </div>
                                <div>
                                    <label for="arquivo" class="block text-xs font-medium text-gray-500 mb-1">
                                        Peça recursal assinada (arquivo único em PDF)
                                    </label>
                                    <input type="file" name="arquivo" id="arquivo" accept=".pdf" required
                                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                    <x-input-error :messages="$errors->get('arquivo')" class="mt-1" />
                                </div>
                                <button type="submit"
                                        class="btn btn-primary">
                                    Protocolar recurso
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth

            @if(in_array($chamamento->status, ['publicado', 'em_inscricao']))
                <div class="mt-8 bg-brand-50 border border-brand-200 rounded-lg p-5 text-center">
                    @if($chamamento->status_efetivo === 'em_inscricao')
                        <p class="text-sm text-brand-800 font-medium mb-3">
                            Sua OSC pode submeter uma proposta para este chamamento.
                        </p>
                        @auth
                            @if(auth()->user()->ehRepresentanteOsc() && auth()->user()->can('osc_propostas'))
                                <a href="{{ route('portal.participar', $chamamento) }}"
                                   class="btn btn-primary">
                                    Submeter Proposta
                                </a>
                            @elseif(auth()->user()->ehRepresentanteOsc())
                                <p class="text-xs text-brand-600">
                                    Sua conta não tem a função de montar propostas. Fale com o responsável
                                    legal da organização.
                                </p>
                            @elseif(auth()->user()->temAcessoInterno())
                                <p class="text-xs text-brand-600">
                                    Você está conectado como usuário do sistema. A submissão de propostas é exclusiva das OSCs.
                                </p>
                            @endif
                        @else
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('login') }}"
                                   class="btn btn-primary">
                                    Entrar
                                </a>
                                <a href="{{ route('portal.osc.create') }}"
                                   class="btn btn-outline">
                                    Cadastrar OSC
                                </a>
                            </div>
                        @endauth
                    @else
                        <p class="text-sm text-slate-800 font-medium">
                            Este chamamento foi publicado. As inscrições abrirão em breve.
                            @if($chamamento->data_inicio_inscricao)
                                Previsão: {{ $chamamento->data_inicio_inscricao->format('d/m/Y') }}.
                            @endif
                        </p>
                        @guest
                            <p class="text-xs text-slate-600 mt-1">
                                <a href="{{ route('portal.osc.create') }}" class="underline">Cadastre sua OSC</a>
                                para estar pronto quando as inscrições abrirem.
                            </p>
                        @endguest
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Edital, cronograma, documentos e fase recursal: rolagem longa. --}}
    <x-atalhos-rolagem />
</x-portal-layout>
