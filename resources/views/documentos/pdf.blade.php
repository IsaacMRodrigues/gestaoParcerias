<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm 2cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #111; }
        p { margin: .4rem 0; line-height: 1.6; text-align: justify; }
        strong { font-weight: bold; }
        ul, ol { padding-left: 1.5rem; }
        img { max-width: 120px; height: auto; }
        table { border-collapse: collapse; }
        .doc table:not([style*="border:none"]) { width: 100%; }
        .doc th, .doc td { border: 1px solid #555; padding: 4px 8px; font-size: 12px; }
        .doc table[style*="border:none"] td { border: none; }
    </style>
</head>
<body>
<div class="doc">
    {!! $conteudo !!}

    {{-- Uma faixa por assinatura, com nome e cargo gravados no ato (ver Concerns\GuardaQuemAssinou). --}}
    @foreach($assinaturas as $a)
        <table style="border:none;border-collapse:collapse;width:100%;margin-top:{{ $loop->first ? '28px' : '10px' }};border-top:2px solid #1e3a8a;">
            <tr>
                <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                    <p style="margin:0;"><strong>ASSINATURA ELETRÔNICA.</strong> Documento assinado eletronicamente por
                        <strong>{{ $a['nome'] }}</strong>@if($a['cargo']), {{ $a['cargo'] }}@endif,
                        em <strong>{{ $a['em']->format('d/m/Y') }}</strong>,
                        às <strong>{{ $a['em']->format('H:i') }}</strong>,
                        conforme horário oficial de Brasília, com fundamento na Lei Federal nº 13.019/2014.</p>
                    @if($a['codigo'])
                        <p style="margin:4px 0 0;">A autenticidade pode ser verificada em
                            <strong>{{ url('/validar') }}</strong> com o código
                            <strong style="font-family:monospace;letter-spacing:.5px;">{{ $a['codigo'] }}</strong>.</p>
                    @endif
                </td>
                @if($a['qr'])
                    <td style="border:none;width:120px;vertical-align:top;padding-top:8px;text-align:center;">
                        <img src="{{ $a['qr'] }}" style="width:110px;height:110px;">
                    </td>
                @endif
            </tr>
        </table>
    @endforeach
</div>
</body>
</html>
