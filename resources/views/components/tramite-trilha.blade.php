{{-- Trilha de etapas de um trâmite (Seleção, Celebração).
     $etapas: array de ['setor' => ..., 'acao' => ...]
     $atual: índice da etapa corrente | $concluido: trâmite encerrado
     $labels: mapa setor => rótulo --}}
@props(['etapas', 'atual' => 0, 'concluido' => false, 'labels' => []])

<ol class="space-y-2">
    @foreach($etapas as $i => $etapa)
        @php
            $feita = $concluido || $i < $atual;
            $agora = !$concluido && $i === $atual;
        @endphp
        <li class="flex items-start gap-3 text-sm">
            <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full border text-[11px] font-bold flex items-center justify-center
                {{ $feita ? 'bg-green-100 border-green-300 text-green-700'
                         : ($agora ? 'bg-indigo-100 border-indigo-300 text-indigo-700'
                                   : 'bg-white border-gray-300 text-gray-400') }}">
                {{ $feita ? '✓' : $i + 1 }}
            </span>
            <span class="{{ $agora ? 'text-gray-900 font-medium' : ($feita ? 'text-gray-500' : 'text-gray-400') }}">
                <span class="text-xs font-semibold uppercase tracking-wide {{ $agora ? 'text-indigo-600' : 'text-gray-400' }}">
                    {{ $labels[$etapa['setor']] ?? strtoupper($etapa['setor']) }}
                </span>
                — {{ $etapa['acao'] }}
            </span>
        </li>
    @endforeach
</ol>
