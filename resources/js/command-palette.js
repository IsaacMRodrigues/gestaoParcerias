/**
 * Barra de comandos (Ctrl+K / Cmd+K, ou "/").
 *
 * Encurta o caminho até qualquer tela ou registro: em vez de
 * menu → listagem → filtro → paginação, o usuário digita o que procura.
 *
 * Duas fontes de resultado:
 *  - Telas: lista fixa montada no Blade, já filtrada pelas permissões do usuário.
 *  - Registros: /busca, consultado com atraso de digitação. O servidor aplica de
 *    novo as permissões e os escopos de visibilidade — a barra nunca mostra o
 *    que a listagem esconderia.
 *
 * Com o campo vazio, oferece as telas visitadas há pouco (localStorage), que é o
 * que quase sempre se quer reabrir.
 */

const CHAVE_RECENTES = 'pgp:paleta:recentes';
const MAX_RECENTES = 6;
const MAX_TELAS = 5;
const ATRASO_DIGITACAO = 220;

/** Sem acento e em minúsculas: "execução" tem de casar com "execucao". */
function normalizar(texto) {
    return (texto || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function lerRecentes() {
    try {
        const bruto = JSON.parse(localStorage.getItem(CHAVE_RECENTES) || '[]');
        return Array.isArray(bruto) ? bruto.filter((i) => i && i.titulo && i.url) : [];
    } catch {
        // localStorage indisponível (modo privado) ou JSON corrompido: sem recentes.
        return [];
    }
}

/**
 * Nome da tela atual. O <title> é genérico na maioria das páginas, então vale
 * mais o título que o próprio cabeçalho já mostra ao usuário.
 */
function nomeDaPaginaAtual() {
    const doCabecalho = document.querySelector('header h1, header h2')?.textContent;
    const nome = (doCabecalho || '').replace(/\s+/g, ' ').trim();
    return nome.length > 1 && nome.length < 80 ? nome : null;
}

function registrarVisita(atalhos) {
    const url = window.location.pathname + window.location.search;

    // Quando a tela está na lista de atalhos, vale o nome oficial dela: o
    // cabeçalho do Painel, por exemplo, cumprimenta pelo nome do usuário
    // ("Olá, Isaac"), que não serve como item de navegação.
    const atalho = atalhos.find((a) => new URL(a.url, window.location.origin).pathname === window.location.pathname);
    const titulo = atalho ? atalho.titulo : nomeDaPaginaAtual();
    if (!titulo) return;

    const lista = lerRecentes().filter((i) => i.url !== url);
    lista.unshift({ titulo, url, subtitulo: 'Visitada recentemente' });

    try {
        localStorage.setItem(CHAVE_RECENTES, JSON.stringify(lista.slice(0, MAX_RECENTES)));
    } catch {
        // Cota estourada: perder o histórico é aceitável, quebrar a página não.
    }
}

export default function paletaComandos(atalhos, urlBusca) {
    return {
        aberto: false,
        termo: '',
        gruposRemotos: [],
        carregando: false,
        indice: 0,
        _timer: null,
        _requisicao: null,

        init() {
            registrarVisita(atalhos);

            window.addEventListener('keydown', (e) => this.teclaGlobal(e));

            // Reconsulta o servidor a cada tecla, com atraso, e devolve o
            // destaque ao primeiro resultado.
            this.$watch('termo', () => {
                this.indice = 0;
                this.buscarComAtraso();
            });
        },

        // ----- abertura e fechamento -----

        teclaGlobal(e) {
            const tecla = (e.key || '').toLowerCase();

            if ((e.ctrlKey || e.metaKey) && tecla === 'k') {
                e.preventDefault();
                this.aberto ? this.fechar() : this.abrir();
                return;
            }

            // "/" é atalho só fora de campos de texto, senão impede a digitação.
            if (tecla === '/' && !this.aberto && !this.editando(e.target)) {
                e.preventDefault();
                this.abrir();
            }
        },

        editando(alvo) {
            if (!alvo) return false;
            return alvo.isContentEditable
                || ['INPUT', 'TEXTAREA', 'SELECT'].includes(alvo.tagName);
        },

        abrir() {
            this.aberto = true;
            this.termo = '';
            this.gruposRemotos = [];
            this.indice = 0;
            this.$nextTick(() => this.$refs.campo?.focus());
        },

        fechar() {
            this.aberto = false;
            this._requisicao?.abort();
            this.carregando = false;
        },

        // ----- resultados -----

        buscarComAtraso() {
            clearTimeout(this._timer);
            this._requisicao?.abort();

            const termo = this.termo.trim();
            if (termo.length < 2) {
                this.gruposRemotos = [];
                this.carregando = false;
                return;
            }

            this.carregando = true;
            this._timer = setTimeout(() => this.buscar(termo), ATRASO_DIGITACAO);
        },

        async buscar(termo) {
            const controlador = new AbortController();
            this._requisicao = controlador;

            try {
                const resposta = await fetch(`${urlBusca}?q=${encodeURIComponent(termo)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controlador.signal,
                });
                if (!resposta.ok) throw new Error(resposta.status);

                const dados = await resposta.json();
                // Uma resposta lenta não pode sobrescrever uma busca mais nova.
                if (this._requisicao !== controlador) return;

                this.gruposRemotos = dados.grupos || [];
            } catch (erro) {
                if (erro.name !== 'AbortError') this.gruposRemotos = [];
            } finally {
                if (this._requisicao === controlador) this.carregando = false;
            }
        },

        get grupos() {
            const termo = this.termo.trim();

            if (!termo) {
                const recentes = lerRecentes();
                // Uma tela recém-visitada não precisa aparecer duas vezes na
                // mesma lista: sai de "Ir para" e fica só no topo.
                const jaListadas = new Set(recentes.map((i) => i.url));
                const restantes = atalhos.filter((a) => !jaListadas.has(new URL(a.url, window.location.origin).pathname));

                return [
                    recentes.length && { rotulo: 'Visitadas recentemente', icone: 'relogio', itens: recentes },
                    restantes.length && { rotulo: 'Ir para', icone: 'tela', itens: restantes },
                ].filter(Boolean);
            }

            return [
                (() => {
                    const telas = this.telasQueCasam(termo);
                    return telas.length && { rotulo: 'Telas', icone: 'tela', itens: telas };
                })(),
                ...this.gruposRemotos,
            ].filter(Boolean);
        },

        /**
         * Telas que casam com o termo, das mais prováveis para as menos.
         *
         * Substring pura é frouxa demais: com duas letras, "te" achava seis
         * telas (por "pendenTEs", "templates"…) e empurrava os registros para
         * fora da tela. Então vale primeiro o começo de palavra — como se
         * digita quando se sabe o que quer — e só na ausência disso o
         * substring, que ainda salva quem lembra do meio do nome.
         */
        telasQueCasam(termo) {
            const alvo = normalizar(termo);
            const texto = (a) => normalizar(`${a.titulo} ${a.termos || ''}`);

            const porInicio = atalhos.filter((a) => texto(a).split(/\s+/).some((p) => p.startsWith(alvo)));
            const escolhidas = porInicio.length ? porInicio : atalhos.filter((a) => texto(a).includes(alvo));

            // Teto baixo: passando disto, quem responde melhor é o próprio termo.
            return escolhidas.slice(0, MAX_TELAS);
        },

        /** Lista achatada — é sobre ela que as setas do teclado andam. */
        get itens() {
            return this.grupos.flatMap((g) => g.itens);
        },

        get vazio() {
            return this.termo.trim().length >= 2 && !this.carregando && this.itens.length === 0;
        },

        /** Posição do item dentro da lista achatada, para saber quem está ativo. */
        posicao(grupoIndice, itemIndice) {
            let n = 0;
            for (let g = 0; g < grupoIndice; g++) n += this.grupos[g].itens.length;
            return n + itemIndice;
        },

        // ----- navegação por teclado -----

        mover(passo) {
            const total = this.itens.length;
            if (!total) return;

            // Dá a volta: de baixo para o topo e vice-versa.
            this.indice = (this.indice + passo + total) % total;
            this.$nextTick(() => {
                this.$refs.lista?.querySelector('[data-ativo="true"]')
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },

        escolher() {
            const item = this.itens[this.indice];
            if (item) this.ir(item);
        },

        ir(item) {
            window.location.href = item.url;
        },
    };
}
