{{-- A tela do pedido de alteração (3.3).

     Enquanto o pedido está com a OSC, ela edita aqui o próprio Plano de
     Trabalho — é o que o modelo manda. O quadro "o que mudou" compara o plano
     de agora com o retrato guardado na abertura, para quem analisa não ter de
     adivinhar. --}}
@php
    $u = auth()->user();
    $ehOsc = $u->ehRepresentanteOsc();
    $daVez = $u->setorNoTramite() === $alteracao->setor_atual && ! $alteracao->decidida();
    $pendencias = $alteracao->pendencias();
    $mudancas = $alteracao->mudancasNoPlano();
    $cor = \App\Models\Alteracao::STATUS_COLORS[$alteracao->status] ?? 'gray';
    $dinheiro = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $ultima = $alteracao->etapa === count(\App\Models\Alteracao::ETAPAS) - 1;
@endphp
<x-dynamic-component :component="$ehOsc ? 'portal-layout' : 'app-layout'">
    @unless($ehOsc)
        <x-slot name="header">
            <div>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('alteracoes.index') }}" class="hover:underline">← Alterações da Parceria</a>
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-0.5">Alteração {{ $alteracao->numero }} — {{ $alteracao->titulo }}</h2>
                <p class="text-sm text-gray-500">{{ $alteracao->instrumento?->numero }} · {{ $alteracao->osc()?->name }}</p>
            </div>
        </x-slot>
    @endunless

    <div class="{{ $ehOsc ? 'max-w-5xl' : 'max-w-7xl' }} mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if($ehOsc)
            <div>
                <p class="text-sm text-gray-500"><a href="{{ route('alteracoes.index') }}" class="hover:underline">← Alterações da parceria</a></p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">Alteração {{ $alteracao->numero }} — {{ $alteracao->titulo }}</h1>
                <p class="text-sm text-gray-500">{{ $alteracao->instrumento?->numero }}</p>
            </div>
        @endif

        <x-flash-message />

        {{-- Onde está --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @foreach(\App\Models\Alteracao::ETAPAS as $i => $etapa)
                    <span class="px-2.5 py-1 text-xs rounded-lg {{ $i === $alteracao->etapa && ! $alteracao->decidida()
                        ? 'bg-brand-600 text-white font-semibold'
                        : ($i < $alteracao->etapa || $alteracao->decidida() ? 'bg-brand-50 text-brand-800' : 'bg-gray-100 text-gray-500') }}">
                        {{ $i + 1 }}. {{ \App\Models\Alteracao::SETORES[$etapa['setor']] }}
                    </span>
                @endforeach
                <span class="ml-auto px-2 py-0.5 text-[11px] font-semibold bg-{{ $cor }}-50 text-{{ $cor }}-800 ring-1 ring-{{ $cor }}-200 rounded">
                    {{ $alteracao->statusLabel() }}
                </span>
            </div>
            <p class="text-sm text-gray-600">
                {{ $alteracao->decidida()
                    ? 'Decidida em ' . $alteracao->decidida_em?->format('d/m/Y H:i') . '.'
                    : \App\Models\Alteracao::ETAPAS[$alteracao->etapa]['acao'] . '.' }}
            </p>

            @if($alteracao->decidida() && $alteracao->decisao_motivo)
                <div class="mt-4 rounded-lg border p-4 {{ $alteracao->status === 'aprovada' ? 'bg-brand-50 border-brand-200' : 'bg-red-50 border-red-200' }}">
                    <p class="text-sm font-semibold {{ $alteracao->status === 'aprovada' ? 'text-brand-800' : 'text-red-800' }}">
                        {{ $alteracao->status === 'aprovada' ? 'Alteração aprovada' : 'Alteração indeferida' }}
                    </p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $alteracao->decisao_motivo }}</p>
                </div>
            @endif
        </div>

        {{-- O pedido --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">O pedido</h2>
            <p class="text-xs text-gray-400 mt-0.5 mb-4">
                É deste texto que a Proposta de Alteração se escreve — cada gravação regera o documento,
                até alguém assiná-lo.
            </p>

            @if($daVez && $alteracao->emElaboracao())
                <form action="{{ route('alteracoes.atualizar', $alteracao) }}" method="POST" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="titulo" value="Título *" />
                        <x-text-input id="titulo" name="titulo" type="text" required maxlength="255"
                                      :value="old('titulo', $alteracao->titulo)" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="descricao" value="Descrição das alterações desejadas *" />
                        <textarea name="descricao" id="descricao" rows="3" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('descricao', $alteracao->descricao) }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="justificativa" value="Justificativa *" />
                        <textarea name="justificativa" id="justificativa" rows="3" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('justificativa', $alteracao->justificativa) }}</textarea>
                    </div>
                    <x-input-error :messages="$errors->all()" />
                    <button class="btn btn-secondary btn-sm">Salvar</button>
                </form>
            @else
                <dl class="text-sm space-y-3">
                    <div>
                        <dt class="text-gray-500">Descrição das alterações desejadas</dt>
                        <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $alteracao->descricao }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Justificativa</dt>
                        <dd class="text-gray-900 mt-0.5 whitespace-pre-line">{{ $alteracao->justificativa }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        {{-- O que mudou no plano --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800">O que mudou no Plano de Trabalho</h2>
            <p class="text-xs text-gray-400 mt-0.5 mb-4">
                Comparado com o plano no momento em que o pedido foi aberto.
            </p>

            @if($mudancas)
                <table class="min-w-full text-sm">
                    <thead class="text-xs text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="text-left py-2">Item</th>
                            <th class="text-right py-2">Antes</th>
                            <th class="text-right py-2">Agora</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($mudancas as $m)
                            <tr>
                                <td class="py-2 text-gray-700">{{ $m['rotulo'] }}</td>
                                <td class="py-2 text-right text-gray-500">
                                    {{ ($m['contagem'] ?? false) ? $m['de'] : $dinheiro($m['de']) }}
                                </td>
                                <td class="py-2 text-right font-semibold text-gray-900">
                                    {{ ($m['contagem'] ?? false) ? $m['para'] : $dinheiro($m['para']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-400">
                    O plano ainda está como estava na abertura do pedido.
                    @if($podeEditarPlano) Ajuste-o abaixo. @endif
                </p>
            @endif
        </div>

        {{-- O Plano de Trabalho, editável enquanto o pedido é da OSC --}}
        @if($proposta)
            @include('plano._editor', [
                'dono'       => $proposta,
                'rota'       => 'portal.proposta.plano',
                'podeEditar' => $podeEditarPlano,
            ])
        @endif

        {{-- Checklist --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @include('pecas._cabecalho', [
                'titulo'    => 'Documentos da alteração',
                'descricao' => 'A Proposta de Alteração e as declarações são geradas do que foi preenchido — basta conferir e assinar. O resto é anexo.',
                'progresso' => $progresso,
            ])
            @include('pecas._checklist', ['pecas' => $pecas])
        </div>

        {{-- Trâmite --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Trâmite</h2></div>
            <div class="px-6 py-4 space-y-3">
                @forelse($alteracao->tramitacoes as $t)
                    <p class="text-sm text-gray-800">
                        <strong>{{ \App\Models\Alteracao::SETORES[$t->de_setor] ?? $t->de_setor }}</strong>
                        {{ $t->status === 'devolvido' ? 'devolveu para' : 'enviou para' }}
                        <strong>{{ \App\Models\Alteracao::SETORES[$t->para_setor] ?? $t->para_setor }}</strong>
                        <span class="block text-xs text-gray-400">
                            {{ $t->enviado_em->format('d/m/Y H:i') }} por {{ $t->remetente?->name ?? '—' }}
                            @if($t->parecer) · {{ $t->parecer }} @endif
                        </span>
                    </p>
                @empty
                    <p class="text-sm text-gray-400">Ainda sem movimentação — o pedido está sendo montado pela OSC.</p>
                @endforelse
            </div>

            @if($daVez)
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
                    @if($pendencias)
                        <p class="text-sm text-red-700">Falta concluir: <strong>{{ implode(', ', $pendencias) }}</strong>.</p>
                    @endif

                    @if($ultima)
                        {{-- Fim do fluxo: a SCP decide, e o motivo é o que a OSC lê. --}}
                        <form action="{{ route('alteracoes.decidir', $alteracao) }}" method="POST" class="space-y-2">
                            @csrf
                            <textarea name="motivo" rows="2" required placeholder="Conclusão — a OSC vai ler este texto"
                                      class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                            <div class="flex flex-wrap gap-2">
                                <button name="decisao" value="aprovada" @disabled($pendencias) class="btn btn-primary">
                                    Aprovar alteração
                                </button>
                                <button name="decisao" value="indeferida" class="btn btn-danger-outline">
                                    Indeferir
                                </button>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('alteracoes.avancar', $alteracao) }}" method="POST" class="space-y-2">
                            @csrf
                            <input name="parecer" placeholder="Observação (opcional)"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                            <button @disabled($pendencias) class="btn btn-primary">
                                Encaminhar para {{ \App\Models\Alteracao::SETORES[\App\Models\Alteracao::ETAPAS[$alteracao->etapa + 1]['setor']] }}
                            </button>
                        </form>
                    @endif

                    @if($alteracao->etapa > 0 && $alteracao->setor_atual !== 'osc')
                        <form action="{{ route('alteracoes.devolver', $alteracao) }}" method="POST" class="flex gap-2 pt-3 border-t border-gray-200">
                            @csrf
                            <input name="parecer" required placeholder="Motivo da devolução…"
                                   class="flex-1 border-gray-300 rounded-lg shadow-sm text-sm focus:ring-red-500 focus:border-red-500">
                            <button class="btn btn-secondary btn-sm !text-accent-800 !border-accent-300">Devolver</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
