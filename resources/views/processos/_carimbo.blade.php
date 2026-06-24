{{-- Carimbo de assinatura eletrônica (padrão documento oficial). Espera: $peca, $qrValidacao --}}
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
            <td style="border:none;width:48px;vertical-align:top;padding-top:8px;font-size:26px;">🔏</td>
            <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                <p style="margin:0;">Documento assinado eletronicamente por
                    <strong>{{ $assinante?->name }}</strong>@if($cargo), {{ $cargo }}@endif,
                    em <strong>{{ $peca->assinado_em->format('d/m/Y') }}</strong>,
                    às <strong>{{ $peca->assinado_em->format('H:i') }}</strong>,
                    conforme horário oficial de Brasília, com fundamento na Lei Federal nº 13.019/2014.</p>
                <p style="margin:4px 0 0;">A autenticidade deste documento pode ser verificada apontando a câmera
                    para o QR Code ao lado, ou em <strong>{{ url('/validar') }}</strong> com o código
                    <strong style="font-family:monospace;letter-spacing:.5px;">{{ $peca->codigo_validacao }}</strong>.</p>
            </td>
            @if($qrValidacao)
                <td style="border:none;width:120px;vertical-align:top;padding-top:8px;text-align:center;">
                    <div style="width:110px;">{!! $qrValidacao !!}</div>
                </td>
            @endif
        </tr>
    </table>
@endif
