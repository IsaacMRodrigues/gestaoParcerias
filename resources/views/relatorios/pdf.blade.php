<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 30px 40px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #111; }
        .cab td { border: none; vertical-align: middle; }
        h1 { font-size: 13pt; margin: 14px 0 2px; }
        h2 { font-size: 10pt; margin: 16px 0 6px; }
        .filtros { color: #555; margin: 0 0 10px; }
        table.dados { width: 100%; border-collapse: collapse; }
        table.dados th { background: #f1f5f4; text-align: left; font-size: 7.5pt; text-transform: uppercase; padding: 5px 4px; border-bottom: 1px solid #999; }
        table.dados td { padding: 4px; border-bottom: 1px solid #ddd; }
        table.dados .n { text-align: right; white-space: nowrap; }
        table.dados tfoot td { font-weight: bold; border-top: 1.5px solid #555; border-bottom: none; background: #f7f7f7; }
        .neg { color: #b91c1c; }
        .rodape { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 7pt; color: #777; }
    </style>
</head>
<body>
    <table class="cab" style="width:100%;border-collapse:collapse"><tr>
        <td style="width:80px"><img src="https://pmsgra.net/logo.png" width="64"></td>
        <td style="text-align:center"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>Plataforma de Gestão de Parcerias — Prestação de contas</td>
        <td style="width:80px"></td>
    </tr></table>

    <h1>{{ $r['titulo'] }}</h1>
    <p class="filtros">{{ implode(' · ', $r['filtros']) }} · Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @php $direita = ['dinheiro', 'pct', 'numero']; @endphp
    @foreach($r['tabelas'] as $t)
        @if($t['titulo'])<h2>{{ $t['titulo'] }}</h2>@endif
        @if(empty($t['linhas']))
            <p>Nada encontrado com esses filtros.</p>
        @else
            <table class="dados">
                <thead><tr>
                    @foreach($t['colunas'] as [$rotulo, $tipo])<th class="{{ in_array($tipo, $direita, true) ? 'n' : '' }}">{{ $rotulo }}</th>@endforeach
                </tr></thead>
                <tbody>
                    @foreach($t['linhas'] as $linha)
                        <tr>
                            @foreach($t['colunas'] as $chave => [$rotulo, $tipo])
                                @php $v = $linha[$chave] ?? null; @endphp
                                <td class="{{ in_array($tipo, $direita, true) ? 'n' : '' }} {{ $tipo === 'dinheiro' && (float) $v < 0 ? 'neg' : '' }}">{{ \App\Support\RelatorioPrestacao::formatar($v, $tipo) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                @if($t['totais'])
                    <tfoot><tr>
                        @foreach($t['colunas'] as $chave => [$rotulo, $tipo])
                            <td class="{{ in_array($tipo, $direita, true) ? 'n' : '' }}">{{ array_key_exists($chave, $t['totais']) ? \App\Support\RelatorioPrestacao::formatar($t['totais'][$chave], $tipo) : '' }}</td>
                        @endforeach
                    </tr></tfoot>
                @endif
            </table>
        @endif
    @endforeach

    <div class="rodape">Plataforma de Gestão de Parcerias — Prefeitura Municipal de São Gonçalo do Rio Abaixo</div>
</body>
</html>
