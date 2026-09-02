@php
    use Illuminate\Support\Arr;

    $u = auth()->user();

    /**
     * Telas oferecidas pela barra de comandos. A coluna 'quando' repete as
     * mesmas permissões do menu lateral — a barra não pode virar uma porta dos
     * fundos para módulos que o usuário não enxerga. 'termos' são sinônimos e
     * erros de grafia comuns, para o usuário achar a tela pelo nome que usa no
     * dia a dia, não pelo nome oficial.
     */
    $atalhos = collect([
        ['Painel', route('dashboard'), 'inicio home dashboard visao geral', true],
        ['Portal Público', route('portal.index'), 'site publico cidadao chamamentos abertos', true],

        ['Planejamento — Processos', route('processos.index'), 'processo etapa 1 abertura', $u->can('planejamento')],
        ['Caixa de Entrada', route('processos.caixa'), 'recebidos pendentes meu setor tramitacao', $u->can('planejamento') && $u->setor],
        ['Novo Processo', route('processos.create'), 'abrir criar cadastrar processo', $u->can('planejamento')],

        ['Chamamentos', route('programas.index'), 'programas edital selecao etapa 2', $u->can('chamamentos')],
        ['Novo Programa', route('programas.create'), 'criar cadastrar programa', $u->can('chamamentos')],
        ['Propostas', route('propostas.index'), 'planos de trabalho osc etapa 2', $u->can('propostas')],

        ['Celebração — Instrumentos', route('instrumentos.index'), 'termo fomento colaboracao acordo convenio etapa 3', $u->can('formalizacao')],
        ['Execução', route('execucao.index'), 'repasses despesas prestacao saldo etapa 4', $u->can('execucao')],

        // Uma entrada só, com as palavras das duas telas que se juntaram: quem
        // procura "usuários" e quem procura "secretaria" chega ao mesmo lugar.
        ['Órgãos e usuários', route('orgaos.index'), 'usuarios servidores contas acesso secretaria orgao unidade gestora', $u->can('cadastros')],
        ['Novo Usuário', route('usuarios.create'), 'criar cadastrar servidor', $u->can('cadastros')],
        ['Aprovações pendentes', route('usuarios.pendentes'), 'aprovar cadastros novos', $u->can('cadastros')],
        ['OSCs', route('oscs.index'), 'entidades organizacoes sociedade civil', $u->can('cadastros')],
        ['Nova OSC', route('oscs.create'), 'criar cadastrar entidade organizacao', $u->can('cadastros')],

        ['Meus usuários', route('subusuarios.index'), 'equipe subusuarios da unidade', $u->hasRole('responsavel_unidade_gestora')],
        ['Modelos padrão', route('modelos.index'), 'templates documentos ti', $u->hasRole('administrador_setorial')],

        ['Meu perfil', route('profile.edit'), 'conta senha dados pessoais', true],
        ['Transparência pública', route('transparencia'), 'dados abertos prestacao publicidade', true],
        ['Validar documento', route('validacao.index'), 'autenticidade assinatura codigo verificador', true],
    ])
        ->filter(fn ($a) => $a[3])
        ->map(fn ($a) => ['titulo' => $a[0], 'url' => $a[1], 'termos' => $a[2]])
        ->values();
@endphp

<div x-data="paletaComandos(@js($atalhos), '{{ route('busca') }}')"
     x-cloak
     @abrir-paleta.window="abrir()"
     @keydown.escape.window="fechar()">

    <div x-show="aberto" x-transition.opacity.duration.120ms
         @click="fechar()"
         class="fixed inset-0 z-50 bg-gray-900/50 flex items-start justify-center p-4 pt-[12vh]"
         role="dialog" aria-modal="true" aria-label="Buscar no sistema">

        <div @click.stop
             x-transition:enter="transition duration-150 ease-out"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-[.98]"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="w-full max-w-xl bg-white rounded-xl shadow-2xl ring-1 ring-black/5 overflow-hidden">

            {{-- Campo --}}
            <div class="flex items-center gap-3 px-4 border-b border-gray-100">
                <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input x-ref="campo" x-model="termo" type="text" autocomplete="off" spellcheck="false"
                       placeholder="Buscar telas, processos, propostas, OSCs…"
                       class="flex-1 border-0 py-4 text-base text-gray-900 placeholder-gray-400 focus:ring-0 bg-transparent"
                       @keydown.arrow-down.prevent="mover(1)"
                       @keydown.arrow-up.prevent="mover(-1)"
                       @keydown.enter.prevent="escolher()"
                       @keydown.tab.prevent="mover($event.shiftKey ? -1 : 1)">

                {{-- Ocupa o mesmo espaço do "esc" para o campo não mudar de largura ao carregar --}}
                <span class="w-9 flex justify-end shrink-0">
                    <svg x-show="carregando" class="w-4 h-4 text-brand-600 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                    </svg>
                    <kbd x-show="!carregando" class="px-1.5 py-0.5 text-[11px] font-sans font-semibold text-gray-400 bg-gray-100 border border-gray-200 rounded">esc</kbd>
                </span>
            </div>

            {{-- Resultados --}}
            <div x-ref="lista" class="max-h-[52vh] overflow-y-auto overscroll-contain py-2">
                <template x-for="(grupo, gi) in grupos" :key="grupo.rotulo">
                    <div class="mb-1">
                        <p class="px-4 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400"
                           x-text="grupo.rotulo"></p>

                        <template x-for="(item, ii) in grupo.itens" :key="item.url + ii">
                            <a :href="item.url"
                               :data-ativo="posicao(gi, ii) === indice"
                               @mouseenter="indice = posicao(gi, ii)"
                               class="flex items-center gap-3 px-4 py-2.5 cursor-pointer
                                      data-[ativo=true]:bg-brand-50 group">

                                <span class="w-8 h-8 shrink-0 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center
                                             group-data-[ativo=true]:bg-brand-600 group-data-[ativo=true]:text-white transition-colors">
                                    <template x-if="grupo.icone === 'relogio'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </template>
                                    <template x-if="grupo.icone === 'tela'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM9 20h6"/>
                                        </svg>
                                    </template>
                                    <template x-if="grupo.icone === 'osc'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0A5 5 0 0012 13a5 5 0 00-4.64 3.14M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </template>
                                    <template x-if="['processo','proposta','chamamento','programa','instrumento'].includes(grupo.icone)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 truncate" x-text="item.titulo"></span>
                                    <span x-show="item.subtitulo"
                                          class="block text-xs text-gray-500 truncate" x-text="item.subtitulo"></span>
                                </span>

                                <svg class="w-4 h-4 shrink-0 text-brand-600 opacity-0 group-data-[ativo=true]:opacity-100"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </template>
                    </div>
                </template>

                <div x-show="vazio" class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-500">Nada encontrado para <span class="font-semibold text-gray-700" x-text="termo"></span>.</p>
                    <p class="text-xs text-gray-400 mt-1">Tente o número do processo, o nome da OSC ou o título da proposta.</p>
                </div>
            </div>

            {{-- Rodapé com as teclas: ensina o atalho sem precisar de tutorial --}}
            <div class="flex items-center gap-4 px-4 py-2.5 border-t border-gray-100 bg-gray-50 text-[11px] text-gray-500">
                <span class="flex items-center gap-1"><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> navegar</span>
                <span class="flex items-center gap-1"><kbd class="kbd">enter</kbd> abrir</span>
                <span class="ml-auto flex items-center gap-1"><kbd class="kbd">ctrl</kbd><kbd class="kbd">k</kbd> a qualquer momento</span>
            </div>
        </div>
    </div>
</div>
