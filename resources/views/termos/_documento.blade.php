{{-- O texto dos Termos com o cabeçalho da versão; serve à página pública e à tela do aceite. --}}
<div class="termos-de-uso text-sm text-gray-700 leading-relaxed">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Versão {{ \App\Support\TermosDeUso::VERSAO }} · vigente desde {{ \App\Support\TermosDeUso::VIGENTE_DESDE }}
    </p>
    {!! \App\Support\TermosDeUso::texto() !!}
</div>

<style>
    .termos-de-uso h2 { margin: 1.4rem 0 .4rem; font-size: .95rem; font-weight: 700; color: #111827; }
    .termos-de-uso p { margin: .45rem 0; text-align: justify; }
    .termos-de-uso ul { margin: .45rem 0; padding-left: 1.25rem; list-style: disc; }
    .termos-de-uso li { margin: .3rem 0; text-align: justify; }
    .termos-de-uso strong { font-weight: 600; color: #1f2937; }
</style>
