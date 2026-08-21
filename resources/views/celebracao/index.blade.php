<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Celebração</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Parcerias aprovadas em formalização — as quinze etapas do trâmite, de ponta a ponta.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden">
                @forelse($propostas as $proposta)
                    @php
                        $concluida = $proposta->celebracaoConcluida();
                        // "É a sua vez" só faz sentido para quem tem lotação: é o
                        // que diferencia acompanhar de ter trabalho parado.
                        $minhaVez = !$concluida
                            && $proposta->celebracaoIniciada()
                            && $proposta->celebracao_setor === auth()->user()->setorNoTramite();
                        $etapa = $proposta->etapaCelebracaoInfo();
                    @endphp
                    <a href="{{ route('celebracao.show', $proposta) }}"
                       class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition group">

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand-800">
                                {{ $proposta->titulo }}
                                @if($minhaVez)
                                    <span class="ml-1.5 align-middle px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide
                                                 bg-accent-50 text-accent-800 ring-1 ring-accent-200 rounded">
                                        Com o seu setor
                                    </span>
                                @endif
                            </span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                {{ collect([
                                    $proposta->osc?->name,
                                    $proposta->chamamento?->programa?->orgao?->sigla,
                                ])->filter()->implode(' · ') }}
                            </span>
                            <span class="block text-xs text-gray-600 mt-1">
                                @if($concluida)
                                    Celebração concluída em {{ $proposta->celebracao_concluida_em->format('d/m/Y') }}
                                @elseif(!$proposta->celebracaoIniciada())
                                    Aguardando o início do trâmite pela Unidade Gestora
                                @else
                                    Etapa {{ $proposta->celebracao_etapa + 1 }}/{{ $proposta->totalEtapasCelebracao() }}
                                    — {{ $etapa['acao'] }}
                                    <span class="text-gray-400">
                                        ({{ \App\Models\Proposta::SETORES_CELEBRACAO[$proposta->celebracao_setor] ?? $proposta->celebracao_setor }})
                                    </span>
                                @endif
                            </span>
                        </span>

                        <span class="shrink-0 text-right">
                            @if($concluida)
                                <span class="px-2 py-0.5 text-[11px] font-semibold bg-brand-50 text-brand-800 ring-1 ring-brand-200 rounded">
                                    Concluída
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 ring-1 ring-slate-200 rounded">
                                    Em andamento
                                </span>
                            @endif
                            <span class="block text-sm font-semibold text-brand-700 mt-1.5">Abrir →</span>
                        </span>
                    </a>
                @empty
                    <div class="px-6 py-14">
                        <x-empty-state icone="pasta">
                            Nenhuma parceria em Celebração — o trâmite começa quando uma proposta é aprovada.
                        </x-empty-state>
                    </div>
                @endforelse
            </div>

            {{ $propostas->links() }}
        </div>
    </div>
</x-app-layout>
