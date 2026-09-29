{{--
    Valores da Nova Proposta já no primeiro formulário (decisão da gestão,
    29/09/2026): os itens 5 (plano de aplicação), 6 (valor total e
    contrapartida) e 7 (cronograma de desembolso) do Plano de Trabalho. Grava
    tudo junto com a proposta; na tela seguinte o mesmo plano segue editável.

    As linhas vêm de old() quando a validação devolve o formulário, para a OSC
    não perder o que digitou.
--}}
@php
    $itensIniciais = collect(old('itens', []))->values()->map(fn ($i) => [
        'descricao'             => $i['descricao'] ?? '',
        'tipo_despesa'          => $i['tipo_despesa'] ?? array_key_first(\App\Models\Despesa::NATUREZAS),
        'unidade'               => $i['unidade'] ?? '',
        'quantidade'            => $i['quantidade'] ?? 1,
        'valor_unitario'        => $i['valor_unitario'] ?? '',
        'atividades_vinculadas' => $i['atividades_vinculadas'] ?? '',
    ])->all();
    $parcelasIniciais = collect(old('desembolsos', []))->values()->map(fn ($d) => [
        'ano'   => $d['ano'] ?? now()->year,
        'mes'   => (int) ($d['mes'] ?? 1),
        'valor' => $d['valor'] ?? '',
    ])->all();
    $inputCls = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

{{-- 6. Valor total da proposta e contrapartida --}}
<div class="pt-4 border-t border-gray-100">
    <h2 class="text-base font-semibold text-gray-800">Valor total da proposta e contrapartida</h2>
    <p class="text-xs text-gray-400 mt-0.5 mb-3">Item 6 do Plano de Trabalho — de onde vem o dinheiro da parceria.</p>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="valor_solicitado" value="Valor solicitado ao município (R$) *" />
            <x-input-dinheiro name="valor_solicitado" required class="mt-1" />
            <x-input-error :messages="$errors->get('valor_solicitado')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="valor_proprio" value="Contrapartida da OSC (R$)" />
            <x-input-dinheiro name="valor_proprio" class="mt-1" />
            <x-input-error :messages="$errors->get('valor_proprio')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="valor_outras_fontes" value="Outras fontes (R$)" />
            <x-input-dinheiro name="valor_outras_fontes" class="mt-1" />
            <x-input-error :messages="$errors->get('valor_outras_fontes')" class="mt-1" />
        </div>
    </div>
</div>

