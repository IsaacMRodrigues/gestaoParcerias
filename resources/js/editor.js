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

function iniciarEditores() {
    if (!document.querySelector('textarea[data-editor-rico]')) return;

    tinymce.init({
        selector: 'textarea[data-editor-rico]',
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
        skin: false,        // skin já importado acima
        content_css: false, // usamos content_style abaixo
        content_style:
            contentCss +
            '\n body{font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;padding:16px;}' +
            ' table{border-collapse:collapse;width:100%;} ' +
            ' th,td{border:1px solid #94a3b8;padding:4px 8px;} th{background:#f1f5f9;} ' +
            ' img{max-width:120px;height:auto;}',
        // garante que o HTML seja gravado no textarea antes do submit
        setup(editor) {
            editor.on('change', () => editor.save());
        },
    });

    // sincroniza todos os editores ao enviar qualquer formulário
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => tinymce.triggerSave());
    });
}

if (document.readyState !== 'loading') {
    iniciarEditores();
} else {
    document.addEventListener('DOMContentLoaded', iniciarEditores);
}
