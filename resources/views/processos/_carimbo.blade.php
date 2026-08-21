{{-- Carimbo de assinatura eletrônica (padrão documento oficial).
     Espera: $peca, $qrValidacao e — quando o documento tem assinatura das
     partes (Termo de Parceria) — $qrContra. --}}
@php
    /* Cargo de quem assina: o papel no sistema e a entidade por quem assina.
       Para o servidor, a Secretaria; para quem assina pela OSC, o nome da
       própria OSC — é ela que se obriga no Termo, e o carimbo sem isso dizia
       apenas "Representante Legal", sem dizer de quem. */
    $cargoDe = function ($u) {
        if (!$u) {
            return null;
        }

        $papel = $u->roles->first()?->name;
        $cargo = $papel ? (\App\Models\User::$roleLabels[$papel] ?? null) : null;
        $cargo = $cargo ?: ($u->setor ? (\App\Models\Processo::SETORES[$u->setor] ?? null) : null);

        $entidade = $u->orgao?->name ?: $u->osc?->name;

        return $entidade ? ($cargo ? $cargo . ' — ' . $entidade : $entidade) : $cargo;
    };

    /* Uma entrada por assinatura. O Termo da Celebração é assinado pelas duas
       partes (Município e OSC) e o carimbo mostrava só a primeira: quem lia o
       documento não via de quem era a contra-assinatura, nem quando foi dada.
       method_exists porque ProcessoPeca e OrdemPagamento usam este mesmo
       carimbo e não têm assinatura das partes. */
    $assinaturas = [];

    if ($peca->assinado()) {
        $assinaturas[] = [
            'verbo'  => 'assinado eletronicamente',
            'pessoa' => $peca->assinante,
            'em'     => $peca->assinado_em,
            'codigo' => $peca->codigo_validacao,
            'qr'     => $qrValidacao ?? null,
        ];
    }

    if (method_exists($peca, 'contraAssinado') && $peca->contraAssinado()) {
        $assinaturas[] = [
            'verbo'  => 'contra-assinado eletronicamente (assinatura das partes)',
            'pessoa' => $peca->contraAssinante,
            'em'     => $peca->contra_assinado_em,
            'codigo' => $peca->codigo_validacao_contra,
            'qr'     => $qrContra ?? null,
        ];
    }
@endphp

@foreach($assinaturas as $i => $assinatura)
    @php $cargo = $cargoDe($assinatura['pessoa']); @endphp
    {{-- A segunda assinatura encosta na primeira, com filete leve: é o mesmo
         carimbo do mesmo documento, não outro bloco. --}}
    <table style="border:none;border-collapse:collapse;width:100%;
                  margin-top:{{ $i === 0 ? '28px' : '10px' }};
                  border-top:{{ $i === 0 ? '2px solid #1e3a8a' : '1px solid #cbd5e1' }};">
        <tr>
            <td style="border:none;width:48px;vertical-align:top;padding-top:8px;font-size:26px;">🔏</td>
            <td style="border:none;vertical-align:top;padding-top:8px;font-size:11px;color:#1e293b;line-height:1.5;">
                <p style="margin:0;">Documento {{ $assinatura['verbo'] }} por
                    <strong>{{ $assinatura['pessoa']?->name }}</strong>@if($cargo), {{ $cargo }}@endif,
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
