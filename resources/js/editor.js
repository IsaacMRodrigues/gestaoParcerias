import Quill from 'quill';

/**
 * Editor rico (Quill) para os "documentos modelo".
 *
 * Uso no Blade:
 *   <input type="hidden" name="conteudo" id="conteudo">
 *   <div data-editor-rico data-target="conteudo">{!! $html !!}</div>
 *
 * O HTML editado é sincronizado para o input alvo antes do submit.
 */
function iniciarEditores() {
    document.querySelectorAll('[data-editor-rico]').forEach((el) => {
        if (el.dataset.quillPronto) return;
        el.dataset.quillPronto = '1';

        const targetId = el.dataset.target;
        const target = targetId ? document.getElementById(targetId) : null;

        const quill = new Quill(el, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ font: [] }, { size: [] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ script: 'sub' }, { script: 'super' }],
                    [{ header: 1 }, { header: 2 }, 'blockquote'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ align: [] }],
                    ['link', 'clean'],
                ],
            },
        });

        // sincroniza o HTML para o input no submit
        const form = el.closest('form');
        if (form && target) {
            form.addEventListener('submit', () => {
                target.value = quill.root.innerHTML;
            });
        }
    });
}

if (document.readyState !== 'loading') {
    iniciarEditores();
} else {
    document.addEventListener('DOMContentLoaded', iniciarEditores);
}
