{{-- Carimbo de assinatura eletrônica (padrão documento oficial).
     Espera: $peca, $qrValidacao e — quando o documento tem assinatura das
     partes (Termo de Parceria) — $qrContra. --}}
@php
    /* Nome e cargo vêm do que foi gravado no ato da assinatura, não do
       cadastro de hoje: editar o perfil, mudar de setor ou ganhar outro papel
       não pode reescrever quem assinou o quê. A leitura do usuário vivo fica
       só como recurso para assinatura antiga que não tenha o registro. */
    $identidade = function (?string $nome, ?string $cargo, $u) {
        return [
            'nome'  => $nome ?: $u?->name,
            'cargo' => $cargo ?: $u?->cargoParaAssinatura(),
        ];
    };

    /* Uma entrada por assinatura. O Termo da Celebração é assinado pelas duas
       partes (Município e OSC) e o carimbo mostrava só a primeira: quem lia o
       documento não via de quem era a contra-assinatura, nem quando foi dada.
       method_exists porque ProcessoPeca e OrdemPagamento usam este mesmo
       carimbo e não têm assinatura das partes. */
    $assinaturas = [];

    if ($peca->assinado()) {
        $assinaturas[] = $identidade($peca->assinante_nome, $peca->assinante_cargo, $peca->assinante) + [
            'verbo'  => 'assinado eletronicamente',
            'em'     => $peca->assinado_em,
            'codigo' => $peca->codigo_validacao,
            'qr'     => $qrValidacao ?? null,
        ];
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
