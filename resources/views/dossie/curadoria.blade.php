{{-- Curadoria do dossiê: o município escolhe o que a OSC enxerga.

     A cliente pediu "uma tela em que a SCP selecionava os documentos que
     aparecem para a OSC". O padrão já vem razoável — aberto para o que decide
     e para o que é publicado, fechado para a instrução interna —, e aqui se
     ajusta caso a caso. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-sm text-gray-500">
                <a href="{{ route('propostas.show', $proposta) }}" class="hover:underline">&larr; {{ $proposta->titulo }}</a>
            </p>
            <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Documentos visíveis à organização</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $proposta->osc?->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-sm text-gray-600">
                <p>
                    O que estiver marcado aparece para a organização na tela da inscrição dela,
                    <strong>desde que já esteja assinado ou anexado</strong> — documento em elaboração
                    nunca é mostrado, ainda que marcado aqui.
                </p>
                <p class="mt-2">
                    Os documentos do <strong>Planejamento</strong> não aparecem nesta lista: são internos da
                    Prefeitura e nunca são mostrados à organização. O Edital segue na página pública do chamamento.
                </p>
            </div>

            <form action="{{ route('dossie.curadoria.salvar', $proposta) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')

                @forelse($grupos as $fase => $documentos)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">{{ $fase }}</h3>
                            <span class="text-xs text-gray-400">{{ $documentos->count() }} documentos</span>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($documentos as $doc)
                                @php $pronto = $doc->assinado() || $doc->temArquivo(); @endphp
                                <li class="px-6 py-3 flex items-start gap-3">
                                    <input type="checkbox" name="abertas[]" value="{{ $doc->chaveDoDossie() }}"
                                           id="doc-{{ $doc->chaveDoDossie() }}"
                                           @checked($doc->visivel_osc)
                                           class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <label for="doc-{{ $doc->chaveDoDossie() }}" class="min-w-0 flex-1 cursor-pointer">
                                        <span class="block text-sm text-gray-900">{{ $doc->rotuloDoDossie() }}</span>
                                        <span class="block text-xs {{ $pronto ? 'text-gray-400' : 'text-accent-700' }}">
                                            @if($doc->assinado())
                                                Assinado por {{ $doc->assinanteNome() }} em {{ $doc->assinado_em->format('d/m/Y') }}
                                            @elseif($doc->temArquivo())
                                                Arquivo anexado
                                            @else
                                                Ainda não assinado — não aparecerá enquanto não estiver pronto
                                            @endif
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-12">
                        <x-empty-state icone="lista">Esta parceria ainda não tem documentos.</x-empty-state>
                    </div>
                @endforelse

                @if(count($grupos))
                    <button class="btn btn-primary">Salvar</button>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
