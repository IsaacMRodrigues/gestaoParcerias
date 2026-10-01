{{-- Documento devolvido para correção: o motivo, no próprio documento
     (decisão da gestão, 30/09/2026). Espera: $peca (Peca ou ProcessoPeca). --}}
@if($peca->devolvida())
    <div class="mt-2 bg-red-50 border border-red-200 rounded-md px-3 py-2 text-xs text-red-800">
        <p class="font-semibold">
            Devolvido para correção
            @if($peca->devolvida_por_nome) por {{ $peca->devolvida_por_nome }}@endif
            @if($peca->devolvida_pelo_setor) ({{ \App\Models\User::LOTACOES[$peca->devolvida_pelo_setor] ?? strtoupper($peca->devolvida_pelo_setor) }})@endif
            em {{ $peca->devolvida_em->format('d/m/Y H:i') }}
        </p>
        <p class="mt-0.5 whitespace-pre-line">{{ $peca->devolucao_motivo }}</p>
    </div>
@endif
