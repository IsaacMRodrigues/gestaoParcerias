@use('App\Support\HoraLegal')
@php
    // IP, navegador e contatos de quem assinou são dado pessoal: só para o administrador e os auditores.
    $completo = (bool) auth()->user()?->veTrilhaCompletaDasAssinaturas();
    $quando = fn ($em) => HoraLegal::emBrasilia($em)?->format('d/m/Y \à\s H:i:s');
@endphp

<x-portal-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
        @if($doc)
            <div class="bg-white rounded-xl shadow-sm border {{ $doc['integro'] === false ? 'border-red-200' : 'border-brand-200' }} p-8">
                <div class="flex items-center gap-3 mb-4">
                    @if($doc['integro'] === false)
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <h1 class="text-xl font-bold text-red-800">Documento inválido</h1>
                            <p class="text-sm text-gray-600">Validação recusada: o conteúdo foi alterado depois da assinatura, e o
                                código de integridade não confere. Este documento não vale como documento assinado; procure o órgão
                                que o emitiu.</p>
                        </div>
                    @else
                        <span class="text-3xl">✅</span>
                        <div>
                            <h1 class="text-xl font-bold text-brand-800">{{ $doc['integro'] ? 'Documento autêntico e íntegro' : 'Documento autêntico' }}</h1>
                            <p class="text-sm text-gray-500">
                                {{ $doc['integro']
                                    ? 'A assinatura confere e o texto é exatamente o do momento da assinatura.'
                                    : 'Assinatura eletrônica verificada.' }}
                            </p>
                        </div>
                    @endif
                </div>

                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Documento</dt>
                        <dd class="text-gray-900 font-medium text-right">{{ $doc['tipo'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">{{ $doc['ref_label'] }}</dt>
                        <dd class="text-gray-900 text-right">{{ $doc['ref'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">{{ $doc['extra_label'] }}</dt>
                        <dd class="text-gray-900 text-right">{{ $doc['extra'] }}</dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Assinado por</dt>
                        <dd class="text-gray-900 font-medium text-right">
                            {{ $doc['assinante'] }}
                            @if($cpf = $doc['evento']?->cpfMascarado())
                                <span class="block text-xs font-normal text-gray-500">CPF {{ $cpf }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Data da assinatura</dt>
                        <dd class="text-gray-900 text-right">{{ $quando($doc['assinado_em']) }} <span class="text-gray-500">(Brasília)</span></dd>
                    </div>
                    @if($doc['evento'])
                        <div class="py-2 flex justify-between gap-4">
                            <dt class="text-gray-500">Carimbo do tempo</dt>
                            <dd class="text-gray-900 text-right">
                                @if($doc['evento']->fonteDaHoraDaAssinatura())
                                    Hora Legal Brasileira, pelo NTP.br
                                    <span class="block text-xs text-gray-500">servidor {{ $doc['evento']->fonte_tempo }}</span>
                                @elseif($doc['evento']->evento === 'registro_anterior')
                                    Relógio do servidor
                                    <span class="block text-xs text-gray-500">assinatura anterior à trilha de auditoria</span>
                                @else
                                    Relógio do servidor
                                    <span class="block text-xs text-gray-500">a fonte oficial não respondeu no momento</span>
                                @endif
                            </dd>
                        </div>
                        @if($doc['evento']->autenticacao)
                            <div class="py-2 flex justify-between gap-4">
                                <dt class="text-gray-500">Autenticação</dt>
                                <dd class="text-gray-900 text-right">{{ $doc['evento']->autenticacao }}</dd>
                            </div>
                        @endif
                    @endif
                    <div class="py-2 flex justify-between gap-4">
                        <dt class="text-gray-500">Código de validação</dt>
                        <dd class="text-gray-900 font-mono text-right">{{ $doc['codigo'] }}</dd>
                    </div>
                    <div class="py-2">
                        <dt class="text-gray-500">Código de integridade do texto assinado (SHA-256)</dt>
                        @if($doc['hash_assinado'])
                            <dd class="mt-1 font-mono text-xs text-gray-900 break-all">{{ $doc['hash_assinado'] }}</dd>
                            @if($doc['integro'] === false)
                                <dd class="mt-2 text-xs text-red-700">Texto de hoje:
                                    <span class="font-mono break-all">{{ $doc['hash_atual'] }}</span></dd>
                            @elseif($doc['evento']?->evento === 'registro_anterior')
                                <dd class="mt-1 text-xs text-gray-500">Calculado em
                                    {{ HoraLegal::emBrasilia($doc['evento']->instante())->format('d/m/Y') }}, na implantação da trilha,
                                    sobre o texto como estava.</dd>
                            @endif
                        @else
                            <dd class="mt-1 text-xs text-gray-500">Esta assinatura não tem código de integridade registrado.</dd>
                        @endif
                    </div>

                    {{-- Assinado em sequência (o Termo): todas as
                         partes que já assinaram, e se ainda falta alguma. --}}
                    @if(!empty($doc['partes']))
                        <div class="py-2">
                            <dt class="text-gray-500 mb-1">Assinaturas do documento</dt>
                            <dd>
                                <ul class="space-y-1">
                                    @foreach($doc['partes'] as $parte)
                                        <li class="flex justify-between gap-4 text-sm">
                                            <span class="text-gray-900"><strong>{{ $parte['rotulo'] }}:</strong> {{ $parte['nome'] }}</span>
                                            <span class="text-gray-500 text-right">{{ HoraLegal::emBrasilia($parte['em'])->format('d/m/Y H:i') }} · <a href="{{ route('validacao.mostrar', $parte['codigo']) }}" class="font-mono hover:underline">{{ $parte['codigo'] }}</a></span>
                                        </li>
                                    @endforeach
                                </ul>
                                @unless($doc['completo'])
                                    <p class="mt-1 text-xs text-accent-800">Ainda faltam assinaturas: o documento não está concluído.</p>
                                @endunless
                            </dd>
                        </div>
                    @endif

                    {{-- Assinatura das partes: o Termo de Parceria só está completo
                         com a contra-assinatura da OSC, e quem valida precisa ver
                         quem assinou pelos dois lados. --}}
                    @if(!empty($doc['contra_assinante']))
                        <div class="py-2 flex justify-between gap-4">
                            <dt class="text-gray-500">Contra-assinado pela OSC</dt>
                            <dd class="text-gray-900 font-medium text-right">
                                {{ $doc['contra_assinante'] }}
                                @if(!empty($doc['contra_osc']))
                                    <span class="block text-xs font-normal text-gray-500">{{ $doc['contra_osc'] }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="py-2 flex justify-between gap-4">
                            <dt class="text-gray-500">Data da contra-assinatura</dt>
                            <dd class="text-gray-900 text-right">{{ $quando($doc['contra_assinado_em']) }} <span class="text-gray-500">(Brasília)</span></dd>
                        </div>
                        <div class="py-2 flex justify-between gap-4">
                            <dt class="text-gray-500">Código da contra-assinatura</dt>
                            <dd class="text-gray-900 font-mono text-right">{{ $doc['contra_codigo'] }}</dd>
                        </div>
                    @endif
                </dl>

                @if(!empty($doc['conteudo']))
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $doc['integro'] === false ? 'Texto de hoje (diferente do assinado, sem validade)' : 'Cópia do documento' }}</h2>
                        <div class="documento-copia border border-gray-200 rounded-lg bg-white p-6 sm:p-8 overflow-x-auto text-[14px] text-gray-900 leading-relaxed">
                            {!! $doc['conteudo'] !!}
                        </div>
                        @unless($doc['integro'] === false)
                            <p class="mt-3 text-xs text-gray-400">
                                Cópia fiel do documento assinado eletronicamente (código {{ $doc['codigo'] }}@if(!empty($doc['contra_codigo'])); contra-assinatura da OSC: {{ $doc['contra_codigo'] }}@endif).
                            </p>
                        @endunless
                    </div>
                @endif

                <a href="{{ route('validacao.index') }}" class="inline-block mt-6 text-sm text-brand-600 hover:underline">
                    ← Validar outro documento
                </a>
            </div>

            <style>
                .documento-copia p { margin: .4rem 0; line-height: 1.6; text-align: justify; }
                .documento-copia ul, .documento-copia ol { padding-left: 1.5rem; }
                .documento-copia img { max-width: 120px; height: auto; }
                .documento-copia strong { font-weight: 700; }
                .documento-copia table { border-collapse: collapse; }
                .documento-copia table:not([style*="border:none"]) { width: 100%; }
                .documento-copia th, .documento-copia td { border: 1px solid #555; padding: 4px 8px; font-size: 12px; }
                .documento-copia table[style*="border:none"] td { border: none; }
            </style>
        @elseif($anulacao)
            <div class="bg-white rounded-xl shadow-sm border border-accent-200 p-8">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">⛔</span>
                    <div>
                        <h1 class="text-xl font-bold text-accent-900">Assinatura anulada</h1>
                        <p class="text-sm text-gray-600 mt-0.5">
                            O código <strong class="font-mono">{{ $codigo }}</strong> validava uma assinatura de
                            <strong>{{ $anulacao->documento_rotulo }}</strong>, anulada em {{ $quando($anulacao->instante()) }}
                            (Brasília) porque o documento foi devolvido para correção. O texto que ela assinou não vale mais.
                        </p>
                    </div>
                </div>
                @if($vigentes)
                    <p class="mt-4 text-sm text-gray-700">O documento corrigido foi assinado de novo. Valide pelo código atual:
                        @foreach($vigentes as $vigente)
                            <a href="{{ route('validacao.mostrar', $vigente) }}" class="font-mono text-brand-700 hover:underline">{{ $vigente }}</a>@if(!$loop->last), @endif
                        @endforeach
                    </p>
                @endif
                <a href="{{ route('validacao.index') }}" class="inline-block mt-6 text-sm text-brand-600 hover:underline">← Validar outro documento</a>
            </div>
        @elseif($eventos->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-accent-200 p-8">
                <h1 class="text-xl font-bold text-accent-900">Documento fora do sistema</h1>
                <p class="text-sm text-gray-600 mt-1">
                    A assinatura de código <strong class="font-mono">{{ $codigo }}</strong> está na trilha de auditoria, mas o
                    documento ({{ $eventos->first()->documento_rotulo }}) não está mais disponível no sistema.
                </p>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-red-200 p-8 text-center">
                <span class="text-3xl">⚠️</span>
                <h1 class="text-xl font-bold text-red-800 mt-2">Documento não encontrado</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Nenhum documento assinado corresponde ao código
                    <strong class="font-mono">{{ $codigo }}</strong>.
                    Confira o código e tente novamente.
                </p>
                <a href="{{ route('validacao.index') }}"
                   class="btn btn-primary mt-6">
                    Tentar novamente
                </a>
            </div>
        @endif

        {{-- Trilha de auditoria do documento (Lei 14.063/2020): cada evento preso ao anterior pelo hash. --}}
        @if(isset($trilha) && $trilha->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h2 class="text-base font-semibold text-gray-900">Trilha de auditoria</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Cada evento guarda o código do anterior: alterar ou apagar qualquer um deles quebra a corrente e aparece aqui.
                    @unless($completo) Endereço IP e navegador ficam com a administração e os auditores. @endunless
                </p>

                <ol class="mt-4 space-y-3">
                    @foreach($trilha as $e)
                        @php $id = $e->dadosDeIdentidade(); $meta = $e->dadosDaRequisicao(); $det = $e->dadosDoEvento(); @endphp
                        <li class="rounded-lg border {{ $e->elo_integro ? 'border-gray-200' : 'border-red-200 bg-red-50' }} p-3 text-xs text-gray-600 space-y-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="text-sm text-gray-900">
                                    <strong>{{ $e->rotuloDoEvento() }}</strong>@if($e->rotuloDoPapel()) — {{ $e->rotuloDoPapel() }}@endif
                                </p>
                                <span class="shrink-0 {{ $e->elo_integro ? 'text-brand-700' : 'text-red-700 font-semibold' }}">
                                    {{ $e->elo_integro ? '✓ elo íntegro' : '⚠ elo quebrado' }}
                                </span>
                            </div>
                            <p>{{ HoraLegal::emBrasilia($e->instante())->format('d/m/Y \à\s H:i:s.v') }} (Brasília) ·
                                {{ $e->fonte_tempo ? 'Hora Legal Brasileira (NTP.br, ' . $e->fonte_tempo . ')' : 'relógio do servidor' }}</p>
                            @if(!empty($id['nome']))
                                <p>{{ $id['nome'] }}@if(!empty($id['cargo'])), {{ $id['cargo'] }}@endif
                                    @if($cpf = $e->cpfMascarado()) · CPF {{ $cpf }}@endif</p>
                            @endif
                            @if($e->codigo_validacao)
                                <p>Código de validação <span class="font-mono">{{ $e->codigo_validacao }}</span></p>
                            @endif
                            @if($e->hash_documento)
                                <p>Texto (SHA-256) <span class="font-mono break-all">{{ $e->hash_documento }}</span></p>
                            @endif

                            @if($completo)
                                <div class="mt-2 pt-2 border-t border-gray-100 space-y-1">
                                    @if($e->autenticacao)<p>Autenticação: {{ $e->autenticacao }}</p>@endif
                                    @if($e->ip)<p>IP: <span class="font-mono">{{ $e->ip }}</span>
                                        @if(($meta['ip_da_conexao'] ?? null) && $meta['ip_da_conexao'] !== $e->ip) · conexão <span class="font-mono">{{ $meta['ip_da_conexao'] }}</span>@endif</p>@endif
                                    @if(!empty($meta['x_forwarded_for']))<p>X-Forwarded-For: <span class="font-mono break-all">{{ $meta['x_forwarded_for'] }}</span></p>@endif
                                    @if(!empty($meta['navegador']))<p class="break-all">Navegador: {{ $meta['navegador'] }}</p>@endif
                                    @if(!empty($id['email']))<p>E-mail: {{ $id['email'] }}@if(!empty($id['lotacao'])) · {{ $id['lotacao'] }}@endif</p>@endif
                                    @if(!empty($id['conta_aprovada_por']))<p>Conta aprovada por {{ $id['conta_aprovada_por'] }}@if(!empty($id['conta_aprovada_em'])) em {{ HoraLegal::emBrasilia(\Illuminate\Support\Carbon::parse($id['conta_aprovada_em'], 'UTC'))->format('d/m/Y') }}@endif</p>@endif
                                    @if(!empty($det['motivo']))<p>Motivo: {{ $det['motivo'] }}</p>@endif
                                    @if(!empty($det['observacao']))<p>{{ $det['observacao'] }}</p>@endif
                                    <p>Evento nº {{ $e->id }} · hash <span class="font-mono break-all">{{ $e->hash }}</span></p>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
</x-portal-layout>
