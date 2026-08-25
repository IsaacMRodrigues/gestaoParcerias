{{-- Card de proposta da OSC. Espera: $proposta, $cor --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-3 flex flex-wrap items-center justify-between gap-4">
    <div class="flex-1 min-w-[16rem]">
        <p class="text-xs text-gray-400 mb-0.5">
            <x-selo-modalidade :tipo="$proposta->chamamento->tipo" />
            <span class="ml-1">
                {{ $proposta->chamamento->programa->orgao->sigla ?? $proposta->chamamento->programa->orgao->name }}
                · {{ $proposta->chamamento->titulo }}
            </span>
        </p>
        <p class="font-semibold text-gray-900">{{ $proposta->titulo }}</p>
        <div class="flex flex-wrap gap-4 mt-1 text-xs text-gray-500">
            <span>R$ {{ number_format($proposta->valor_solicitado, 2, ',', '.') }}</span>
            @if($proposta->submitted_at)
                <span>Submetida em {{ $proposta->submitted_at->format('d/m/Y') }}</span>
            @else
                <span>Criada em {{ $proposta->created_at->format('d/m/Y') }}</span>
            @endif
            @if($proposta->instrumento)
                <span class="text-brand-700 font-medium">Termo {{ $proposta->instrumento->numero }}</span>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <span class="px-2 py-1 text-xs font-medium bg-{{ $cor }}-100 text-{{ $cor }}-800 rounded-full">
            {{ \App\Models\Proposta::STATUS[$proposta->status] }}
        </span>
        {{-- A Celebração é o que a OSC tem a fazer agora; por isso vem antes. --}}
        @if($proposta->temTramiteCelebracao())
            <a href="{{ route('celebracao.show', $proposta) }}"
               class="text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Celebração →</a>
        @endif
        <a href="{{ route('portal.proposta.show', $proposta) }}"
           class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition">Abrir</a>
    </div>
</div>
