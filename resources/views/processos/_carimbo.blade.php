{{-- Carimbo de assinatura eletrônica (padrão documento oficial).
     Espera: $peca, $qrValidacao e — quando o documento tem assinatura das
     partes (Termo de Parceria) — $qrContra. --}}
@php
    /* Nome e cargo gravados no ato da assinatura; o cadastro atual só para assinatura antiga sem registro. */
    $identidade = function (?string $nome, ?string $cargo, $u) {
        return [
            'nome'  => $nome ?: $u?->name,
            'cargo' => $cargo ?: $u?->cargoParaAssinatura(),
        ];
    };

    /* Uma entrada por assinatura (inclui a contra-assinatura dos Termos antigos e as partes do
       documento assinado em sequência). method_exists: ProcessoPeca e OrdemPagamento também usam. */
    $assinaturas = [];

    if ($peca->assinado_em) {
        $assinaturas[] = $identidade($peca->assinante_nome, $peca->assinante_cargo, $peca->assinante) + [
            'verbo'  => 'assinado eletronicamente',
            'em'     => $peca->assinado_em,
            'codigo' => $peca->codigo_validacao,
            'qr'     => $qrValidacao ?? null,
        ];
    }

    /* Assinado em sequência (o Termo): uma entrada por parte,
       cada uma com o seu código e o seu QR. */
    if (method_exists($peca, 'temAssinaturasEmSequencia') && $peca->temAssinaturasEmSequencia()) {
        $rotulos = $peca->sequenciaDeAssinaturas();
        foreach ($peca->assinaturasPartes as $parte) {
            $assinaturas[] = $identidade($parte->assinante_nome, $parte->assinante_cargo, $parte->assinante) + [
                'verbo'  => 'assinado eletronicamente (' . ($rotulos[$parte->papel]['rotulo'] ?? $parte->papel) . ')',
                'em'     => $parte->assinado_em,
                'codigo' => $parte->codigo_validacao,
                'qr'     => \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->generate(route('validacao.mostrar', $parte->codigo_validacao)),
            ];
        }
    }

    if (method_exists($peca, 'contraAssinado') && $peca->contraAssinado()) {
        $assinaturas[] = $identidade($peca->contra_assinante_nome, $peca->contra_assinante_cargo, $peca->contraAssinante) + [
            'verbo'  => 'contra-assinado eletronicamente (assinatura das partes)',
            'em'     => $peca->contra_assinado_em,
            'codigo' => $peca->codigo_validacao_contra,
            'qr'     => $qrContra ?? null,
        ];
    }
@endphp

@foreach($assinaturas as $i => $assinatura)
    {{-- A segunda assinatura encosta na primeira, com filete leve: é o mesmo
         carimbo do mesmo documento, não outro bloco. --}}
    <table style="border:none;border-collapse:collapse;width:100%;
                  margin-top:{{ $i === 0 ? '28px' : '10px' }};
                  border-top:{{ $i === 0 ? '2px solid #1e3a8a' : '1px solid #cbd5e1' }};">
        <tr>
            <td style="border:none;width:48px;vertical-align:top;padding-top:8px;font-size:26px;">🔏</td>
            <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                <p style="margin:0;">Documento {{ $assinatura['verbo'] }} por
                    <strong>{{ $assinatura['nome'] }}</strong>@if($assinatura['cargo']), {{ $assinatura['cargo'] }}@endif,
                    em <strong>{{ $assinatura['em']->format('d/m/Y') }}</strong>,
                    às <strong>{{ $assinatura['em']->format('H:i') }}</strong>,
                    conforme horário oficial de Brasília, com fundamento na Lei Federal nº 13.019/2014.</p>
                <p style="margin:4px 0 0;">A autenticidade deste documento pode ser verificada apontando a câmera
                    para o QR Code ao lado, ou em <strong>{{ url('/validar') }}</strong> com o código
                    <strong style="font-family:monospace;letter-spacing:.5px;">{{ $assinatura['codigo'] }}</strong>.</p>
            </td>
            @if($assinatura['qr'])
                <td style="border:none;width:120px;vertical-align:top;padding-top:8px;text-align:center;">
                    <div style="width:110px;">{!! $assinatura['qr'] !!}</div>
                </td>
            @endif
        </tr>
    </table>
@endforeach
