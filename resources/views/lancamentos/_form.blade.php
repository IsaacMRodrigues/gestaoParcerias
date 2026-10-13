{{-- Formulário de um lançamento do painel, novo ou em correção.
     Espera: $instrumento, $tipo ('despesa' ou um tipo de Lancamento) e $item (null = novo). --}}
@php
    $novo = $item === null;
    $sufixo = $novo ? $tipo . '_novo' : $tipo . '_' . $item->id;
    $acao = $tipo === 'despesa'
        ? ($novo ? route('lancamentos.despesas.store', $instrumento) : route('lancamentos.despesas.update', $item))
        : ($novo ? route('lancamentos.store', $instrumento) : route('lancamentos.update', $item));
    $campo = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
    $arquivoCss = 'mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100';
    $mes = fn ($i) => $i?->competencia?->format('Y-m');
    $dia = fn ($i) => $i?->competencia?->format('Y-m-d');
@endphp

<form action="{{ $acao }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
    @csrf
    @unless($novo) @method('PUT') @endunless
    @if($tipo !== 'despesa' && $novo)
        <input type="hidden" name="tipo" value="{{ $tipo }}">
    @endif

    @switch($tipo)
        @case('despesa')
            <div><x-input-label value="Data do pagamento *" />
                <input type="date" name="data_despesa" required class="{{ $campo }}" value="{{ $item?->data_despesa?->format('Y-m-d') }}"></div>
            <div><x-input-label value="Valor *" />
                <x-input-dinheiro name="valor" :id="'valor_' . $sufixo" :value="$item?->valor" required class="mt-1" /></div>
            <div class="sm:col-span-2"><x-input-label value="Natureza *" />
                <select name="natureza" required class="{{ $campo }}">
                    @foreach(\App\Models\Despesa::NATUREZAS as $k => $rotulo)
                        <option value="{{ $k }}" @selected($item?->natureza === $k)>{{ $rotulo }}</option>
                    @endforeach
                </select></div>
            <div class="sm:col-span-2"><x-input-label value="Fornecedor" />
                <input name="fornecedor" maxlength="255" class="{{ $campo }}" value="{{ $item?->fornecedor }}"></div>
            <div><x-input-label value="CPF/CNPJ do fornecedor" />
                <input name="doc_fornecedor" maxlength="18" class="{{ $campo }}" value="{{ $item?->doc_fornecedor }}"></div>
            <div class="sm:col-span-3"><x-input-label value="Descrição" />
                <input name="descricao" maxlength="255" class="{{ $campo }}" value="{{ $item?->descricao }}"></div>
            <div><x-input-label value="Nota fiscal nº" />
                <input name="nota_fiscal_numero" maxlength="255" class="{{ $campo }}" value="{{ $item?->nota_fiscal_numero }}"></div>
            <div class="sm:col-span-2"><x-input-label :value="$novo ? 'Nota fiscal ou comprovante *' : 'Trocar a nota fiscal'" />
                <input type="file" name="nota_fiscal" accept=".pdf,.jpg,.jpeg,.png" @required($novo) class="{{ $arquivoCss }}"></div>
            @break

        @case('extrato_conta')
        @case('extrato_aplicacao')
            <div><x-input-label value="Mês *" />
                <input type="month" name="competencia" required class="{{ $campo }}" value="{{ $mes($item) }}"></div>
            <div class="sm:col-span-3"><x-input-label value="Observação" />
                <input name="descricao" maxlength="1000" class="{{ $campo }}" value="{{ $item?->descricao }}"></div>
            <div class="sm:col-span-2"><x-input-label :value="$novo ? 'Extrato *' : 'Trocar o extrato'" />
                <input type="file" name="arquivo" @required($novo) class="{{ $arquivoCss }}"></div>
            @break

        @case('evidencia')
            <div><x-input-label value="Data *" />
                <input type="date" name="competencia" required class="{{ $campo }}" value="{{ $dia($item) }}"></div>
            <div class="sm:col-span-2"><x-input-label value="Meta do plano de trabalho" />
                <select name="meta_id" class="{{ $campo }}">
                    <option value="">— Sem meta específica —</option>
                    @foreach($instrumento->proposta?->metas ?? [] as $meta)
                        <option value="{{ $meta->id }}" @selected($item?->meta_id === $meta->id)>{{ \Illuminate\Support\Str::limit($meta->descricao, 70) }}</option>
                    @endforeach
                </select></div>
            <div class="sm:col-span-3"><x-input-label value="O que mostra *" />
                <input name="descricao" required maxlength="1000" placeholder="Ex.: lista de presença da oficina de 12/10"
                       class="{{ $campo }}" value="{{ $item?->descricao }}"></div>
            <div class="sm:col-span-3"><x-input-label :value="$novo ? 'Arquivo (foto, lista, relatório) *' : 'Trocar o arquivo'" />
                <input type="file" name="arquivo" @required($novo) class="{{ $arquivoCss }}"></div>
            @break

        @case('folha')
            <div><x-input-label value="Mês *" />
                <input type="month" name="competencia" required class="{{ $campo }}" value="{{ $mes($item) }}"></div>
            @foreach(\App\Models\Lancamento::CAMPOS_DA_FOLHA as $chave => $rotulo)
                <div><x-input-label :value="$rotulo" />
                    @if($chave === 'folha_funcionarios')
                        <input type="number" min="0" step="1" name="{{ $chave }}" class="{{ $campo }}" value="{{ $item?->dados[$chave] ?? '' }}">
                    @else
                        <x-input-dinheiro :name="$chave" :id="$chave . '_' . $sufixo" :value="$item?->dados[$chave] ?? null" class="mt-1" />
                    @endif
                </div>
            @endforeach
            <div class="sm:col-span-3"><x-input-label value="Observação" />
                <input name="descricao" maxlength="1000" class="{{ $campo }}" value="{{ $item?->descricao }}"></div>
            <div class="sm:col-span-2"><x-input-label :value="$novo ? 'Folha ou holerites (arquivo)' : 'Trocar o arquivo'" />
                <input type="file" name="arquivo" class="{{ $arquivoCss }}"></div>
            @break

        @case('bem')
            <div><x-input-label value="Data da compra *" />
                <input type="date" name="competencia" required class="{{ $campo }}" value="{{ $dia($item) }}"></div>
            <div class="sm:col-span-3"><x-input-label value="Especificação do bem *" />
                <input name="descricao" required maxlength="250" class="{{ $campo }}" value="{{ $item?->descricao }}"></div>
            <div><x-input-label value="Quantidade *" />
                <input type="number" name="quantidade" required min="0.01" step="0.01" class="{{ $campo }}" value="{{ $item?->quantidade ?? 1 }}"></div>
            <div><x-input-label value="Valor unitário *" />
                <x-input-dinheiro name="valor" :id="'valor_' . $sufixo" :value="$item?->valor" required class="mt-1" /></div>
            <div><x-input-label value="Nota fiscal nº" />
                <input name="documento" maxlength="60" class="{{ $campo }}" value="{{ $item?->dados['documento'] ?? '' }}"></div>
            <div class="sm:col-span-3"><x-input-label :value="$novo ? 'Nota fiscal (arquivo)' : 'Trocar o arquivo'" />
                <input type="file" name="arquivo" class="{{ $arquivoCss }}"></div>
            @break
    @endswitch

    <div class="sm:col-span-6">
        <button class="btn btn-primary btn-sm">{{ $novo ? 'Lançar' : 'Salvar correção' }}</button>
    </div>
</form>
