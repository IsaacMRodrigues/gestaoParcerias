{{-- Os três textos do PMIS, na ordem do formulário. --}}
<div class="space-y-4">
    @foreach([
        'interesse_publico' => 'Interesse público envolvido',
        'diagnostico'       => 'Diagnóstico da realidade',
        'viabilidade'       => 'Viabilidade, custos, benefícios e prazos',
    ] as $campo => $rotulo)
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $rotulo }}</p>
            <p class="text-sm text-gray-800 mt-1 whitespace-pre-line">{{ $manifestacao->{$campo} }}</p>
        </div>
    @endforeach
</div>
