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
    {!! $peca->conteudo !!}

    @if($peca->assinado())
        @php
            $assinante = $peca->assinante;
            $papel = $assinante?->roles->first()?->name;
            $papelLabel = $papel ? (\App\Models\User::$roleLabels[$papel] ?? null) : null;
            $setorLabel = $assinante?->setor ? (\App\Models\Processo::SETORES[$assinante->setor] ?? null) : null;
            $cargo = $papelLabel ?: $setorLabel;
            $orgaoNome = $assinante?->orgao?->name;
            if ($orgaoNome) {
                $cargo = $cargo ? $cargo . ' — ' . $orgaoNome : $orgaoNome;
            }
        @endphp
        <table style="border:none;border-collapse:collapse;width:100%;margin-top:28px;border-top:2px solid #1e3a8a;">
            <tr>
                <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                    <p style="margin:0;"><strong>ASSINATURA ELETRÔNICA.</strong> Documento assinado eletronicamente por
                        <strong>{{ $assinante?->name }}</strong>@if($cargo), {{ $cargo }}@endif,
                        em <strong>{{ $peca->assinado_em->format('d/m/Y') }}</strong>,
                        às <strong>{{ $peca->assinado_em->format('H:i') }}</strong>,
                        conforme horário oficial de Brasília, com fundamento na Lei Federal nº 13.019/2014.</p>
                    <p style="margin:4px 0 0;">A autenticidade pode ser verificada em
                        <strong>{{ url('/validar') }}</strong> com o código
                        <strong style="font-family:monospace;letter-spacing:.5px;">{{ $peca->codigo_validacao }}</strong>.</p>
                </td>
                @if($qrImg)
                    <td style="border:none;width:120px;vertical-align:top;padding-top:8px;text-align:center;">{!! $qrImg !!}</td>
                @endif
            </tr>
        </table>
    @endif
</div>
</body>
</html>
