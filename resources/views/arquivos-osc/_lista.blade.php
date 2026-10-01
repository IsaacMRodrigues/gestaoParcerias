{{--
    "Arquivos da OSC" (30/09/2026), no desenho do portal do DF: um quadro por
    grupo, uma linha por documento, com a versão atual, o histórico e o envio de
    nova versão. Serve ao portal (a OSC anexa) e à Prefeitura (vê; e, na tela da
    proposta, analisa nesta parceria).

    @param $osc         Osc (com arquivos carregados)
    @param $podeEditar  bool — a OSC pode anexar nova versão
    @param $proposta    ?Proposta — na tela da proposta: análise nesta parceria
    @param $podeAnalisar bool
--}}
@php
    $atuais    = $osc->arquivosAtuais();
    $versoes   = $osc->arquivos->groupBy('tipo');
    $proposta  = $proposta ?? null;
    $podeAnalisar = $podeAnalisar ?? false;
    $analises  = $proposta
        ? \App\Models\OscArquivoAnalise::where('proposta_id', $proposta->id)->with('analista')->get()->keyBy('osc_arquivo_id')
        : collect();
    $campo = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

<div class="space-y-6">
    @foreach(\App\Models\OscArquivo::GRUPOS as $grupo)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-brand-800 text-white text-sm font-semibold uppercase tracking-wide">{{ $grupo['rotulo'] }}</div>
            <div class="divide-y divide-gray-100">
                @foreach($grupo['itens'] as $tipo => $rotulo)
                    @php
                        $atual = $atuais[$tipo] ?? null;
                        $historico = $versoes[$tipo] ?? collect();
                        $analise = $atual ? ($analises[$atual->id] ?? null) : null;
                    @endphp
                    <div id="arquivo-{{ $tipo }}" class="px-4 py-3 {{ $loop->odd ? 'bg-gray-50' : '' }}" style="scroll-margin-top:7rem">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 w-14 text-sm text-gray-500">{{ $atual?->id ?? '—' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 uppercase">{{ $rotulo }}</p>
                                <div class="mt-1 flex flex-wrap gap-1.5 text-[11px] font-semibold uppercase">
                                    @if(! $atual)
                                        <span class="px-1.5 py-0.5 rounded bg-red-600 text-white">Arquivo não anexado</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-gray-900 text-white">Versão atual: {{ $atual->versao }}</span>
                                        @if($atual->validade)
                                            @if($atual->vencida())
                                                <span class="px-1.5 py-0.5 rounded bg-red-600 text-white">Vencida em {{ $atual->validade->format('d/m/Y') }}</span>
                                            @elseif($atual->venceEmBreve())
                                                <span class="px-1.5 py-0.5 rounded bg-accent-100 text-accent-800">Vence em {{ $atual->validade->format('d/m/Y') }}</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded bg-brand-50 text-brand-800">Válida até {{ $atual->validade->format('d/m/Y') }}</span>
                                            @endif
                                        @endif
                                        @if($proposta)
                                            @if($analise)
                                                <span class="px-1.5 py-0.5 rounded {{ $analise->situacao === 'aprovado' ? 'bg-brand-50 text-brand-800' : 'bg-red-600 text-white' }}">
                                                    {{ \App\Models\OscArquivoAnalise::SITUACOES[$analise->situacao] }} nesta parceria
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded bg-accent-100 text-accent-800">Aguardando análise nesta parceria</span>
                                            @endif
                                        @endif
                                    @endif
                                </div>

                                @if($analise?->situacao === 'recusado' && $analise->motivo)
                                    <p class="mt-1.5 text-xs text-red-800 bg-red-50 border border-red-200 rounded px-2 py-1">
                                        Recusado por {{ $analise->analista?->name ?? '—' }} em {{ $analise->analisado_em->format('d/m/Y') }}: {{ $analise->motivo }}
                                    </p>
                                @endif

                                <div class="mt-2 pt-2 border-t border-dashed border-gray-200 flex flex-wrap items-start gap-2">
                                    @if($atual)
                                        <a href="{{ route('arquivos-osc.download', $atual) }}" class="btn btn-outline btn-sm">Baixar</a>
                                    @endif

                                    <details class="group">
                                        <summary class="btn btn-outline btn-sm cursor-pointer marker:content-none" style="list-style:none">Histórico • {{ str_pad((string) $historico->count(), 2, '0', STR_PAD_LEFT) }}</summary>
                                        <ul class="mt-2 space-y-1 text-xs text-gray-600">
                                            @forelse($historico as $versao)
                                                <li>
                                                    Versão {{ $versao->versao }} ·
                                                    <a href="{{ route('arquivos-osc.download', $versao) }}" class="text-brand-700 hover:underline">{{ $versao->arquivo_nome }}</a>
                                                    · {{ $versao->created_at->format('d/m/Y H:i') }}
                                                    @if($versao->remetente) · {{ $versao->remetente->name }} @endif
                                                    @if($versao->validade) · validade {{ $versao->validade->format('d/m/Y') }} @endif
                                                </li>
                                            @empty
                                                <li class="text-gray-400">Nenhuma versão enviada.</li>
                                            @endforelse
                                        </ul>
                                    </details>

                                    @if($podeEditar)
                                        <details class="group">
                                            <summary class="btn btn-outline btn-sm cursor-pointer marker:content-none" style="list-style:none">Editar</summary>
                                            <form action="{{ route('portal.arquivos.store', $tipo) }}" method="POST" enctype="multipart/form-data"
                                                  class="mt-2 grid sm:grid-cols-3 gap-2 items-end">
                                                @csrf
                                                <div class="sm:col-span-2">
                                                    <label class="block text-xs text-gray-500">Nova versão (PDF, JPG ou PNG — até 10 MB)</label>
                                                    <input type="file" name="arquivo" required accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600">
                                                </div>
                                                @if(\App\Models\OscArquivo::exigeValidade($tipo))
                                                    <div>
                                                        <label class="block text-xs text-gray-500">Válida até *</label>
                                                        <input type="date" name="validade" required min="{{ now()->format('Y-m-d') }}" class="{{ $campo }}">
                                                    </div>
                                                @endif
                                                <div class="sm:col-span-3">
                                                    <button class="btn btn-primary btn-sm">Enviar nova versão</button>
                                                </div>
                                            </form>
                                        </details>
                                        @if(\App\Models\OscArquivo::ehDeclaracao($tipo))
                                            <a href="{{ route('portal.arquivos.declaracao', $tipo) }}" target="_blank" class="btn btn-outline btn-sm">Texto para assinar</a>
                                        @endif
                                    @endif

                                    @if($proposta && $podeAnalisar && $atual)
                                        <details class="group" @if($analise === null) open @endif>
                                            <summary class="btn btn-outline btn-sm cursor-pointer marker:content-none" style="list-style:none">Analisar nesta parceria</summary>
                                            <form action="{{ route('propostas.arquivos-osc.analisar', [$proposta, $atual]) }}" method="POST"
                                                  class="mt-2 flex flex-wrap items-end gap-2">
                                                @csrf
                                                <select name="situacao" required class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                                    @foreach(\App\Models\OscArquivoAnalise::SITUACOES as $k => $lbl)
                                                        <option value="{{ $k }}" @selected($analise?->situacao === $k)>{{ $lbl }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="motivo" placeholder="Motivo (obrigatório na recusa)" value="{{ $analise?->motivo }}"
                                                       class="flex-1 min-w-[12rem] border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                                <button class="btn btn-primary btn-sm">Registrar</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
