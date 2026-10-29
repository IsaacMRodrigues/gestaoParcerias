{{-- Ferramentas de acessibilidade, como no Agiliza (e-MAG): menu flutuante (tamanho do texto, alto contraste, ajuda,
     mapa do sítio), atalhos de teclado de 0 a 7 e o tradutor VLibras. As preferências ficam no navegador
     (pgp:a11y:fonte, pgp:a11y:contraste); o CSS está em public/css/acessibilidade.css. --}}
<div class="a11y-menu" data-a11y-raiz>
    <div class="a11y-painel" id="a11y-painel" role="region" aria-label="Ferramentas de acessibilidade" hidden>
        <p>Acessibilidade</p>
        <div class="a11y-grade">
            <button type="button" data-a11y="fonte-menos" aria-label="Diminuir o tamanho do texto (atalho 3)">A−</button>
            <button type="button" data-a11y="fonte-mais" aria-label="Aumentar o tamanho do texto (atalho 2)">A+</button>
            <button type="button" data-a11y="contraste-mais" aria-pressed="false" aria-label="Ativar o alto contraste (atalho 4)">Contraste +</button>
            <button type="button" data-a11y="contraste-menos" aria-label="Desativar o alto contraste (atalho 5)">Contraste −</button>
        </div>
        <div class="a11y-nivel" data-a11y-nivel aria-live="polite">Texto em 100%</div>
        <div class="a11y-links">
            <a href="{{ route('acessibilidade') }}">Ajuda e atalhos</a>
            <a href="{{ route('mapa-do-sitio') }}">Mapa do sítio</a>
        </div>
    </div>
    <button type="button" class="a11y-botao" data-a11y-abrir aria-expanded="false" aria-controls="a11y-painel"
            aria-label="Abrir as ferramentas de acessibilidade">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><circle cx="12" cy="6.8" r="1.4" fill="currentColor"/>
            <path d="M7 9.8l5 1.2 5-1.2M12 11v3.6m0 0l-2.6 4.2M12 14.6l2.6 4.2"/>
        </svg>
        <span>Acessibilidade</span>
    </button>
</div>

{{-- VLibras: tradutor de português para Libras, do Governo Federal. --}}
<div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div>
</div>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js" defer></script>
<script>
    window.addEventListener('load', function () {
        if (window.VLibras) new window.VLibras.Widget('https://vlibras.gov.br/app');
    });

    (function () {
        var FONTE = 'pgp:a11y:fonte', CONTRASTE = 'pgp:a11y:contraste', PASSOS = [1, 1.1, 1.2, 1.3];
        var AJUDA = @json(route('acessibilidade')), MAPA = @json(route('mapa-do-sitio'));
        var raiz = document.querySelector('[data-a11y-raiz]');
        var painel = document.getElementById('a11y-painel');
        var abrir = raiz.querySelector('[data-a11y-abrir]');

        function ler(chave) { try { return localStorage.getItem(chave); } catch (e) { return null; } }
        function gravar(chave, valor) { try { localStorage.setItem(chave, valor); } catch (e) {} }
        function passo() {
            var n = parseInt(ler(FONTE) || '0', 10);
            return isFinite(n) ? Math.max(0, Math.min(PASSOS.length - 1, n)) : 0;
        }
        function contrasteLigado() { return document.documentElement.classList.contains('a11y-contraste'); }

        function atualizar() {
            raiz.querySelector('[data-a11y-nivel]').textContent = 'Texto em ' + Math.round(PASSOS[passo()] * 100) + '%'
                + (contrasteLigado() ? ' · alto contraste ligado' : '');
            raiz.querySelector('[data-a11y="contraste-mais"]').setAttribute('aria-pressed', contrasteLigado() ? 'true' : 'false');
        }
        function fonte(novo) {
            novo = Math.max(0, Math.min(PASSOS.length - 1, novo));
            gravar(FONTE, String(novo));
            document.documentElement.style.fontSize = novo ? (16 * PASSOS[novo]) + 'px' : '';
            atualizar();
        }
        function contraste(ligar) {
            gravar(CONTRASTE, ligar ? '1' : '0');
            document.documentElement.classList.toggle('a11y-contraste', ligar);
            atualizar();
        }
        function mostrarPainel(sim) {
            painel.hidden = !sim;
            abrir.setAttribute('aria-expanded', sim ? 'true' : 'false');
        }
        function visivel(el) { return el && el.getClientRects().length > 0; }
        function focarMenu() {
            var menus = document.querySelectorAll('[data-a11y-menu]');
            for (var i = 0; i < menus.length; i++) {
                var link = menus[i].querySelector('a, button');
                if (visivel(link)) { link.focus(); return; }
            }
        }
        function focarConteudo() {
            var alvo = document.querySelector('[data-a11y-conteudo]');
            if (alvo) { alvo.focus(); alvo.scrollIntoView({ block: 'start' }); }
        }
        function digitando(el) {
            var tag = (el && el.tagName || '').toLowerCase();
            return tag === 'input' || tag === 'textarea' || tag === 'select' || (el && el.isContentEditable);
        }

        abrir.addEventListener('click', function () { mostrarPainel(painel.hidden); });
        raiz.querySelectorAll('[data-a11y]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var acao = botao.getAttribute('data-a11y');
                if (acao === 'fonte-mais') fonte(passo() + 1);
                if (acao === 'fonte-menos') fonte(passo() - 1);
                if (acao === 'contraste-mais') contraste(true);
                if (acao === 'contraste-menos') contraste(false);
            });
        });
        document.addEventListener('click', function (e) { if (!painel.hidden && !raiz.contains(e.target)) mostrarPainel(false); });

        // Atalhos do e-MAG, como no Agiliza: 0 menu, 1 conteúdo, 2/3 texto, 4/5 contraste, 6 ajuda, 7 mapa do sítio.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !painel.hidden) { mostrarPainel(false); abrir.focus(); return; }
            if (e.altKey || e.ctrlKey || e.metaKey || digitando(e.target) || !/^[0-7]$/.test(e.key)) return;
            e.preventDefault();
            ({ '0': focarMenu, '1': focarConteudo,
               '2': function () { fonte(passo() + 1); }, '3': function () { fonte(passo() - 1); },
               '4': function () { contraste(true); }, '5': function () { contraste(false); },
               '6': function () { window.location.href = AJUDA; }, '7': function () { window.location.href = MAPA; } })[e.key]();
        });

        atualizar();
    })();
</script>
