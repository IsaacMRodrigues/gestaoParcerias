{{-- Alterações da Parceria (3.3): a OSC vê as suas; o Município vê as que
     passam por ele. Mesma tela dos dois lados, como na prestação de contas. --}}
@php $layout = $oscId ? 'portal-layout' : 'app-layout'; @endphp
<x-dynamic-component :component="$layout">
    @if(! $oscId)
        <x-slot name="header">
            <h2 class="text-2xl font-bold text-gray-900">Alterações da Parceria</h2>
            <p class="text-sm text-gray-500 mt-0.5">Pedidos das organizações para mudar o que foi pactuado.</p>
        </x-slot>
    @endif

    <div class="{{ $oscId ? 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10' : 'py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' }} space-y-6">
        @if($oscId)
            <h1 class="text-2xl font-bold text-gray-900">Alterações da parceria</h1>
        @endif
        <x-flash-message />

        @if($oscId)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-900">Nova alteração</h2>
                <p class="text-sm text-gray-500 mt-0.5 mb-4">
                    Diga o que precisa mudar e por quê. Depois de abrir o pedido, o Plano de Trabalho
                    volta a ficar editável para você ajustar metas, aplicação e desembolso.
                </p>

                @if($instrumentos->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma parceria em vigor para alterar.</p>
                @else
                    <form action="{{ route('alteracoes.store') }}" method="POST" class="grid sm:grid-cols-2 gap-3">
                        @csrf
                        <div>
                            <x-input-label for="instrumento_id" value="Parceria *" />
                            <select id="instrumento_id" name="instrumento_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                                @foreach($instrumentos as $i)
                                    <option value="{{ $i->id }}">{{ $i->numero }} — {{ \Illuminate\Support\Str::limit($i->objeto, 50) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="titulo" value="Título da alteração *" />
                            <x-text-input id="titulo" name="titulo" type="text" required maxlength="255"
                                          placeholder="Ex.: remanejamento entre rubricas"
                                          :value="old('titulo')" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="descricao" value="Descrição das alterações desejadas *" />
                            <textarea name="descricao" id="descricao" rows="3" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('descricao') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="justificativa" value="Justificativa da alteração *" />
                            <textarea name="justificativa" id="justificativa" rows="3" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('justificativa') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-error :messages="$errors->all()" class="mb-2" />
                            <button class="btn btn-primary">Abrir pedido de alteração</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
            @forelse($alteracoes as $a)
                @php $cor = \App\Models\Alteracao::STATUS_COLORS[$a->status] ?? 'gray'; @endphp
                <a href="{{ route('alteracoes.show', $a) }}" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">
                            Alteração {{ $a->numero }} — {{ $a->titulo }}
                        </span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            {{ $a->instrumento?->numero }} · {{ $a->osc()?->name }}
                        </span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                            {{ $a->statusLabel() }}
                        </span>
                        @unless($a->decidida())
                            <span class="block text-[11px] text-gray-400 mt-1">
                                Etapa {{ $a->etapa + 1 }} de {{ count(\App\Models\Alteracao::ETAPAS) }} ·
                                {{ \App\Models\Alteracao::SETORES[$a->setor_atual] ?? $a->setor_atual }}
                            </span>
                        @endunless
                    </span>
                </a>
            @empty
                <div class="px-6 py-12"><x-empty-state icone="lista">Nenhum pedido de alteração.</x-empty-state></div>
            @endforelse
        </div>
    </div>
</x-dynamic-component>
