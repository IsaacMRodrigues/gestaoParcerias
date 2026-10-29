@php
    // A barra de validação ocupa a margem de baixo de cada página: a margem cresce com o número de assinaturas.
    $barra = $assinaturas ? max(1.5, 0.45 + (count($assinaturas) + 2) * 0.3) : 0;
    $margem = $assinaturas ? $barra + 0.9 : 1.5;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm 2cm {{ $margem }}cm; }
        .barra-validacao { position: fixed; left: 0; right: 0; bottom: -{{ $margem - 0.5 }}cm; }
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
{{-- Fixa, antes do conteúdo: o dompdf a repete em todas as páginas. --}}
@if($assinaturas)
    <div class="barra-validacao">@include('documentos._barra-validacao', ['assinaturas' => $assinaturas])</div>
@endif
<div class="doc">
    {!! $conteudo !!}

    {{-- Uma faixa por assinatura, com nome e cargo gravados no ato (ver Concerns\GuardaQuemAssinou). --}}
    @foreach($assinaturas as $a)
        <table style="border:none;border-collapse:collapse;width:100%;margin-top:{{ $loop->first ? '28px' : '10px' }};border-top:2px solid #1e3a8a;">
            <tr>
                <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                    <p style="margin:0;"><strong>ASSINATURA ELETRÔNICA.</strong> Documento assinado eletronicamente por
                        <strong>{{ $a['nome'] }}</strong>{{ $a['cpf'] ? ' (CPF ' . $a['cpf'] . ')' : '' }}@if($a['cargo']), {{ $a['cargo'] }}@endif,
                        em <strong>{{ $a['em']->format('d/m/Y') }}</strong>,
                        às <strong>{{ $a['em']->format('H:i:s') }}</strong>,
                        horário oficial de Brasília{{ $a['fonte'] ? ' (carimbo do tempo pela Hora Legal Brasileira, NTP.br)' : '' }},
                        com fundamento na Lei Federal nº 13.019/2014 e na Lei Federal nº 14.063/2020.</p>
                    @if($a['codigo'])
                        <p style="margin:4px 0 0;">A autenticidade e a integridade podem ser verificadas em
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

    @foreach($hashes as $hash)
        <p style="margin:6px 0 0;font-size:9px;color:#475569;line-height:1.4;text-align:left;">
            Código de integridade do texto assinado (SHA-256): <span style="font-family:monospace;">{{ $hash }}</span>
        </p>
    @endforeach
    {{-- Só os PDFs com assinatura entram no registro de emitidos (TrilhaDeAssinatura::registrarEmissao). --}}
    @if(array_filter(array_column($assinaturas, 'codigo')))
        <p style="margin:4px 0 0;font-size:9px;color:#475569;line-height:1.4;text-align:left;">
            Este arquivo PDF pode ser conferido, tal como está, em {{ url('/validar') }}: qualquer alteração nele é detectada.
        </p>
    @endif
</div>
</body>
</html>
