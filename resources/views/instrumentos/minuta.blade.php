<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minuta — {{ $instrumento->numero }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Times New Roman, serif; font-size: 12pt; color: #000; background: #fff; }
        .page { max-width: 21cm; margin: 0 auto; padding: 3cm 2.5cm; }
        h1 { font-size: 14pt; text-align: center; text-transform: uppercase; font-weight: bold; margin-bottom: 8pt; }
        h2 { font-size: 12pt; font-weight: bold; margin: 16pt 0 4pt; }
        p { text-align: justify; line-height: 1.6; margin-bottom: 6pt; }
        .label { font-weight: bold; }
        .section { margin-top: 18pt; }
        .clause { margin-top: 12pt; }
        .clause-title { font-weight: bold; text-transform: uppercase; margin-bottom: 4pt; }
        .signature-block { margin-top: 48pt; }
        .signature-line { border-top: 1px solid #000; width: 280px; display: inline-block; margin-top: 32pt; }
        .signature-grid { display: flex; justify-content: space-between; margin-top: 16pt; }
        .sig { text-align: center; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="no-print" style="background:#fef3c7;padding:12px 16px;border:1px solid #d97706;border-radius:4px;margin-bottom:24pt;font-family:sans-serif;font-size:11pt;">
        <strong>Minuta para impressão</strong> — Esta é uma minuta gerada automaticamente.
        <button onclick="window.print()" style="float:right;padding:4px 12px;background:#1d4ed8;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:11pt;">Imprimir</button>
    </div>

    <h1>{{ \App\Models\Instrumento::TIPOS[$instrumento->tipo] ?? $instrumento->tipo }}</h1>
    <h1 style="font-size:12pt;">Nº {{ $instrumento->numero }}</h1>

    <p style="text-align:center; margin-top:8pt;">
        Celebrado entre
        <span class="label">{{ $instrumento->proposta->chamamento->programa->orgao->name }}</span>
        e
        <span class="label">{{ $instrumento->proposta->osc->name }}</span>.
    </p>

    <div class="section">
        <p>
            O <span class="label">{{ $instrumento->proposta->chamamento->programa->orgao->name }}</span>,
            por meio de sua autoridade competente, doravante denominado <span class="label">CONCEDENTE</span>,
            e a entidade <span class="label">{{ $instrumento->proposta->osc->name }}</span>,
            inscrita no CNPJ sob nº {{ $instrumento->proposta->osc->cnpj }},
            com sede em {{ $instrumento->proposta->osc->logradouro }}, {{ $instrumento->proposta->osc->numero }}
            @if($instrumento->proposta->osc->complemento), {{ $instrumento->proposta->osc->complemento }}@endif,
            {{ $instrumento->proposta->osc->bairro }}, {{ $instrumento->proposta->osc->cidade }}/{{ $instrumento->proposta->osc->estado }},
            representada por <span class="label">{{ $instrumento->proposta->osc->resp_nome }}</span>,
            CPF nº {{ $instrumento->proposta->osc->resp_cpf }},
            doravante denominada <span class="label">PARCEIRA</span>,
            têm entre si justo e acordado o presente instrumento, que se regerá pelas cláusulas e condições a seguir:
        </p>
    </div>

    <div class="clause">
        <p class="clause-title">Cláusula Primeira — Do Objeto</p>
        <p>{{ $instrumento->objeto }}</p>
    </div>

    <div class="clause">
        <p class="clause-title">Cláusula Segunda — Do Valor e dos Recursos</p>
        <p>
            O valor total do presente instrumento é de
            <span class="label">R$ {{ number_format($instrumento->valorTotal(), 2, ',', '.') }}</span>,
            sendo <span class="label">R$ {{ number_format($instrumento->valor_repasse, 2, ',', '.') }}</span>
            de responsabilidade do CONCEDENTE (repasse)
            e <span class="label">R$ {{ number_format($instrumento->valor_proprio, 2, ',', '.') }}</span>
            de contrapartida da PARCEIRA.
        </p>
    </div>

    <div class="clause">
        <p class="clause-title">Cláusula Terceira — Da Vigência</p>
        <p>
            O presente instrumento terá vigência de
            <span class="label">{{ $instrumento->data_inicio->format('d/m/Y') }}</span>
            a
            <span class="label">{{ $instrumento->dataFimVigente()->format('d/m/Y') }}</span>.
        </p>
    </div>

    @if($instrumento->publicado_doe)
    <div class="clause">
        <p class="clause-title">Publicação</p>
        <p>
            Publicado no Diário Oficial em
            {{ $instrumento->data_publicacao_doe?->format('d/m/Y') }}
            @if($instrumento->numero_doe), Edição {{ $instrumento->numero_doe }}@endif.
        </p>
    </div>
    @endif

    <div class="signature-block">
        <p style="text-align:center;">
            {{ $instrumento->proposta->chamamento->programa->orgao->cidade ?? '' }},
            {{ $instrumento->data_assinatura?->format('d/m/Y') ?? '___ de ____________ de ______' }}.
        </p>

        <div class="signature-grid">
            <div class="sig">
                <div class="signature-line"></div>
                <p style="margin-top:4pt;">{{ $instrumento->proposta->chamamento->programa->orgao->name }}</p>
                <p style="font-size:10pt;color:#555;">CONCEDENTE</p>
            </div>
            <div class="sig">
                <div class="signature-line"></div>
                <p style="margin-top:4pt;">{{ $instrumento->proposta->osc->resp_nome }}</p>
                <p style="font-size:10pt;color:#555;">{{ $instrumento->proposta->osc->name }}</p>
                <p style="font-size:10pt;color:#555;">PARCEIRA</p>
            </div>
        </div>
    </div>

</div>
</body>
</html>
