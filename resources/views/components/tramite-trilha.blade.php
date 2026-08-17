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
            {{-- Etapa atual em laranja, não em verde: verde suave marca o que já
                 passou, e antes as duas cores eram verde — a etapa corrente se
                 perdia no meio do histórico. --}}
            <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full border text-[12px] font-bold flex items-center justify-center
                {{ $feita ? 'bg-brand-100 border-brand-200 text-brand-700'
                         : ($agora ? 'bg-accent-500 border-accent-500 text-white'
                                   : 'bg-white border-gray-300 text-gray-400') }}">
                {{ $feita ? '✓' : $i + 1 }}
            </span>
            <span class="{{ $agora ? 'text-gray-900 font-medium' : ($feita ? 'text-gray-500' : 'text-gray-400') }}">
                <span class="text-xs font-semibold uppercase tracking-wide {{ $agora ? 'text-accent-700' : 'text-gray-400' }}">
                    {{ $labels[$etapa['setor']] ?? strtoupper($etapa['setor']) }}
                </span>
                — {{ $etapa['acao'] }}
            </span>
        </li>
    @endforeach
</ol>
