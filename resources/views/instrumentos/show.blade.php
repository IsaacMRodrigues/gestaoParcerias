<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('instrumentos.index') }}" class="hover:underline">Instrumentos</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
                    {{ $instrumento->numero }}
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        — {{ \App\Models\Instrumento::TIPOS[$instrumento->tipo] ?? $instrumento->tipo }}
                    </span>
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('instrumentos.minuta', $instrumento) }}" target="_blank"
                   class="btn btn-secondary btn-sm">
                    Imprimir Minuta
                </a>
                @can('execucao')
                    @if($instrumento->status === 'vigente')
                        <a href="{{ route('instrumentos.execucao', $instrumento) }}"
                           class="btn btn-primary btn-sm">
                            Execução Financeira
                        </a>
                    @endif
                @endcan
                @if($instrumento->status === 'minuta')
                    <form action="{{ route('instrumentos.assinar', $instrumento) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="btn btn-primary btn-sm">
                            Marcar como Assinado
                        </button>
                    </form>
                @endif
                @if($instrumento->status === 'assinado' && !$instrumento->publicado_doe)
                    <form action="{{ route('instrumentos.publicar', $instrumento) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="btn btn-primary btn-sm">
                            Registrar Publicação (DOE)
                        </button>
                    </form>
                @endif
                <a href="{{ route('instrumentos.edit', $instrumento) }}"
                   class="btn btn-secondary btn-sm">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Dados do instrumento --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Dados do Instrumento</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">OSC</dt>
                        <dd class="font-medium text-gray-900">{{ $instrumento->proposta->osc->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">CNPJ da OSC</dt>
                        <dd class="text-gray-900">{{ $instrumento->proposta->osc->cnpj }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Programa</dt>
                        <dd class="text-gray-900">{{ $instrumento->proposta->chamamento->programa->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>
                            @php $color = \App\Models\Instrumento::STATUS_COLORS[$instrumento->status] ?? 'gray'; @endphp
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                {{ \App\Models\Instrumento::STATUS[$instrumento->status] }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Valor de Repasse</dt>
                        <dd class="font-medium text-gray-900">R$ {{ number_format($instrumento->valor_repasse, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Contrapartida</dt>
                        <dd class="text-gray-900">R$ {{ number_format($instrumento->valor_proprio, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Assinatura</dt>
                        <dd class="text-gray-900">{{ $instrumento->data_assinatura?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Vigência</dt>
                        <dd class="text-gray-900">
                            {{ $instrumento->data_inicio->format('d/m/Y') }} a
                            {{ $instrumento->dataFimVigente()->format('d/m/Y') }}
                            @if(!$instrumento->data_fim->eq($instrumento->dataFimVigente()))
                                <span class="text-xs text-gray-400">(prorrogado)</span>
                            @endif
                        </dd>
                    </div>
                    @if($instrumento->publicado_doe)
                        <div>
                            <dt class="text-gray-500">Publicação DOE</dt>
                            <dd class="text-gray-900">
                                {{ $instrumento->data_publicacao_doe?->format('d/m/Y') }}
                                @if($instrumento->numero_doe)
                                    — Edição {{ $instrumento->numero_doe }}
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div class="col-span-2">
                        <dt class="text-gray-500">Objeto</dt>
                        <dd class="text-gray-900">{{ $instrumento->objeto }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Termos Aditivos --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Termos Aditivos / Apostilamentos</h3>
                    @if(in_array($instrumento->status, ['assinado', 'vigente']))
                        <a href="{{ route('instrumentos.aditivos.create', $instrumento) }}"
                           class="btn btn-primary btn-sm">
                            + Novo Aditivo
                        </a>
                    @endif
                </div>

                @forelse($instrumento->aditivos as $aditivo)
                    <div class="px-6 py-4 border-b border-gray-100 last:border-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $aditivo->numero }}º Aditivo — {{ \App\Models\Aditivo::TIPOS[$aditivo->tipo] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if($aditivo->data_assinatura) Assinado em {{ $aditivo->data_assinatura->format('d/m/Y') }} @endif
                                    @if($aditivo->nova_data_fim) · Nova vigência: {{ $aditivo->nova_data_fim->format('d/m/Y') }} @endif
                                    @if($aditivo->valor_adicional) · R$ {{ number_format($aditivo->valor_adicional, 2, ',', '.') }} @endif
                                </p>
                                <p class="text-sm text-gray-600 mt-1">{{ $aditivo->descricao }}</p>
                            </div>
                            <div class="flex items-center gap-3 ml-4 shrink-0">
                                <span class="px-2 py-1 text-xs font-medium {{ $aditivo->status === 'assinado' ? 'bg-accent-100 text-accent-800' : 'bg-gray-100 text-gray-600' }} rounded-full">
                                    {{ \App\Models\Aditivo::STATUS[$aditivo->status] }}
                                </span>
                                <a href="{{ route('instrumentos.aditivos.documentacao', [$instrumento, $aditivo]) }}"
                                   class="text-xs text-gray-600 hover:text-gray-900">Documentação</a>
                                <a href="{{ route('instrumentos.aditivos.edit', [$instrumento, $aditivo]) }}"
                                   class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
                                <form action="{{ route('instrumentos.aditivos.destroy', [$instrumento, $aditivo]) }}"
                                      method="POST" class="inline"
                                      data-confirm="Remover este aditivo?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-gray-400 text-center">Nenhum termo aditivo registrado.</p>
                @endforelse
            </div>

            {{-- 2.3.1 Ordens de Pagamento --}}
            @can('ordem_pagamento')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Ordens de Pagamento</h3>
                    @if($instrumento->status === 'vigente')
                        @php $temGlobal = $instrumento->ordensPagamento->contains('tipo', 'global'); @endphp
                        <form action="{{ route('ordens-pagamento.create', $instrumento) }}" method="POST"
                              class="flex items-center gap-2">
                            @csrf
                            <select name="tipo"
                                    class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                @foreach(\App\Models\OrdemPagamento::TIPOS as $key => $label)
                                    <option value="{{ $key }}" {{ $key === 'global' && $temGlobal ? 'disabled' : '' }}>
                                        {{ $label }}{{ $key === 'global' && $temGlobal ? ' — já emitida' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm whitespace-nowrap">
                                + Nova OP
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-gray-400">Disponível quando o instrumento estiver vigente</span>
                    @endif
                </div>

                @forelse($instrumento->ordensPagamento as $op)
                    <div class="px-6 py-4 border-b border-gray-100 last:border-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">
                                    OP nº {{ $op->numero }}
                                    <span class="ml-1 px-1.5 py-0.5 text-[12px] font-medium rounded {{ $op->ehGlobal() ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $op->ehGlobal() ? 'Global' : 'Parcial' }}
                                    </span>
                                    @if($op->favorecido) <span class="font-normal text-gray-600">— {{ $op->favorecido }}</span> @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if($op->valor) R$ {{ number_format($op->valor, 2, ',', '.') }} @endif
                                    @if($op->data_emissao) · Emissão: {{ $op->data_emissao->format('d/m/Y') }} @endif
                                    @if($op->temDadosBancarios()) · 📎 dados bancários @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3 ml-4 shrink-0">
                                @if($op->assinado())
                                    <span class="px-2.5 py-1 text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200 rounded-md">Assinada</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Rascunho</span>
                                @endif
                                <a href="{{ route('ordens-pagamento.edit', $op) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">{{ $op->assinado() ? 'Abrir' : 'Editar' }}</a>
                                <a href="{{ route('ordens-pagamento.imprimir', $op) }}" target="_blank" class="text-xs text-gray-600 hover:text-gray-900">Imprimir</a>
                                @unless($op->assinado())
                                    <form action="{{ route('ordens-pagamento.destroy', $op) }}" method="POST" class="inline"
                                          data-confirm="Remover esta ordem de pagamento?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-gray-400 text-center">Nenhuma ordem de pagamento emitida.</p>
                @endforelse
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
