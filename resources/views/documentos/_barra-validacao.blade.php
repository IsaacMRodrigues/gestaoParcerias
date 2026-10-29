{{-- Barra de validação, em cada página do documento assinado: quem assinou, com o CPF parcialmente oculto (LGPD),
     a data e a hora oficiais de Brasília, a chave de validação e o QR Code da conferência.
     Espera: $assinaturas (DocumentoPdf::assinaturas). O QR é o da última assinatura: a página dela mostra todas. --}}
@php $comQr = collect($assinaturas)->whereNotNull('qr')->last(); @endphp
<table style="width:100%;border:none;border-collapse:collapse;border-top:1px solid #1e3a8a;font-family:Helvetica,Arial,sans-serif;">
    <tr>
        <td style="border:none;vertical-align:top;padding:4px 8px 0 0;font-size:7.5px;color:#334155;line-height:1.4;text-align:left;">
            <strong style="color:#1e3a8a;">DOCUMENTO ASSINADO ELETRONICAMENTE</strong> — assinatura eletrônica avançada, Lei Federal nº 14.063/2020<br>
            @foreach($assinaturas as $a)
                {{ $a['nome'] }}{{ $a['cpf'] ? ' (CPF ' . $a['cpf'] . ')' : '' }} — {{ $a['em']->format('d/m/Y \à\s H:i:s') }}, horário oficial de Brasília{{ $a['codigo'] ? ' — chave ' : '' }}<strong>{{ $a['codigo'] }}</strong><br>
            @endforeach
            Confira a autenticidade e a integridade em {{ url('/validar') }} ou pelo QR Code.
        </td>
        @if($comQr)
            <td style="border:none;width:54px;vertical-align:top;padding-top:4px;text-align:right;">
                <img src="{{ $comQr['qr'] }}" style="width:50px;height:50px;" alt="QR Code de validação">
            </td>
        @endif
    </tr>
</table>
