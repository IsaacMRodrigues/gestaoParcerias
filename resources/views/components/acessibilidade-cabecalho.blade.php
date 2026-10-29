{{-- No <head>: o CSS das ferramentas de acessibilidade e as preferências guardadas (tamanho do texto, alto contraste)
     aplicadas antes de a página aparecer, sem piscar. Ver components/acessibilidade. --}}
<link rel="stylesheet" href="{{ asset('css/acessibilidade.css') }}?v={{ @filemtime(public_path('css/acessibilidade.css')) }}">
<script>
    (function () {
        try {
            var passo = parseInt(localStorage.getItem('pgp:a11y:fonte') || '0', 10), passos = [1, 1.1, 1.2, 1.3];
            if (passo > 0 && passo < passos.length) document.documentElement.style.fontSize = (16 * passos[passo]) + 'px';
            if (localStorage.getItem('pgp:a11y:contraste') === '1') document.documentElement.classList.add('a11y-contraste');
        } catch (e) {}
    })();
</script>
