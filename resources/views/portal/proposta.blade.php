<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-brand-600 mb-1">
                    <a href="{{ route('portal.minhas-propostas') }}" class="hover:underline">← Minhas Propostas</a>
                </p>
                <h1 class="text-2xl font-bold text-gray-900">{{ $proposta->titulo }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $proposta->chamamento->programa->name }} — {{ $proposta->chamamento->titulo }}
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                @php $color = \App\Models\Proposta::STATUS_COLORS[$proposta->status] ?? 'gray'; @endphp
                <span class="px-3 py-1.5 text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                    {{ \App\Models\Proposta::STATUS[$proposta->status] }}
                </span>
                @if($proposta->status === 'rascunho')
                    {{-- A equipe monta a proposta; apresentá-la é ato do
                         responsável legal. Em vez de esconder o botão e deixar
                         o membro sem saber o que falta, a tela diz de quem é a
                         vez — mesmo princípio do checklist dos trâmites. --}}
                    @if(auth()->user()->ehResponsavelLegalOsc())
                        <form action="{{ route('portal.proposta.submeter', $proposta) }}" method="POST"
                              data-confirm="Confirma a submissão? Após isso não será possível editar.">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="btn btn-primary">
                                Submeter Proposta
                            </button>
                        </form>
                    @else
                        <span class="inline-block px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-lg">
                            Pronta para submissão —
                            {{ $proposta->osc->resp_nome ?: 'o responsável legal' }} precisa submeter
                        </span>
                    @endif
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="bg-slate-50 border border-slate-200 text-slate-800 px-4 py-3 rounded-lg text-sm">
                {{ session('info') }}
            </div>
        @endif

        {{-- Dados da proposta --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Dados da Proposta</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Valor Solicitado</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Contrapartida</dt>
                    <dd class="text-gray-900">R$ {{ number_format($proposta->valor_proprio, 2, ',', '.') }}</dd>
                </div>
                @if($proposta->data_inicio_prevista)
                    <div>
                        <dt class="text-gray-500">Início Previsto</dt>
                        <dd class="text-gray-900">{{ $proposta->data_inicio_prevista->format('d/m/Y') }}</dd>
                    </div>
                @endif
                @if($proposta->data_fim_prevista)
                    <div>
                        <dt class="text-gray-500">Fim Previsto</dt>
                        <dd class="text-gray-900">{{ $proposta->data_fim_prevista->format('d/m/Y') }}</dd>
                    </div>
                @endif
                <div class="col-span-2">
                    <dt class="text-gray-500">Objeto</dt>
                    <dd class="text-gray-900 mt-0.5">{{ $proposta->objeto }}</dd>
                </div>
                @if($proposta->justificativa)
                    <div class="col-span-2">
                        <dt class="text-gray-500">Justificativa</dt>
                        <dd class="text-gray-900 mt-0.5">{{ $proposta->justificativa }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Documentos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Documentos Anexados</h2>
                <span class="text-xs text-gray-400">Máx. 10 MB por arquivo — PDF, Word, Excel, JPG, PNG</span>
            </div>

            @if($proposta->aceitaDocumentosDaOsc())
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <form action="{{ route('documentos.store', $proposta) }}" method="POST" enctype="multipart/form-data"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo do Documento</label>
                            <select name="tipo" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                                @foreach(\App\Models\Documento::TIPOS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Arquivo</label>
                            <input type="file" name="arquivo" required
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        </div>
                        <button type="submit"
                                class="btn btn-primary">
                            Enviar
                        </button>
                    </form>
                    @error('arquivo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                </div>
            @endif

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
                            </p>
                            {{-- A recusa só serve se disser o que corrigir. --}}
                            @if($doc->recusado())
                                <p class="text-xs text-red-600 mt-0.5">
                                    Recusado em {{ $doc->analisado_em?->format('d/m/Y') }}
                                    @if($doc->analise_motivo) — {{ $doc->analise_motivo }} @endif
                                    <span class="text-gray-500">Envie o documento corrigido.</span>
                                </p>
                            @elseif($doc->aprovado())
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Aprovado pelo município em {{ $doc->analisado_em?->format('d/m/Y') }}
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
                        {{-- Retirar enquanto ninguém decidiu, ou depois de recusado
                             (parte de corrigir). Aprovado já integra a instrução. --}}
                        @if($doc->podeSerRemovido())
                            <form action="{{ route('documentos.destroy', [$proposta, $doc]) }}" method="POST"
                                  data-confirm="Remover este documento?">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-6 py-6 text-sm text-gray-400 text-center">
                    Nenhum documento anexado.
                    @if($proposta->aceitaDocumentosDaOsc()) Use o formulário acima para enviar. @endif
                </p>
            @endforelse
        </div>

        {{-- Pareceres (leitura) --}}
        @if($proposta->pareceres->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Análise da Proposta</h2>
                </div>
                @foreach($proposta->pareceres as $parecer)
                    @php $color = \App\Models\Parecer::RESULTADO_COLORS[$parecer->resultado] ?? 'gray'; @endphp
                    <div class="px-6 py-4 border-b border-gray-50 last:border-0">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-gray-800">
                                {{ \App\Models\Parecer::TIPOS[$parecer->tipo] }}
                            </p>
                            <span class="px-2 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                {{ \App\Models\Parecer::RESULTADOS[$parecer->resultado] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400">{{ $parecer->data_parecer->format('d/m/Y') }}</p>
                        @if($parecer->resultado === 'diligencia')
                            <p class="text-sm text-accent-700 mt-2 bg-accent-50 px-3 py-2 rounded">
                                O órgão solicitou informações adicionais. Entre em contato com a Secretaria responsável.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Tela de trabalho da OSC: plano, metas, etapas e documentos numa coluna
         só. As setas somem sozinhas quando a página cabe na janela. --}}
    <x-atalhos-rolagem />
</x-portal-layout>
