<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\ProcessoPeca::TIPOS[$peca->tipo] ?? 'Documento' }} — {{ $processo->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; background: #fff; margin: 0; }
        .page { max-width: 21cm; margin: 0 auto; padding: 2.5cm 2cm; }
        p { margin: .4rem 0; line-height: 1.6; text-align: justify; }
        strong { font-weight: bold; }
        ul, ol { padding-left: 1.5rem; }
        img { max-width: 120px; height: auto; }
        table { border-collapse: collapse; }
        .doc table:not([style*="border:none"]) { width: 100%; }
        .doc th, .doc td { border: 1px solid #555; padding: 4px 8px; font-size: 12px; }
        .doc table[style*="border:none"] td { border: none; }
        .rodape { margin-top: 6px; font-size: 10px; color: #777; text-align: right; }
        .barra-print { background:#fef3c7; border:1px solid #d97706; padding:10px 14px; margin-bottom:18px; font-size:12px; border-radius:4px; }
        @media print { .barra-print { display: none; } .page { padding: 1.5cm; } }
    </style>
</head>
<body>
<div class="page">
    <div class="barra-print">
        <strong>Documento para impressão.</strong>
        <button onclick="window.print()" style="float:right;padding:4px 12px;background:#1d4ed8;color:#fff;border:none;border-radius:4px;cursor:pointer;">Imprimir</button>
    </div>

    <div class="doc">
        {!! $peca->conteudo !!}
        @include('processos._carimbo', ['peca' => $peca, 'qrValidacao' => $qrValidacao])
    </div>

    <p class="rodape">Processo nº {{ $processo->numero }} · {{ \App\Models\ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo }}</p>
</div>
</body>
</html>
