{{-- Painel da prestação de contas: a OSC lança ao longo da execução, a Prefeitura confere, e a
     prestação de um período se gera com o que foi lançado nele. --}}
@php
    $dinheiro = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $porAba = [
        'despesa'   => $despesas,
        'extrato'   => $lancamentos->whereIn('tipo', ['extrato_conta', 'extrato_aplicacao'])->values(),
        'evidencia' => $lancamentos->where('tipo', 'evidencia')->values(),
        'folha'     => $lancamentos->where('tipo', 'folha')->values(),
        'bem'       => $lancamentos->where('tipo', 'bem')->values(),
    ];
    $explicacao = [
        'despesa'   => 'Cada pagamento feito com o recurso da parceria, com a nota fiscal ou o comprovante.',
        'extrato'   => 'O extrato de cada mês da conta corrente específica e da conta de aplicação.',
        'evidencia' => 'Fotos, listas de presença e relatórios que mostram as metas sendo cumpridas.',
        'folha'     => 'A folha do mês, quando há despesa com pessoal: os valores somam no resumo da folha.',
        'bem'       => 'Equipamento ou material permanente comprado com o recurso (Anexo VII).',
    ];
    $acao = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer select-none transition marker:content-none';
    $campo = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
    // Voltou com erro de validação: abre a aba do formulário enviado.
    if ($errors->any() && (old('tipo') || old('data_despesa'))) {
        $aba = old('tipo') ? (str_starts_with(old('tipo'), 'extrato') ? 'extrato' : old('tipo')) : 'despesa';
    }
