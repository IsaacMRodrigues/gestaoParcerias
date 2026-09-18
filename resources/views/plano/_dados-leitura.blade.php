{{-- Os dados do plano em leitura — para quem analisa e para a OSC depois de apresentado. --}}
<dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
    <div class="sm:col-span-2">
        <dt class="text-gray-500">Objeto</dt>
        <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $dono->objeto }}</dd>
    </div>

    @foreach([
        'Descrição da realidade' => $dono->descricao_realidade,
        'Justificativa'          => $dono->justificativa,
        'Público-alvo'           => $dono->publico_alvo,
        'Objetivos'              => $dono->objetivos,
    ] as $rotulo => $valor)
        @if($valor)
            <div class="sm:col-span-2">
                <dt class="text-gray-500">{{ $rotulo }}</dt>
                <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $valor }}</dd>
            </div>
        @endif
    @endforeach

    <div>
        <dt class="text-gray-500">Valor solicitado (PMSGRA)</dt>
        <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $dono->valor_solicitado, 2, ',', '.') }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Contrapartida da OSC</dt>
        <dd class="text-gray-900">R$ {{ number_format((float) $dono->valor_proprio, 2, ',', '.') }}</dd>
    </div>
    @if((float) $dono->valor_outras_fontes > 0)
        <div>
            <dt class="text-gray-500">Outras fontes</dt>
            <dd class="text-gray-900">R$ {{ number_format((float) $dono->valor_outras_fontes, 2, ',', '.') }}</dd>
        </div>
    @endif
    @if($dono->vigencia_dias)
        <div>
            <dt class="text-gray-500">Vigência proposta</dt>
            <dd class="text-gray-900">{{ $dono->vigencia_dias }} dias corridos</dd>
        </div>
    @endif
    @if($dono->data_inicio_prevista)
        <div>
            <dt class="text-gray-500">Início previsto</dt>
            <dd class="text-gray-900">{{ $dono->data_inicio_prevista->format('d/m/Y') }}</dd>
        </div>
    @endif
    @if($dono->data_fim_prevista)
        <div>
            <dt class="text-gray-500">Término previsto</dt>
            <dd class="text-gray-900">{{ $dono->data_fim_prevista->format('d/m/Y') }}</dd>
        </div>
    @endif

    @if($dono->atuacao_rede)
        <div class="sm:col-span-2 border-t border-gray-100 pt-3">
            <dt class="text-gray-500">Atuação em rede</dt>
            <dd class="text-gray-900 mt-0.5">
                {{ $dono->rede_razao_social }} — CNPJ {{ $dono->rede_cnpj }}
                @if($dono->rede_municipio) · {{ $dono->rede_municipio }} @endif
                @if($dono->rede_data_termo)
                    <span class="block text-xs text-gray-500">
                        Termo de Atuação em Rede assinado em {{ $dono->rede_data_termo->format('d/m/Y') }}
                    </span>
                @endif
            </dd>
        </div>
    @endif
</dl>
