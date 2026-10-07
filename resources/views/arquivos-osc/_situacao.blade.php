{{-- Situação dos Arquivos da OSC num fluxo que os usa no lugar de pedir os documentos de novo.
     Espera: $osc (?Osc) e $parceria (?Proposta), para contar também as recusas da UG nela. --}}
@php
    $pendencias = $osc?->pendenciasDosArquivos($parceria ?? null) ?? [];
    $link = auth()->user()->ehRepresentanteOsc()
        ? ['url' => route('portal.arquivos.index'), 'rotulo' => 'Abrir Arquivos da OSC']
        : ($osc ? ['url' => route('oscs.arquivos', $osc), 'rotulo' => 'Ver os arquivos'] : null);
@endphp

<div class="rounded-xl border p-4 text-sm {{ $pendencias ? 'border-accent-200 bg-accent-50' : 'border-brand-200 bg-brand-50' }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="font-semibold {{ $pendencias ? 'text-accent-800' : 'text-brand-800' }}">
                Arquivos da OSC — {{ $pendencias ? 'faltam documentos' : 'completos e em dia' }}
            </p>
            <p class="text-xs mt-0.5 {{ $pendencias ? 'text-accent-700' : 'text-brand-700' }}">
                Certidões, estatuto, ata e declarações vêm da área da organização: não se anexam de novo aqui.
            </p>
            @if($pendencias)
                <ul class="mt-2 list-disc list-inside text-accent-800 space-y-0.5">
                    @foreach($pendencias as $p)<li>{{ $p }}</li>@endforeach
                </ul>
            @endif
        </div>
        @if($link)
            <a href="{{ $link['url'] }}" class="btn btn-outline btn-sm shrink-0">{{ $link['rotulo'] }}</a>
        @endif
    </div>
</div>