@endphp
<x-dynamic-component :component="$ehOsc ? 'portal-layout' : 'app-layout'">
    @unless($ehOsc)
        <x-slot name="header">
            <p class="text-sm text-gray-500">
                <a href="{{ route('instrumentos.execucao', $instrumento) }}" class="hover:underline">Execução {{ $instrumento->numero }}</a>
                <span class="text-gray-300">/</span> Painel da prestação de contas
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $instrumento->proposta?->osc?->name }}</h2>
            <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($instrumento->objeto, 120) }}</p>
        </x-slot>
    @endunless

    <div class="{{ $ehOsc ? 'max-w-5xl' : 'max-w-7xl' }} mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if($ehOsc)
            <div>
                <p class="text-sm text-gray-500"><a href="{{ route('prestacao-contas.index') }}" class="hover:underline">← Prestação de contas</a></p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">Painel da prestação de contas</h1>
                <p class="text-sm text-gray-500">Parceria {{ $instrumento->numero }} — {{ \Illuminate\Support\Str::limit($instrumento->objeto, 100) }}</p>
            </div>
        @endif

        <x-flash-message />
        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Como funciona e os números --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <p class="text-sm text-gray-600">
                @if($ehOsc)
                    Lance aqui, à medida que acontecem, as despesas, os extratos, as evidências, a folha e os bens da parceria.
                    A Prefeitura confere cada lançamento; se devolver algum, corrija e ele volta para a conferência.
                    Quando chegar a hora, gere a prestação de contas do período: ela já vem montada com o que foi lançado.
                @else
                    A OSC lança aqui, ao longo da execução, o que vai compor a prestação de contas. Confira cada lançamento
                    ou devolva com o motivo — a OSC é avisada por e-mail e corrige. A prestação do período nasce com o que foi lançado.
                @endif
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-4">
                @foreach([
                    ['Repassado', $dinheiro($instrumento->totalRepassado()), ''],
                    ['Despesas lançadas', $dinheiro($instrumento->totalGasto()), ''],
                    ['Saldo', $dinheiro($instrumento->saldo()), $instrumento->saldo() < 0 ? 'text-red-700' : ''],
                    ['A conferir', $contagem['pendente'] ?? 0, ($contagem['pendente'] ?? 0) ? 'text-accent-700' : ''],
                    ['Devolvidos', $contagem['devolvido'] ?? 0, ($contagem['devolvido'] ?? 0) ? 'text-red-700' : ''],
                ] as [$rotulo, $valor, $cor])
                    <div class="border border-gray-200 rounded-lg px-4 py-3">
                        <p class="text-xs text-gray-500">{{ $rotulo }}</p>
                        <p class="text-lg font-bold {{ $cor ?: 'text-gray-900' }}">{{ $valor }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lançamentos, por aba --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm" x-data="{ aba: @js($aba) }">
            <div class="px-6 pt-4 border-b border-gray-200 flex flex-wrap items-end justify-between gap-3">
                <nav class="flex flex-wrap gap-1 -mb-px">
                    @foreach(\App\Http\Controllers\LancamentoController::ABAS as $chave => $rotulo)
                        @php $aConferir = $porAba[$chave]->filter->aConferir()->count(); $voltaram = $porAba[$chave]->filter->devolvido()->count(); @endphp
                        <button type="button" @click="aba = '{{ $chave }}'"
                                :class="aba === '{{ $chave }}' ? 'border-brand-600 text-brand-800' : 'border-transparent text-gray-500 hover:text-gray-800'"
                                class="px-3 py-2 text-sm font-semibold border-b-2 transition flex items-center gap-1.5">
                            {{ $rotulo }}
                            <span class="text-xs font-medium text-gray-400">{{ $porAba[$chave]->count() }}</span>
                            @if($voltaram)<span class="px-1.5 rounded bg-red-50 text-red-700 text-[11px]">{{ $voltaram }} devolvido(s)</span>
                            @elseif($aConferir && ! $ehOsc)<span class="px-1.5 rounded bg-accent-50 text-accent-800 text-[11px]">{{ $aConferir }} a conferir</span>@endif
                        </button>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('lancamentos.painel', $instrumento) }}" class="flex items-center gap-2 pb-2">
                    <input type="hidden" name="aba" :value="aba">
                    <select name="mes" onchange="this.form.submit()" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todos os meses</option>
                        @foreach($meses as $m)
                            <option value="{{ $m }}" @selected($mes === $m)>{{ ucfirst(\Illuminate\Support\Carbon::parse($m . '-01')->locale('pt_BR')->translatedFormat('F/Y')) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @foreach(\App\Http\Controllers\LancamentoController::ABAS as $chave => $rotulo)
                <div x-show="aba === '{{ $chave }}'" @if($chave !== $aba) style="display:none" @endif>
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-start justify-between gap-3">
                        <p class="text-sm text-gray-500 max-w-2xl">{{ $explicacao[$chave] }}</p>
                        @if($podeConferir && $porAba[$chave]->filter->aConferir()->isNotEmpty())
                            <form action="{{ route('lancamentos.conferir-todos', $instrumento) }}" method="POST"
                                  data-confirm="Marcar como conferidos todos os lançamentos a conferir desta aba?">
                                @csrf <input type="hidden" name="aba" value="{{ $chave }}">
                                <button class="btn btn-outline btn-sm">Conferir todos os pendentes</button>
                            </form>
                        @endif
                    </div>

                    @if($ehOsc)
                        <div class="px-6 py-4 border-b border-gray-100">
                            @if($chave === 'extrato')
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['extrato_conta' => 'Extrato da conta corrente', 'extrato_aplicacao' => 'Extrato da aplicação'] as $tipo => $nome)
                                        <details class="group w-full">
                                            <summary class="{{ $acao }} text-brand-800 bg-brand-50 hover:bg-brand-100">+ {{ $nome }}</summary>
                                            <div class="mt-3">@include('lancamentos._form', ['tipo' => $tipo, 'item' => null])</div>
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <details class="group" @if($errors->any() && old('tipo', 'despesa') === $chave) open @endif>
                                    <summary class="{{ $acao }} text-brand-800 bg-brand-50 hover:bg-brand-100">
                                        + Lançar {{ ['despesa' => 'despesa', 'evidencia' => 'evidência', 'folha' => 'folha do mês', 'bem' => 'bem'][$chave] }}
                                    </summary>
                                    <div class="mt-3">@include('lancamentos._form', ['tipo' => $chave, 'item' => null])</div>
                                </details>
                            @endif
                        </div>
                    @endif

                    <div class="divide-y divide-gray-100">
                        @forelse($porAba[$chave] as $item)
                            @php
                                $ehDespesa = $item instanceof \App\Models\Despesa;
                                $linha = match (true) {
                                    $ehDespesa => [
                                        'titulo'  => $item->data_despesa->format('d/m/Y') . ' — ' . ($item->fornecedor ?: 'Fornecedor não informado'),
                                        'detalhe' => collect([$item->naturezaLabel(), $item->descricao, $item->nota_fiscal_numero ? 'NF ' . $item->nota_fiscal_numero : null])->filter()->implode(' · '),
                                        'valor'   => $dinheiro($item->valor),
                                        'url'     => $item->temNotaFiscal() ? route('lancamentos.despesas.nota', $item) : null,
                                        'nome'    => $item->nota_fiscal_nome,
                                    ],
                                    $item->tipo === 'folha' => [
                                        'titulo'  => 'Folha de ' . $item->referencia(),
                                        'detalhe' => collect([
                                            ($item->dados['folha_funcionarios'] ?? null) ? $item->dados['folha_funcionarios'] . ' funcionário(s)' : null,
                                            'proventos ' . $dinheiro(($item->dados['folha_salarios'] ?? 0) + ($item->dados['folha_vantagens'] ?? 0) + ($item->dados['folha_adicionais'] ?? 0)),
                                            'encargos ' . $dinheiro(($item->dados['folha_fgts'] ?? 0) + ($item->dados['folha_ferias'] ?? 0) + ($item->dados['folha_rescisao'] ?? 0)),
                                            $item->descricao,
                                        ])->filter()->implode(' · '),
                                        'valor'   => null,
                                        'url'     => $item->temArquivo() ? route('lancamentos.arquivo', $item) : null,
                                        'nome'    => $item->arquivo_nome,
                                    ],
                                    $item->tipo === 'bem' => [
                                        'titulo'  => $item->descricao,
                                        'detalhe' => 'Comprado em ' . $item->referencia() . ' · ' . rtrim(rtrim(number_format((float) $item->quantidade, 2, ',', '.'), '0'), ',') . ' × ' . $dinheiro($item->valor)
                                            . (($item->dados['documento'] ?? null) ? ' · NF ' . $item->dados['documento'] : ''),
                                        'valor'   => $dinheiro($item->totalDoBem()),
                                        'url'     => $item->temArquivo() ? route('lancamentos.arquivo', $item) : null,
                                        'nome'    => $item->arquivo_nome,
                                    ],
                                    default => [
                                        'titulo'  => $item->rotuloDoTipo() . ' — ' . $item->referencia(),
                                        'detalhe' => collect([$item->meta ? 'Meta: ' . \Illuminate\Support\Str::limit($item->meta->descricao, 80) : null, $item->descricao])->filter()->implode(' · '),
                                        'valor'   => null,
                                        'url'     => $item->temArquivo() ? route('lancamentos.arquivo', $item) : null,
                                        'nome'    => $item->arquivo_nome,
                                    ],
                                };
                            @endphp
                            @include('lancamentos._linha', [
                                'item' => $item, 'tipo' => $ehDespesa ? 'despesa' : $item->tipo,
                                'titulo' => $linha['titulo'], 'detalhe' => $linha['detalhe'], 'valor' => $linha['valor'],
                                'arquivoUrl' => $linha['url'], 'arquivoNome' => $linha['nome'],
                            ])
                        @empty
                            <div class="px-6 py-10"><x-empty-state icone="lista">Nada lançado{{ $mes ? ' neste mês' : '' }}.</x-empty-state></div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Prestações de contas desta parceria e a geração de uma nova --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Prestações de contas</h2>
                @if($ehOsc)
                    <p class="text-sm text-gray-500 mt-0.5">
                        Escolha o tipo e o período: a prestação nasce com as despesas, os extratos, as evidências, a folha e os bens
                        lançados nele. Os lançamentos devolvidos ficam de fora até serem corrigidos.
                    </p>
                @endif
            </div>
            @if($ehOsc)
                <form action="{{ route('prestacao-contas.store') }}" method="POST" class="px-6 py-4 border-b border-gray-100 grid sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <input type="hidden" name="instrumento_id" value="{{ $instrumento->id }}">
                    <div><x-input-label value="Tipo" />
                        <select name="tipo" class="{{ $campo }}">
                            @foreach(\App\Models\PrestacaoContas::TIPOS as $k => $r)<option value="{{ $k }}">{{ $r }}</option>@endforeach
                        </select></div>
                    <div><x-input-label value="Início do período" />
                        <input type="date" name="periodo_inicio" required class="{{ $campo }}"></div>
                    <div><x-input-label value="Fim do período" />
                        <input type="date" name="periodo_fim" required class="{{ $campo }}"></div>
                    <div><button class="btn btn-primary w-full justify-center">Gerar prestação de contas</button></div>
                </form>
            @endif
            <div class="divide-y divide-gray-100">
                @forelse($prestacoes as $pc)
                    <a href="{{ route('prestacao-contas.show', $pc) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-gray-50 transition">
                        <span class="text-sm font-medium text-gray-900">{{ $pc->rotulo() }}</span>
                        <span class="text-xs text-gray-500">
                            {{ $pc->concluida() ? 'Encerrada' : 'Etapa ' . ($pc->etapa + 1) . ' · ' . (\App\Models\PrestacaoContas::SETORES[$pc->setor] ?? $pc->setor) }}
                        </span>
                    </a>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-400">Nenhuma prestação de contas gerada ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-dynamic-component>
