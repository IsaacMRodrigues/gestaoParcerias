{{-- Etapa do recurso: o que as OSCs protocolaram, ao lado da Resposta ao recurso. --}}
<div class="px-6 py-4 border-b border-gray-100">
    <p class="text-sm font-semibold text-gray-800">Recurso da OSC</p>
    <p class="text-xs text-gray-400 mt-0.5">
        Arquivo que a OSC anexou contra o resultado provisório
        @if($chamamento->prazo_recurso_ate) (prazo até {{ $chamamento->prazo_recurso_ate->format('d/m/Y') }})@endif.
        Havendo recurso, a Comissão de Seleção responde com a "Resposta ao recurso" abaixo, que passa a ser obrigatória.
    </p>

    @forelse($chamamento->recursos as $rec)
        <div class="mt-2 flex items-center justify-between gap-3 flex-wrap rounded-lg border border-gray-200 px-3 py-2">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $rec->osc->name ?? 'OSC' }}</p>
                <p class="text-xs text-gray-500">Protocolado em {{ $rec->protocolado_em?->format('d/m/Y H:i') }}</p>
            </div>
            @if($rec->temArquivo())
                <a href="{{ route('recursos.download', $rec) }}" class="btn btn-secondary btn-sm">
                    Baixar {{ $rec->arquivo_nome }} ({{ $rec->tamanhoFormatado() }})
                </a>
            @endif
        </div>
    @empty
        <p class="mt-2 text-sm text-gray-500">
            @if($chamamento->faseRecursalAberta())
                Prazo de recurso aberto — nenhum recurso protocolado até o momento.
            @elseif($chamamento->prazo_recurso_ate)
                Prazo de recurso encerrado sem recursos: a resposta não é necessária.
            @else
                O prazo de recurso abre quando a SCP publica o Resultado Provisório.
            @endif
        </p>
    @endforelse
</div>
