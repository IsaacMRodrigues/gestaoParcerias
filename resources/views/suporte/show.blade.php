{{-- Um chamado: a conversa, de cima para baixo, e o campo de resposta no fim.
     A nota interna aparece com faixa amarela e só para quem atende — ela nem
     chega ao HTML de quem abriu (ver SuporteController::show). --}}
@php
    $ehOsc = auth()->user()->ehRepresentanteOsc();
    $cor = \App\Models\Chamado::STATUS_COLORS[$chamado->status] ?? 'gray';
    $corCat = \App\Models\Chamado::CATEGORIAS_COLORS[$chamado->categoria] ?? 'gray';
    $souAutor = $chamado->user_id === auth()->id();
@endphp
<x-dynamic-component :component="$ehOsc ? 'portal-layout' : 'app-layout'">
    @unless($ehOsc)
        <x-slot name="header">
            <div class="min-w-0">
                <p class="text-sm text-gray-500">
                    <a href="{{ route('suporte.index') }}" class="hover:underline">&larr; Suporte</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5 truncate">{{ $chamado->assunto }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500">
                    <span class="px-2 py-0.5 text-xs font-semibold whitespace-nowrap rounded
                                 bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200">
                        {{ $chamado->statusLabel() }}
                    </span>
                    <span>{{ $chamado->numero }}</span>
                    <span class="text-gray-400">&middot;</span>
                    <span class="text-{{ $corCat }}-700">{{ $chamado->categoriaLabel() }}</span>
                </div>
            </div>
        </x-slot>
    @endunless

    <div class="{{ $ehOsc ? 'max-w-3xl' : 'max-w-4xl' }} mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if($ehOsc)
            <div>
                <p class="text-sm text-brand-600">
                    <a href="{{ route('suporte.index') }}" class="hover:underline">&larr; Suporte</a>
                </p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $chamado->assunto }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-gray-500">
                    <span class="px-2 py-0.5 text-xs font-semibold rounded
                                 bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200">
                        {{ $chamado->statusLabel() }}
                    </span>
                    <span>{{ $chamado->numero }}</span>
                    <span class="text-gray-400">&middot;</span>
                    <span>{{ $chamado->categoriaLabel() }}</span>
                </div>
            </div>
        @endif

        <x-flash-message />

        {{-- Ficha: quem abriu e de onde --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 text-sm">
            <dl class="grid sm:grid-cols-3 gap-x-6 gap-y-2">
                <div>
                    <dt class="text-gray-500">Aberto por</dt>
                    <dd class="text-gray-900">
                        {{ $chamado->autor_nome }}
                        @if($chamado->autor_vinculo)
                            <span class="block text-xs text-gray-500">{{ $chamado->autor_vinculo }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Abertura</dt>
                    <dd class="text-gray-900">{{ $chamado->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if($chamado->resolvido())
                    <div>
                        <dt class="text-gray-500">Resolvido</dt>
                        <dd class="text-gray-900">
                            {{ $chamado->resolvido_em?->format('d/m/Y H:i') }}
                            @if($chamado->resolvidoPor)
                                <span class="block text-xs text-gray-500">por {{ $chamado->resolvidoPor->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if($atende && $chamado->origem_url)
                    <div class="sm:col-span-3">
                        <dt class="text-gray-500">Tela de origem</dt>
                        <dd class="text-gray-700 break-all text-xs">{{ $chamado->origem_url }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- A conversa --}}
        <div class="space-y-3">
            @foreach($mensagens as $m)
                @php $daEquipe = $m->user_id !== $chamado->user_id; @endphp
                <div class="rounded-xl border p-5
                            {{ $m->interna
                                ? 'bg-accent-50 border-accent-200'
                                : ($daEquipe ? 'bg-brand-50/60 border-brand-200' : 'bg-white border-gray-200') }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $m->autor_nome }}
                            @if($m->autor_vinculo)
                                <span class="font-normal text-gray-500">· {{ $m->autor_vinculo }}</span>
                            @endif
                            @if($m->interna)
                                <span class="ml-1 px-1.5 py-0.5 text-[11px] font-semibold rounded bg-accent-100 text-accent-800">
                                    nota interna — a pessoa que abriu não vê
                                </span>
                            @endif
                        </p>
                        <span class="text-xs text-gray-400">{{ $m->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $m->mensagem }}</p>

                    @if($m->temArquivo())
                        <p class="mt-3 pt-3 border-t border-gray-100 text-sm">
                            <a href="{{ route('suporte.anexo', [$chamado, $m]) }}"
                               class="text-brand-700 hover:underline">{{ $m->arquivo_nome }}</a>
                            <span class="text-xs text-gray-400">· {{ $m->tamanhoLegivel() }}</span>
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Responder --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-3">
                {{ $atende && !$souAutor ? 'Responder' : 'Acrescentar' }}
            </h2>

            <form action="{{ route('suporte.responder', $chamado) }}" method="POST"
                  enctype="multipart/form-data" class="space-y-3">
                @csrf
                <textarea name="mensagem" rows="4" required
                          placeholder="{{ $atende && !$souAutor ? 'Escreva a resposta…' : 'Alguma informação a mais?' }}"
                          class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('mensagem') }}</textarea>
                <x-input-error :messages="$errors->get('mensagem')" />

                <input type="file" name="arquivo"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                <x-input-error :messages="$errors->get('arquivo')" />

                @if($atende)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="interna" value="0">
                        <input type="checkbox" name="interna" value="1"
                               class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                        Nota interna — some para quem abriu o chamado
                    </label>
                @endif

                <button class="btn btn-primary">Enviar</button>
            </form>
        </div>

        {{-- Situação: quem atende e quem abriu podem encerrar ou reabrir --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900">Situação do chamado</h2>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">
                {{ $chamado->resolvido()
                    ? 'Este chamado está encerrado. Escrever de novo o reabre.'
                    : 'Marque como resolvido quando o assunto estiver encerrado.' }}
            </p>
            <form action="{{ route('suporte.status', $chamado) }}" method="POST" class="flex flex-wrap gap-2">
                @csrf @method('PATCH')
                @if($chamado->resolvido())
                    <button name="status" value="em_andamento" class="btn btn-secondary btn-sm">Reabrir</button>
                @else
                    <button name="status" value="resolvido" class="btn btn-primary">Marcar como resolvido</button>
                @endif
            </form>
        </div>
    </div>
</x-dynamic-component>
