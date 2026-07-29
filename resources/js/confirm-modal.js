/**
 * Modal de confirmação global — substitui os confirm()/alert() nativos do navegador.
 *
 * Uso no Blade (sem JS na página):
 *   <form ... data-confirm="Remover este item?">                 // pergunta antes de enviar
 *   <form ... data-confirm="..." data-confirm-variant="danger">  // botão vermelho + ícone de alerta
 *   <form ... data-confirm="..." data-confirm-title="..." data-confirm-text="Remover">
 *   <a href="..." data-confirm="...">                            // idem para links
 *   <form ... data-require-checked="pecas[]"                     // exige ao menos 1 checkbox marcado
 *              data-require-checked-message="Selecione ao menos um.">
 *
 * O variante "danger" é inferido automaticamente por palavras como "Remover",
 * "Excluir", "Recusar", "Rejeitar" quando data-confirm-variant não é informado.
 */

const confirmed = new WeakSet();
let refs = null;
let onConfirm = null;
let lastFocus = null;

const ICONS = {
    danger: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    question: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
};

const DANGER_HINTS = /remover|excluir|recusar|rejeitar|apagar|deletar|cancelar cadastro/i;

function build() {
    const root = document.createElement('div');
    root.className = 'cmodal-backdrop';
    root.setAttribute('hidden', '');
    root.innerHTML = `
        <div class="cmodal" role="dialog" aria-modal="true" aria-labelledby="cmodal-title" aria-describedby="cmodal-msg">
            <div class="cmodal-body">
                <div class="cmodal-icon" data-icon></div>
                <div class="cmodal-text">
                    <h3 class="cmodal-title" id="cmodal-title" data-title></h3>
                    <p class="cmodal-message" id="cmodal-msg" data-message></p>
                </div>
            </div>
            <div class="cmodal-actions" data-actions>
                <button type="button" class="cmodal-btn cmodal-btn-cancel" data-cancel>Cancelar</button>
                <button type="button" class="cmodal-btn cmodal-btn-confirm" data-ok>Confirmar</button>
            </div>
        </div>`;
    document.body.appendChild(root);

    refs = {
        root,
        dialog: root.querySelector('.cmodal'),
        icon: root.querySelector('[data-icon]'),
        title: root.querySelector('[data-title]'),
        message: root.querySelector('[data-message]'),
        cancel: root.querySelector('[data-cancel]'),
        ok: root.querySelector('[data-ok]'),
    };

    refs.cancel.addEventListener('click', close);
    refs.ok.addEventListener('click', () => {
        const fn = onConfirm;
        close();
        if (fn) fn();
    });
    root.addEventListener('mousedown', (e) => { if (e.target === root) close(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !root.hasAttribute('hidden')) close();
    });
}

function close() {
    onConfirm = null;
    if (!refs) return;
    refs.root.setAttribute('hidden', '');
    refs.root.classList.remove('is-open');
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
}

function open({ title, message, confirmText, cancelText, variant, alertOnly, onConfirm: cb }) {
    if (!refs) build();

    const isDanger = variant === 'danger';
    refs.dialog.dataset.variant = variant || 'default';
    refs.icon.innerHTML = alertOnly ? ICONS.info : (isDanger ? ICONS.danger : ICONS.question);

    refs.title.textContent = title || (alertOnly ? 'Atenção' : 'Confirmar ação');
    refs.title.hidden = !refs.title.textContent;
    refs.message.textContent = message || '';
    refs.message.hidden = !message;

    refs.ok.textContent = confirmText || (alertOnly ? 'Entendi' : 'Confirmar');
    refs.cancel.hidden = !!alertOnly;
    refs.cancel.textContent = cancelText || 'Cancelar';

    onConfirm = cb || null;
    lastFocus = document.activeElement;

    refs.root.removeAttribute('hidden');
    // reflow para animar
    void refs.root.offsetWidth;
    refs.root.classList.add('is-open');
    (alertOnly ? refs.ok : refs.ok).focus();
}

// API pública opcional
window.confirmModal = (opts) => open(opts);
window.alertModal = (message, opts = {}) => open({ ...opts, message, alertOnly: true });

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    // já confirmado: deixa passar (e limpa a marca)
    if (confirmed.has(form)) { confirmed.delete(form); return; }

    // validação: exige ao menos um checkbox marcado
    const reqName = form.dataset.requireChecked;
    if (reqName) {
        const anyChecked = form.querySelector(`input[name="${CSS.escape(reqName)}"]:checked`);
        if (!anyChecked) {
            e.preventDefault();
            open({ alertOnly: true, message: form.dataset.requireCheckedMessage || 'Selecione ao menos uma opção.' });
            return;
        }
    }

    // confirmação
    const message = form.dataset.confirm;
    if (message) {
        e.preventDefault();
        open({
            message,
            title: form.dataset.confirmTitle,
            confirmText: form.dataset.confirmText,
            variant: form.dataset.confirmVariant || (DANGER_HINTS.test(message) ? 'danger' : 'default'),
            onConfirm: () => { confirmed.add(form); form.requestSubmit(); },
        });
    }
}, true);

// Links com data-confirm
document.addEventListener('click', (e) => {
    const link = e.target.closest && e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    const message = link.dataset.confirm;
    open({
        message,
        title: link.dataset.confirmTitle,
        confirmText: link.dataset.confirmText,
        variant: link.dataset.confirmVariant || (DANGER_HINTS.test(message) ? 'danger' : 'default'),
        onConfirm: () => { window.location.href = link.href; },
    });
}, true);

if (document.readyState !== 'loading') build();
else document.addEventListener('DOMContentLoaded', build);
