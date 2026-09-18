{{-- Painel de suporte. Mesma tela para os dois lados: quem abre vê os seus
     chamados e o formulário; quem atende vê todos, com filtros. --}}
@php
    $ehOsc = auth()->user()->ehRepresentanteOsc();
    $layout = $ehOsc ? 'portal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    @unless($ehOsc)
        <x-slot name="header">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900">Suporte</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $atende
                        ? 'Dúvidas, problemas e sugestões de quem usa o sistema.'
                        : 'Fale com a equipe sobre o sistema e o seu uso.' }}
                </p>
            </div>
        </x-slot>
    @endunless

    <div class="{{ $ehOsc ? 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10' : 'py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8' }} space-y-6">
        @if($ehOsc)
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Suporte</h1>
                <p class="text-sm text-gray-500 mt-0.5">Fale com a equipe sobre o sistema e o seu uso.</p>
            </div>
        @endif

        <x-flash-message />

        {{-- Abrir chamado: primeiro, porque é o que a maioria vem fazer --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
             x-data="{ aberto: {{ $errors->any() ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-gray-900">Abrir um chamado</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Conte o que aconteceu, ou o que você gostaria que o sistema fizesse.
                    </p>
                </div>
                <button type="button" @click="aberto = !aberto" class="btn btn-primary shrink-0">
                    <span x-show="!aberto">Novo chamado</span>
                    <span x-show="aberto" x-cloak>Fechar</span>
                </button>
            </div>

            <form action="{{ route('suporte.store') }}" method="POST" enctype="multipart/form-data"
                  x-show="aberto" x-cloak class="mt-5 pt-5 border-t border-gray-100 grid sm:grid-cols-3 gap-4">
                @csrf
                {{-- A tela de onde a pessoa veio: metade do suporte é descobrir
                     onde ela estava quando aquilo aconteceu. --}}
                <input type="hidden" name="origem_url" value="{{ url()->previous() }}">

                <div>
                    <x-input-label for="categoria" value="Tipo *" />
                    <select name="categoria" id="categoria" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(\App\Models\Chamado::CATEGORIAS as $k => $rotulo)
                            <option value="{{ $k }}" @selected(old('categoria') === $k)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="assunto" value="Assunto *" />
                    <x-text-input id="assunto" name="assunto" type="text" required maxlength="160"
                                  placeholder="Em poucas palavras, do que se trata"
                                  :value="old('assunto')" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('assunto')" class="mt-1" />
                </div>

                <div class="sm:col-span-3">
                    <x-input-label for="mensagem" value="Descrição *" />
                    <textarea name="mensagem" id="mensagem" rows="5" required
                              placeholder="Se for um problema, diga o que você fez e o que apareceu na tela."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('mensagem') }}</textarea>
                    <x-input-error :messages="$errors->get('mensagem')" class="mt-1" />
                </div>

                <div class="sm:col-span-3">
                    <x-input-label for="arquivo" value="Anexo (opcional) — uma imagem da tela ajuda muito" />
                    <input type="file" name="arquivo" id="arquivo"
                           class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                    <x-input-error :messages="$errors->get('arquivo')" class="mt-1" />
                </div>

                <div class="sm:col-span-3">
                    <button class="btn btn-primary">Enviar chamado</button>
                </div>
            </form>
        </div>

        {{-- Filtros: só para quem atende, que é quem tem fila --}}
        @if($atende)
            <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="f-status" value="Situação" />
                    <select name="status" id="f-status"
                            class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todas</option>
                        @foreach(\App\Models\Chamado::STATUS as $k => $rotulo)
                            <option value="{{ $k }}" @selected(($filtros['status'] ?? '') === $k)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="f-categoria" value="Tipo" />
                    <select name="categoria" id="f-categoria"
                            class="mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Chamado::CATEGORIAS as $k => $rotulo)
                            <option value="{{ $k }}" @selected(($filtros['categoria'] ?? '') === $k)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-secondary btn-sm">Filtrar</button>
                @if(array_filter($filtros))
                    <a href="{{ route('suporte.index') }}" class="text-sm text-gray-500 hover:underline">limpar</a>
                @endif
                <span class="ml-auto text-sm text-gray-500">
                    <strong class="text-gray-800">{{ $abertos }}</strong> em aberto
                </span>
            </form>
        @endif

        {{-- A lista --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
            @forelse($chamados as $c)
                @php
                    $cor = \App\Models\Chamado::STATUS_COLORS[$c->status] ?? 'gray';
                    $corCat = \App\Models\Chamado::CATEGORIAS_COLORS[$c->categoria] ?? 'gray';
                @endphp
                <a href="{{ route('suporte.show', $c) }}" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">
                            {{ $c->numero }} — {{ $c->assunto }}
                        </span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            {{ $c->autor_nome }}@if($c->autor_vinculo) · {{ $c->autor_vinculo }}@endif
                            · {{ $c->created_at->format('d/m/Y H:i') }}
                        </span>
                    </span>
                    <span class="shrink-0 text-right space-y-1">
                        <span class="block px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap rounded
                                     bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200">
                            {{ $c->statusLabel() }}
                        </span>
                        <span class="block text-[11px] text-{{ $corCat }}-700">{{ $c->categoriaLabel() }}</span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-12">
                    <x-empty-state icone="lista">
                        {{ $atende ? 'Nenhum chamado por aqui.' : 'Você ainda não abriu nenhum chamado.' }}
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        @if($chamados->hasPages())
            <div>{{ $chamados->links() }}</div>
        @endif
    </div>
</x-dynamic-component>