{{-- 5. Plano de aplicação dos recursos --}}
<div class="pt-4 border-t border-gray-100"
     x-data="{
        itens: @js($itensIniciais),
        novo() { this.itens.push({ descricao: '', tipo_despesa: @js(array_key_first(\App\Models\Despesa::NATUREZAS)), unidade: '', quantidade: 1, valor_unitario: '', atividades_vinculadas: '' }) },
        linha(i) { return (parseFloat(i.quantidade) || 0) * (parseFloat(i.valor_unitario) || 0) },
        total() { return this.itens.reduce((s, i) => s + this.linha(i), 0) },
        moeda(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
     }">
    <h2 class="text-base font-semibold text-gray-800">Plano de aplicação dos recursos</h2>
    <p class="text-xs text-gray-400 mt-0.5 mb-3">
        Item 5 do Plano de Trabalho (I — Demonstrativo de recursos): o que será comprado ou contratado, e por quanto.
    </p>
    <x-input-error :messages="$errors->get('itens')" class="mb-2" />

    <div class="space-y-3">
        <template x-for="(item, n) in itens" :key="n">
            <div class="border border-gray-200 rounded-lg p-3 grid sm:grid-cols-6 gap-3">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-medium text-gray-600">Descrição *</label>
                    <input type="text" :name="`itens[${n}][descricao]`" x-model="item.descricao" required maxlength="255" class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600">Tipo de despesa *</label>
                    <select :name="`itens[${n}][tipo_despesa]`" x-model="item.tipo_despesa" required class="{{ $inputCls }}">
                        @foreach(\App\Models\Despesa::NATUREZAS as $k => $rotulo)
                            <option value="{{ $k }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Unidade</label>
                    <input type="text" :name="`itens[${n}][unidade]`" x-model="item.unidade" maxlength="30" placeholder="mês, un." class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Qtd. *</label>
                    <input type="number" :name="`itens[${n}][quantidade]`" x-model="item.quantidade" step="0.01" min="0.01" required class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600">Valor unitário (R$) *</label>
                    <input type="number" :name="`itens[${n}][valor_unitario]`" x-model="item.valor_unitario" step="0.01" min="0" required class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2">
                    <span class="block text-xs font-medium text-gray-600">Valor total</span>
                    <span class="mt-1 block py-2 text-sm font-semibold text-gray-900" x-text="moeda(linha(item))"></span>
                </div>
                <div class="sm:col-span-5">
                    <label class="block text-xs font-medium text-gray-600">Atividades vinculadas</label>
                    <input type="text" :name="`itens[${n}][atividades_vinculadas]`" x-model="item.atividades_vinculadas" maxlength="255" class="{{ $inputCls }}">
                </div>
                <div class="flex items-end justify-end">
                    <button type="button" @click="itens.splice(n, 1)" class="text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
                </div>
            </div>
        </template>
        <p x-show="itens.length === 0" class="text-sm text-gray-400">Nenhum item lançado.</p>
    </div>

    <div class="mt-3 flex items-center justify-between gap-3">
        <button type="button" @click="novo()" class="btn btn-secondary btn-sm">Adicionar item</button>
        <p x-show="itens.length" class="text-sm text-gray-600">Total do plano de aplicação: <strong class="text-gray-900" x-text="moeda(total())"></strong></p>
    </div>
</div>

{{-- 7. Cronograma de desembolso --}}
<div class="pt-4 border-t border-gray-100"
     x-data="{
        parcelas: @js($parcelasIniciais),
        novo() { this.parcelas.push({ ano: @js(now()->year), mes: 1, valor: '' }) },
        total() { return this.parcelas.reduce((s, p) => s + (parseFloat(p.valor) || 0), 0) },
        moeda(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
     }">
    <h2 class="text-base font-semibold text-gray-800">Cronograma de desembolso</h2>
    <p class="text-xs text-gray-400 mt-0.5 mb-3">
        Item 7 do Plano de Trabalho: quando o recurso do município precisa entrar na conta da parceria. Uma linha por mês.
    </p>
    <x-input-error :messages="$errors->get('desembolsos')" class="mb-2" />

    <div class="space-y-2">
        <template x-for="(parcela, n) in parcelas" :key="n">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600">Ano *</label>
                    <input type="number" :name="`desembolsos[${n}][ano]`" x-model="parcela.ano" min="2020" max="2100" required
                           class="mt-1 w-28 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Mês *</label>
                    <select :name="`desembolsos[${n}][mes]`" x-model.number="parcela.mes" required
                            class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(\App\Models\PlanoDesembolso::MESES as $num => $nome)
                            <option value="{{ $num }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Valor (R$) *</label>
                    <input type="number" :name="`desembolsos[${n}][valor]`" x-model="parcela.valor" step="0.01" min="0.01" required
                           class="mt-1 w-40 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <button type="button" @click="parcelas.splice(n, 1)" class="pb-2 text-xs text-gray-400 hover:text-red-700 transition">Remover</button>
            </div>
        </template>
        <p x-show="parcelas.length === 0" class="text-sm text-gray-400">Nenhuma parcela lançada.</p>
    </div>

    <div class="mt-3 flex items-center justify-between gap-3">
        <button type="button" @click="novo()" class="btn btn-secondary btn-sm">Adicionar parcela</button>
        <p x-show="parcelas.length" class="text-sm text-gray-600">Total: <strong class="text-gray-900" x-text="moeda(total())"></strong></p>
    </div>
</div>
