/**
 * Barra de progresso da navegação.
 *
 * O sistema recarrega a página inteira a cada clique. Entre o clique e a
 * resposta do servidor nada muda na tela, e o usuário fica sem saber se o
 * clique foi registrado — costuma clicar de novo. A barra fina no topo responde
 * na hora e é o único sinal necessário.
 *
 * Não há como saber o progresso real de uma navegação, então a barra avança em
 * passos cada vez menores e nunca chega sozinha ao fim: quem a completa é a
 * página nova, ao substituir esta.
 */

const ELEMENTO_ID = 'nav-progresso';
const TETO = 92;

let barra = null;
let timer = null;

function criar() {
    if (barra) return barra;

    barra = document.createElement('div');
    barra.id = ELEMENTO_ID;
    barra.setAttribute('aria-hidden', 'true');
    document.body.appendChild(barra);
    return barra;
}

function iniciar() {
    if (timer) return; // Navegação já em curso: não reinicia do zero.

    const elemento = criar();
    let largura = 0;

    elemento.classList.add('is-ativo');
    elemento.style.width = '0%';

    timer = setInterval(() => {
        // Quanto mais perto do teto, menor o passo — dá a sensação de espera
        // sem nunca sugerir que terminou.
        largura += Math.max(0.4, (TETO - largura) / 12);
        elemento.style.width = `${Math.min(largura, TETO)}%`;
    }, 120);
}

function parar() {
    clearInterval(timer);
    timer = null;
    if (!barra) return;

    barra.style.width = '100%';
    setTimeout(() => {
        barra.classList.remove('is-ativo');
        barra.style.width = '0%';
    }, 220);
}

/** Este clique realmente troca de página? */
function ehNavegacao(evento, link) {
    if (evento.defaultPrevented) return false;
    // Cliques com modificador abrem em outra aba: esta página continua onde está.
    if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) return false;
    if (evento.button !== 0) return false;

    const href = link.getAttribute('href') || '';
    if (!href || href.startsWith('#')) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download')) return false;
    // O modal de confirmação decide depois se a navegação acontece.
    if (link.hasAttribute('data-confirm')) return false;
    if (/^(mailto|tel|javascript):/i.test(href)) return false;

    const destino = new URL(href, window.location.href);
    if (destino.origin !== window.location.origin) return false;
    // Só muda a âncora: a página não recarrega.
    if (destino.pathname === window.location.pathname
        && destino.search === window.location.search
        && destino.hash) return false;

    return true;
}

document.addEventListener('click', (evento) => {
    const link = evento.target.closest('a[href]');
    if (link && ehNavegacao(evento, link)) iniciar();
});

document.addEventListener('submit', (evento) => {
    const form = evento.target;
    if (evento.defaultPrevented) return;
    if (form.hasAttribute('data-confirm')) return;
    // Downloads e impressões não trocam a página: a barra ficaria presa.
    if (form.target && form.target !== '_self') return;
    iniciar();
});

// Voltar pelo histórico devolve a página do cache com a barra congelada no meio.
window.addEventListener('pageshow', (evento) => {
    if (evento.persisted) parar();
});
