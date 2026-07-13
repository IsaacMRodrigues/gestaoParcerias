// Editor rico (TinyMCE, self-hosted) para os "documentos modelo".
// Uso no Blade: <textarea name="conteudo" data-editor-rico>{!! $html !!}</textarea>

import tinymce from 'tinymce';

// Núcleo: ícones, tema, modelo de conteúdo
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

// Skin da interface (CSS empacotado)
import 'tinymce/skins/ui/oxide/skin.min.css';

// Plugins
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/autoresize';

// CSS aplicado dentro do editor (como string)
import contentCss from 'tinymce/skins/content/default/content.min.css?raw';

function iniciarEditores(root = document) {
    const alvos = root.querySelectorAll('textarea[data-editor-rico]');
    if (!alvos.length) return;

    // Evita reinicializar textareas que já têm instância TinyMCE
    const seletor = Array.from(alvos)
        .filter((el) => !tinymce.get(el.id) && !el.closest('.tox-tinymce'))
        .map((el, i) => {
            if (!el.id) el.id = `editor-rico-${Date.now()}-${i}`;
            return `#${CSS.escape(el.id)}`;
        })
        .join(',');

    if (!seletor) return;

    tinymce.init({
        selector: seletor,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        menubar: false,
        height: 520,
        plugins: 'lists link table autoresize',
        toolbar:
            'undo redo | blocks fontfamily fontsize | bold italic underline strike | ' +
            'forecolor backcolor | alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | table | link | removeformat',
        skin: false,
        content_css: false,
        content_style:
            contentCss +
            '\n body{font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;padding:16px;}' +
            ' table{border-collapse:collapse;width:100%;} ' +
            ' th,td{border:1px solid #94a3b8;padding:4px 8px;} th{background:#f1f5f9;} ' +
            ' img{max-width:120px;height:auto;}',
        setup(editor) {
            editor.on('change', () => editor.save());
        },
    });
}

function prepararFormularios() {
    document.querySelectorAll('form').forEach((form) => {
        if (form.dataset.tinymceBound) return;
        form.dataset.tinymceBound = '1';
        form.addEventListener('submit', () => tinymce.triggerSave());
    });
}

function iniciar() {
    iniciarEditores();
    prepararFormularios();

    // Peças da Seleção ficam em <details>: reinicia o editor ao abrir
    document.querySelectorAll('details').forEach((detalhe) => {
        detalhe.addEventListener('toggle', () => {
            if (detalhe.open) {
                iniciarEditores(detalhe);
                prepararFormularios();
            }
        });
    });
}

if (document.readyState !== 'loading') {
    iniciar();
} else {
    document.addEventListener('DOMContentLoaded', iniciar);
}
