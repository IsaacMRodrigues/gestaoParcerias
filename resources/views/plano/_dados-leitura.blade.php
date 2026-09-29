{{-- Itens 2 a 6 do plano em leitura — para quem analisa e para a OSC depois de apresentado. --}}
@php
    $duracao = collect([
        $dono->vigencia_dias ? $dono->vigencia_dias . ' dias corridos' : null,
        ($dono->data_inicio_prevista || $dono->data_fim_prevista)
            ? ($dono->data_inicio_prevista?->format('d/m/Y') ?? '—') . ' a ' . ($dono->data_fim_prevista?->format('d/m/Y') ?? '—')
            : null,
    ])->filter()->implode(' · ');
@endphp

<p class="text-sm font-semibold text-gray-800 mb-2">2 – Identificação do projeto</p>
<dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
    <div class="sm:col-span-2">
        <dt class="text-gray-500">Nome do projeto</dt>
        <dd class="text-gray-900 mt-0.5">{{ $dono->titulo }}</dd>
    </div>
    <div class="sm:col-span-2">
        <dt class="text-gray-500">Objeto de execução</dt>
        <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $dono->objeto }}</dd>
    </div>
    <div class="sm:col-span-2">
        <dt class="text-gray-500">Público alvo</dt>
        <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $dono->publico_alvo ?: '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Duração execução</dt>
        <dd class="text-gray-900 mt-0.5">{{ $duracao ?: '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Valor pleiteado</dt>
        <dd class="font-semibold text-gray-900 mt-0.5">R$ {{ number_format((float) $dono->valor_solicitado, 2, ',', '.') }}</dd>
    </div>
</dl>

<dl class="mt-5 space-y-3 text-sm border-t border-gray-100 pt-4">
    @foreach([
        '3 – Descrição da realidade' => $dono->descricao_realidade,
        '4 – Objetivos — Geral'      => $dono->objetivos,
        '4 – Objetivos — Específicos' => $dono->objetivos_especificos,
        '5 – Metodologia'            => $dono->metodologia,
        '6 – Diagnóstico/Justificativa' => $dono->justificativa,
    ] as $rotulo => $valor)
        <div>
            <dt class="font-medium text-gray-700">{{ $rotulo }}</dt>
            <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $valor ?: '—' }}</dd>
        </div>
    @endforeach
</dl>
