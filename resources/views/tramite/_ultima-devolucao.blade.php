{{-- O motivo da última devolução, à vista (decisão da gestão, 30/09/2026):
     antes ele só aparecia no histórico recolhido. Fica enquanto o trâmite não
     andar de novo, com a lista do que ainda está para corrigir.
     Espera: $tramitacoes (qualquer ordem) e $pecas (os documentos do trâmite). --}}
@php
    $ultima = collect($tramitacoes)->sortByDesc('id')->first();
    $aCorrigir = collect($pecas)->filter(fn ($p) => $p->devolvida());
    $setorNome = fn ($s) => \App\Models\User::LOTACOES[$s] ?? strtoupper((string) $s);
@endphp
@if($ultima && $ultima->status === 'devolvido')
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4" role="alert">
        <p class="text-sm font-semibold text-red-800">
            Devolvido por {{ $setorNome($ultima->de_setor) }}
            em {{ $ultima->enviado_em?->format('d/m/Y H:i') }}
            @if($ultima->remetente) — {{ $ultima->remetente->name }} @endif
        </p>
        <p class="text-sm text-red-800 mt-1 whitespace-pre-line">{{ $ultima->parecer }}</p>
        @if($aCorrigir->isNotEmpty())
            <p class="text-xs font-semibold text-red-800 mt-3">Documentos a corrigir:</p>
            <ul class="mt-1 text-sm text-red-800 list-disc list-inside space-y-0.5">
                @foreach($aCorrigir as $doc)
                    <li><a href="#peca-{{ $doc->id }}" class="underline">{{ \App\Support\Devolucao::rotulo($doc) }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
