/**
 * Máscara dos campos de dinheiro.
 *
 * O padrão `data-money` nasceu dentro de uma única view (a de participar do
 * chamamento) e não valia em lugar nenhum além dela — pior: lá o script
 * existia, mas em qualquer outra tela o campo oculto ficaria sem sincronizar.
 * Aqui o comportamento é do sistema.
 *
 * A digitação é da direita para a esquerda, como em caixa eletrônico: os
 * dígitos entram como centavos e o separador aparece sozinho.
 */
const formatarBRL = (centavos) =>
    (centavos / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function sincronizar(display) {
    const oculto = document.getElementById(display.dataset.money);
    if (!oculto) return;

    const digitos = display.value.replace(/\D/g, '');

    if (!digitos) {
        display.value = '';
        oculto.value = '';
        return;
    }

    // Limite do banco: decimal(15,2) — 13 dígitos inteiros mais os centavos.
    const centavos = parseInt(digitos.slice(0, 15), 10);

    display.value = formatarBRL(centavos);
    oculto.value = (centavos / 100).toFixed(2);
}

// Delegação no documento: pega também os campos que aparecem depois — dentro de
// um <details> que abre, de um bloco repetido, de um modal.
document.addEventListener('input', (evento) => {
    if (evento.target.matches?.('[data-money]')) {
        sincronizar(evento.target);
    }
});

// Alinha o oculto ao que já veio preenchido (edição, `old()` de uma validação).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-money]').forEach((display) => {
        if (display.value) sincronizar(display);
    });
});
