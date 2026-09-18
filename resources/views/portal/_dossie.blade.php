{{-- O processo da parceria, como a OSC o vê (módulo 3.3).

     Quatro fases numa lista só: o que o município decidiu, o que publicou e o
     que a própria organização entregou. Só entra documento pronto e aberto —
     ver Proposta::dossieParaOsc(). Espera: $proposta, $dossie. --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800">Documentos do processo</h2>
        <p class="text-xs text-gray-400 mt-0.5">
            O que já foi assinado ou publicado em cada fase da parceria.
        </p>
    </div>

    @forelse($dossie as $fase => $documentos)
        <div class="px-6 py-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ $fase }}</p>
            <ul class="divide-y divide-gray-100">
                @foreach($documentos as $doc)
                    <li class="py-2 flex items-center justify-between gap-3">
                        <span class="min-w-0">
                            <a href="{{ route($doc->ehArquivoNoDossie() ? 'portal.dossie.baixar' : 'portal.dossie.mostrar',
                                              [$proposta, $doc->origemNoDossie(), $doc->id]) }}"
                               class="text-sm text-brand-700 hover:underline">
                                {{ $doc->rotuloDoDossie() }}
                            </a>
                            @if($doc->assinado())
                                <span class="block text-xs text-gray-400">
                                    Assinado por {{ $doc->assinanteNome() }} em {{ $doc->assinado_em->format('d/m/Y') }}
                                </span>
                            @endif
                        </span>
                        <span class="shrink-0 text-xs text-gray-400">
                            {{ $doc->ehArquivoNoDossie() ? 'baixar' : 'ler' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="px-6 py-6 text-sm text-gray-400">
            Nenhum documento do processo disponível ainda. Conforme as etapas avançam, o que for
            assinado e publicado aparece aqui.
        </p>
    @endforelse
</div>
