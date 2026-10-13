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
                <div class="p-4 rounded-lg bg-accent-50 border border-accent-200">
                    <p class="text-sm font-semibold text-accent-800 mb-1">⚠️ Alertas de inconsistência</p>
                    <ul class="list-disc list-inside text-sm text-accent-700 space-y-0.5">
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
                    <p class="mt-1 text-2xl font-bold {{ $saldo < 0 ? 'text-red-600' : 'text-brand-600' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</p>
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
                    <div><x-input-label for="valor_r" value="Valor (R$) *" /><x-input-dinheiro name="valor" id="valor_r" required class="mt-1" /></div>
                    <div class="sm:col-span-2"><x-input-label for="documento" value="Documento / OB" /><x-text-input id="documento" name="documento" type="text" class="mt-1 block w-full" /></div>
                    <div><x-primary-button class="w-full justify-center">Registrar</x-primary-button></div>
                </form>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-2 text-left">Parcela</th><th class="px-6 py-2 text-left">Data</th><th class="px-6 py-2 text-right">Valor</th><th class="px-6 py-2 text-left">Documento</th><th class="px-6 py-2"></th></tr>
                    </thead>
                    @forelse($instrumento->repasses as $r)
                        {{-- um tbody por repasse: dá escopo ao Alpine para alternar
                             entre a linha de leitura e a de edição --}}
                        <tbody x-data="{ editando: false }" class="border-t border-gray-100">
                            <tr x-show="!editando">
                                <td class="px-6 py-2">{{ $r->parcela ? $r->parcela.'ª' : '—' }}</td>
                                <td class="px-6 py-2">{{ $r->data_repasse->format('d/m/Y') }}</td>
                                <td class="px-6 py-2 text-right font-medium">R$ {{ number_format($r->valor, 2, ',', '.') }}</td>
                                <td class="px-6 py-2 text-gray-500">{{ $r->documento ?? '—' }}</td>
                                <td class="px-6 py-2">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" @click="editando = true"
                                                class="text-xs text-brand-600 hover:text-brand-800">Editar</button>
                                        <form action="{{ route('repasses.destroy', $r) }}" method="POST" data-confirm="Remover este repasse?">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="editando" style="display:none" class="bg-brand-50/50">
                                <td colspan="5" class="px-6 py-4">
                                    <form action="{{ route('repasses.update', $r) }}" method="POST"
                                          class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div>
                                            <x-input-label value="Parcela" />
                                            <x-text-input name="parcela" type="number" min="1" class="mt-1 block w-full"
                                                          value="{{ $r->parcela }}" />
                                        </div>
                                        <div>
                                            <x-input-label value="Data *" />
                                            <x-text-input name="data_repasse" type="date" required class="mt-1 block w-full"
                                                          value="{{ $r->data_repasse->format('Y-m-d') }}" />
                                        </div>
                                        <div>
                                            <x-input-label value="Valor (R$) *" />
                                            {{-- id próprio: são vários repasses na mesma página, e o
                                                 campo oculto se liga ao visível pelo id --}}
                                            <x-input-dinheiro name="valor" id="valor_repasse_{{ $r->id }}"
                                                              :value="$r->valor" required class="mt-1" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label value="Documento / OB" />
                                            <x-text-input name="documento" type="text" class="mt-1 block w-full"
                                                          value="{{ $r->documento }}" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="submit"
                                                    class="btn btn-primary btn-sm">
                                                Salvar
                                            </button>
                                            <button type="button" @click="editando = false"
                                                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="5" class="px-6 py-10"><x-empty-state icone="dinheiro">Nenhum repasse registrado.</x-empty-state></td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            {{-- Despesas: lançadas pela OSC no painel da prestação de contas, conferidas lá --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Despesas</h3>
                        <p class="text-xs text-gray-500 mt-0.5">A OSC lança cada despesa, com a nota, no painel da prestação de contas; a conferência é feita lá.</p>
                    </div>
                    <a href="{{ route('lancamentos.painel', $instrumento) }}" class="btn btn-outline btn-sm">Abrir o painel de lançamentos</a>
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-2 text-left">Data</th><th class="px-6 py-2 text-left">Natureza</th><th class="px-6 py-2 text-left">Fornecedor</th><th class="px-6 py-2 text-right">Valor</th><th class="px-6 py-2 text-left">NF</th><th class="px-6 py-2 text-left">Situação</th></tr>
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
                                        <a href="{{ route('despesas.nota.download', $d) }}" class="font-semibold text-brand-700 hover:text-brand-800 transition">{{ $d->nota_fiscal_numero ?? 'baixar' }}</a>
                                    @else
                                        <span class="text-xs text-accent-700">sem nota</span>
                                    @endif
                                </td>
                                <td class="px-6 py-2">
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded ring-1 {{ ['pendente' => 'bg-accent-50 text-accent-800 ring-accent-200', 'conferido' => 'bg-brand-50 text-brand-800 ring-brand-200', 'devolvido' => 'bg-red-50 text-red-700 ring-red-200'][$d->situacao] ?? '' }}">{{ $d->rotuloDaSituacao() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10"><x-empty-state icone="dinheiro">Nenhuma despesa lançada pela OSC.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($porNatureza->count())
                    <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                        <span class="font-semibold text-gray-600">Por natureza:</span>
                        @foreach($porNatureza as $nat => $total)
                            <span>{{ \App\Models\Despesa::rotuloNatureza($nat) }}: <strong class="text-gray-800">R$ {{ number_format($total, 2, ',', '.') }}</strong></span>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
