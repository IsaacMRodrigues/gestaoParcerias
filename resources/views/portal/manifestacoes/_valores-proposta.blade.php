{{-- Nova Proposta, primeiro formulário: a planilha do plano de aplicação. O valor pleiteado
     começa como o total dela. As linhas vêm de old() quando a validação devolve o formulário. --}}
@php
    $itensIniciais = collect(old('itens', []))->values()->map(fn ($i) => [
        'descricao'             => $i['descricao'] ?? '',
        'tipo_despesa'          => $i['tipo_despesa'] ?? array_key_first(\App\Models\Despesa::NATUREZAS),
        'unidade'               => $i['unidade'] ?? '',
        'quantidade'            => $i['quantidade'] ?? 1,
        'valor_unitario'        => $i['valor_unitario'] ?? '',
        'atividades_vinculadas' => $i['atividades_vinculadas'] ?? '',
    ])->all();
    $inputCls = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

<div class="pt-4 border-t border-gray-100"
     x-data="{
        itens: @js($itensIniciais),
        novo() { this.itens.push({ descricao: '', tipo_despesa: @js(array_key_first(\App\Models\Despesa::NATUREZAS)), unidade: '', quantidade: 1, valor_unitario: '', atividades_vinculadas: '' }) },
        linha(i) { return (parseFloat(i.quantidade) || 0) * (parseFloat(i.valor_unitario) || 0) },
        total() { return this.itens.reduce((s, i) => s + this.linha(i), 0) },
        moeda(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
     }">
    <h2 class="text-base font-semibold text-gray-800">Plano de aplicação dos recursos (Anexar planilha)</h2>
    <p class="text-xs text-gray-400 mt-0.5 mb-3">O que será comprado ou contratado, e por quanto.</p>
    <x-input-error :messages="$errors->get('itens')" class="mb-2" />

    <div class="space-y-3">
        <template x-for="(item, n) in itens" :key="n">
            <div class="border border-gray-200 rounded-lg p-3 grid sm:grid-cols-6 gap-3">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-medium text-gray-600">Descrição *</label>
                    <input type="text" :name="`itens[${n}][descricao]`" x-model="item.descricao" required maxlength="255" class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600">Natureza da despesa *</label>
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
