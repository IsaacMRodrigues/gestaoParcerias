{{-- Relatórios da prestação de contas: filtros, a tabela na tela e os mesmos dados em planilha e PDF. --}}
@php
    $campo = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
    $atual = $filtros['relatorio'];
    $baixar = fn (string $formato) => route('prestacao-contas.relatorios', array_filter($filtros + ['formato' => $formato]) + ['formato' => $formato]);
    $direita = ['dinheiro', 'pct', 'numero'];
    // Texto longo quebra com largura mínima; o resto (número, período, datas) fica numa linha.
    $longos = ['osc', 'secretaria', 'situacao', 'natureza'];
    $celula = fn (string $chave, string $tipo) => in_array($tipo, $direita, true) ? 'text-right whitespace-nowrap'
        : (in_array($chave, $longos, true) ? '' : 'whitespace-nowrap');
    $largura = fn (string $chave) => in_array($chave, $longos, true) ? 'min-width:10rem' : '';
@endphp
<x-dynamic-component :component="$ehOsc ? 'portal-layout' : 'app-layout'">
    @unless($ehOsc)
        <x-slot name="header">
            <p class="text-sm text-gray-500"><a href="{{ route('prestacao-contas.index') }}" class="hover:underline">← Prestação de Contas</a></p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Relatórios da prestação de contas</h2>
        </x-slot>
    @endunless

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if($ehOsc)
            <div>
                <p class="text-sm text-gray-500"><a href="{{ route('prestacao-contas.index') }}" class="hover:underline">← Prestação de contas</a></p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">Relatórios da prestação de contas</h1>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Qual relatório e os filtros --}}
        <form method="GET" action="{{ route('prestacao-contas.relatorios') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm">
            {{-- Antes das abas: o botão clicado vem depois e prevalece. --}}
            <input type="hidden" name="relatorio" value="{{ $atual }}">
            <div class="px-6 pt-4 border-b border-gray-200 flex flex-wrap gap-1">
                @foreach(\App\Support\RelatorioPrestacao::RELATORIOS as $chave => $rotulo)
                    <button type="submit" name="relatorio" value="{{ $chave }}"
                            class="px-3 py-2 -mb-px text-sm font-semibold border-b-2 transition {{ $atual === $chave ? 'border-brand-600 text-brand-800' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        {{ $rotulo }}
                    </button>
                @endforeach
            </div>

            <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                @unless($ehOsc)
                    <div><x-input-label value="Secretaria" />
                        <select name="orgao_id" class="{{ $campo }}">
                            <option value="">Todas</option>
                            @foreach($orgaos as $o)<option value="{{ $o->id }}" @selected(($filtros['orgao_id'] ?? null) == $o->id)>{{ $o->name }}</option>@endforeach
                        </select></div>
                    <div><x-input-label value="OSC" />
                        <select name="osc_id" class="{{ $campo }}">
                            <option value="">Todas</option>
                            @foreach($oscs as $o)<option value="{{ $o->id }}" @selected(($filtros['osc_id'] ?? null) == $o->id)>{{ $o->name }}</option>@endforeach
                        </select></div>
                @endunless
                <div class="{{ $ehOsc ? 'sm:col-span-2' : '' }}"><x-input-label value="Parceria" />
                    <select name="instrumento_id" class="{{ $campo }}">
                        <option value="">Todas</option>
                        @foreach($parcerias as $p)
                            <option value="{{ $p->id }}" @selected(($filtros['instrumento_id'] ?? null) == $p->id)>{{ $p->numero }}{{ $ehOsc ? '' : ' — ' . $p->proposta?->osc?->name }}</option>
                        @endforeach
                    </select></div>
                <div><x-input-label value="De" /><input type="date" name="de" value="{{ $filtros['de'] ?? '' }}" class="{{ $campo }}"></div>
                <div><x-input-label value="Até" /><input type="date" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="{{ $campo }}"></div>
                @if($atual === 'prestacoes')
                    <div><x-input-label value="Situação" />
                        <select name="situacao" class="{{ $campo }}">
                            <option value="">Todas</option>
                            @foreach(\App\Support\RelatorioPrestacao::SITUACOES as $k => $nome)<option value="{{ $k }}" @selected(($filtros['situacao'] ?? null) === $k)>{{ $nome }}</option>@endforeach
                        </select></div>
                @endif
                <div class="col-span-2 sm:col-span-6 flex flex-wrap items-center gap-2">
                    <button class="btn btn-primary btn-sm">Gerar relatório</button>
                    <a href="{{ route('prestacao-contas.relatorios', ['relatorio' => $atual]) }}" class="text-sm text-gray-500 hover:text-gray-800 px-2">Limpar filtros</a>
                    <span class="flex-1"></span>
                    <a href="{{ $baixar('planilha') }}" class="btn btn-outline btn-sm">Baixar planilha</a>
                    <a href="{{ $baixar('pdf') }}" class="btn btn-outline btn-sm">Baixar PDF</a>
                </div>
            </div>
        </form>

        {{-- O relatório --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">{{ $r['titulo'] }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ implode(' · ', $r['filtros']) }}</p>
                @if($atual === 'execucao')
                    <p class="text-xs text-gray-500 mt-1">Repasses e despesas dentro do período, somados nos quatro blocos do Anexo VI. Escolha uma parceria para ver o plano de aplicação aprovado contra o executado.</p>
                @endif
            </div>

            @foreach($r['tabelas'] as $t)
                @if($t['titulo'])
                    <h3 class="px-6 pt-6 pb-2 text-sm font-semibold text-gray-800">{{ $t['titulo'] }}</h3>
                    @if(isset($t['totais']['aprovado']) && (float) $t['totais']['aprovado'] === 0.0)
                        <p class="px-6 pb-2 text-xs text-accent-700">O plano de aplicação desta parceria não tem itens lançados: o aprovado sai zerado.</p>
                    @endif
                @endif
                @if(empty($t['linhas']))
                    <div class="px-6 py-10"><x-empty-state icone="lista">Nada encontrado com esses filtros.</x-empty-state></div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                <tr>
                                    @foreach($t['colunas'] as [$rotulo, $tipo])
                                        <th class="px-3 py-2 whitespace-nowrap {{ in_array($tipo, $direita, true) ? 'text-right' : 'text-left' }}">{{ $rotulo }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($t['linhas'] as $linha)
                                    <tr class="hover:bg-gray-50">
                                        @foreach($t['colunas'] as $chave => [$rotulo, $tipo])
                                            @php $v = $linha[$chave] ?? null; @endphp
                                            <td class="px-3 py-2 {{ $celula($chave, $tipo) }} {{ $tipo === 'dinheiro' && (float) $v < 0 ? 'text-red-700' : 'text-gray-700' }}" style="{{ $largura($chave) }}">
                                                {{ \App\Support\RelatorioPrestacao::formatar($v, $tipo) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            @if($t['totais'])
                                <tfoot class="bg-gray-50 font-semibold text-gray-900 border-t border-gray-200">
                                    <tr>
                                        @foreach($t['colunas'] as $chave => [$rotulo, $tipo])
                                            <td class="px-3 py-2 {{ $celula($chave, $tipo) }}">
                                                {{ array_key_exists($chave, $t['totais']) ? \App\Support\RelatorioPrestacao::formatar($t['totais'][$chave], $tipo) : '' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-dynamic-component>
