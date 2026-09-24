{{-- Aviso por e-mail. Estilos em linha: cliente de e-mail ignora <style> e
     não conhece as classes do Tailwind. Ver App\Mail\Aviso. --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:20px 28px;border-bottom:3px solid #15803d;">
                            <p style="margin:0;font-size:13px;color:#6b7280;">Prefeitura de São Gonçalo do Rio Abaixo</p>
                            <p style="margin:2px 0 0;font-size:16px;font-weight:bold;color:#111827;">Portal de Parcerias</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px;">
                            <h1 style="margin:0 0 16px;font-size:19px;line-height:1.35;color:#111827;">{{ $titulo }}</h1>
                            @foreach($linhas as $linha)
                                <p style="margin:0 0 12px;font-size:15px;line-height:1.55;color:#374151;">{{ $linha }}</p>
                            @endforeach
                            @if($url)
                                <p style="margin:24px 0 8px;">
                                    <a href="{{ $url }}" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 22px;border-radius:8px;">{{ $botao }}</a>
                                </p>
                                <p style="margin:0;font-size:12px;color:#9ca3af;word-break:break-all;">{{ $url }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.5;color:#6b7280;">
                            Mensagem automática do Portal de Parcerias. Não responda a este e-mail: para falar
                            com a equipe, use o <strong>Suporte</strong>, dentro do sistema.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
