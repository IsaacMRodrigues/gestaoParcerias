<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('instrumentos.show', $instrumento) }}" class="hover:underline">Instrumento {{ $instrumento->numero }}</a>
            <span class="text-gray-300">/</span> Execução
        </p>
        <h2 class="text-xl font-semibold text-gray-900 mt-0.5">Execução Financeira</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            @if(count($alertas))
                <div class="p-4 rounded-lg bg-amber-50 border border-amber-200">
                    <p class="text-sm font-semibold text-amber-800 mb-1">⚠️ Alertas de inconsistência</p>
                    <ul class="list-disc list-inside text-sm text-amber-700 space-y-0.5">
                        @foreach($alertas as $a) <li>{{ $a }}</li> @endforeach
                    </ul>
                </div>
            @endif

            {{-- Painel de saldo --}}
            @php
                $repassado = $instrumento->totalRepassado();
                $gasto = $instrumento->totalGasto();
                $saldo = $instrumento->saldo();
                $pct = $instrumento->percentualExecutado();
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Total repassado</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($repassado, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Total gasto</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($gasto, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl border {{ $saldo < 0 ? 'border-red-300' : 'border-gray-200' }} p-5">
                    <p class="text-sm text-gray-500">Saldo</p>
                    <p class="mt-1 text-2xl font-bold {{ $saldo < 0 ? 'text-red-600' : 'text-emerald-600' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Executado</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $pct }}%</p>
                    <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-brand-600" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Repasses --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Repasses recebidos</h3>
                </div>
                <form method="POST" action="{{ route('repasses.store', $instrumento) }}"
                      class="px-6 py-4 border-b border-gray-100 grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div><x-input-label for="parcela" value="Parcela" /><x-text-input id="parcela" name="parcela" type="number" min="1" class="mt-1 block w-full" /></div>
                    <div><x-input-label for="data_repasse" value="Data *" /><x-text-input id="data_repasse" name="data_repasse" type="date" required class="mt-1 block w-full" /></div>
                    <div><x-input-label for="valor_r" value="Valor (R$) *" /><x-text-input id="valor_r" name="valor" type="number" step="0.01" min="0.01" required class="mt-1 block w-full" /></div>
                    <div class="sm:col-span-2"><x-input-label for="documento" value="Documento / OB" /><x-text-input id="documento" name="documento" type="text" class="mt-1 block w-full" /></div>
                    <div><x-primary-button class="w-full justify-center">Registrar</x-primary-button></div>
                </form>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-2 text-left">Parcela</th><th class="px-6 py-2 text-left">Data</th><th class="px-6 py-2 text-right">Valor</th><th class="px-6 py-2 text-left">Documento</th><th class="px-6 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($instrumento->repasses as $r)
                            <tr>
                                <td class="px-6 py-2">{{ $r->parcela ? $r->parcela.'ª' : '—' }}</td>
                                <td class="px-6 py-2">{{ $r->data_repasse->format('d/m/Y') }}</td>
                                <td class="px-6 py-2 text-right font-medium">R$ {{ number_format($r->valor, 2, ',', '.') }}</td>
                                <td class="px-6 py-2 text-gray-500">{{ $r->documento ?? '—' }}</td>
                                <td class="px-6 py-2 text-right">
                                    <form action="{{ route('repasses.destroy', $r) }}" method="POST" data-confirm="Remover este repasse?">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400">Nenhum repasse registrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Despesas --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Despesas</h3>
                </div>
                <form method="POST" action="{{ route('despesas.store', $instrumento) }}" enctype="multipart/form-data"
                      class="px-6 py-4 border-b border-gray-100 grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div><x-input-label for="data_despesa" value="Data *" /><x-text-input id="data_despesa" name="data_despesa" type="date" required class="mt-1 block w-full" /></div>
                    <div><x-input-label for="valor_d" value="Valor (R$) *" /><x-text-input id="valor_d" name="valor" type="number" step="0.01" min="0.01" required class="mt-1 block w-full" /></div>
                    <div class="sm:col-span-2">
                        <x-input-label for="natureza" value="Natureza *" />
                        <select id="natureza" name="natureza" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            @foreach(\App\Models\Despesa::NATUREZAS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2"><x-input-label for="fornecedor" value="Fornecedor" /><x-text-input id="fornecedor" name="fornecedor" type="text" class="mt-1 block w-full" /></div>
                    <div class="sm:col-span-2"><x-input-label for="descricao" value="Descrição" /><x-text-input id="descricao" name="descricao" type="text" class="mt-1 block w-full" /></div>
                    <div><x-input-label for="nota_fiscal_numero" value="NF nº" /><x-text-input id="nota_fiscal_numero" name="nota_fiscal_numero" type="text" class="mt-1 block w-full" /></div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nota_fiscal" value="Nota fiscal (arquivo)" />
                        <input id="nota_fiscal" name="nota_fiscal" type="file" accept=".pdf,.jpg,.jpeg,.png"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" />
                    </div>
                    <div><x-primary-button class="w-full justify-center">Lançar</x-primary-button></div>
                </form>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-2 text-left">Data</th><th class="px-6 py-2 text-left">Natureza</th><th class="px-6 py-2 text-left">Fornecedor</th><th class="px-6 py-2 text-right">Valor</th><th class="px-6 py-2 text-left">NF</th><th class="px-6 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($instrumento->despesas as $d)
                            <tr>
                                <td class="px-6 py-2">{{ $d->data_despesa->format('d/m/Y') }}</td>
                                <td class="px-6 py-2">{{ $d->naturezaLabel() }}</td>
                                <td class="px-6 py-2 text-gray-600">{{ $d->fornecedor ?? '—' }}@if($d->descricao)<span class="block text-xs text-gray-400">{{ $d->descricao }}</span>@endif</td>
                                <td class="px-6 py-2 text-right font-medium">R$ {{ number_format($d->valor, 2, ',', '.') }}</td>
                                <td class="px-6 py-2">
                                    @if($d->temNotaFiscal())
                                        <a href="{{ route('despesas.nota.download', $d) }}" class="text-brand-600 hover:text-brand-900">📎 {{ $d->nota_fiscal_numero ?? 'baixar' }}</a>
                                    @else
                                        <span class="text-xs text-amber-600">sem NF</span>
                                    @endif
                                </td>
                                <td class="px-6 py-2 text-right">
                                    <form action="{{ route('despesas.destroy', $d) }}" method="POST" data-confirm="Remover esta despesa?">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400">Nenhuma despesa lançada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($porNatureza->count())
                    <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                        <span class="font-semibold text-gray-600">Por natureza:</span>
                        @foreach($porNatureza as $nat => $total)
                            <span>{{ \App\Models\Despesa::NATUREZAS[$nat] ?? $nat }}: <strong class="text-gray-800">R$ {{ number_format($total, 2, ',', '.') }}</strong></span>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
