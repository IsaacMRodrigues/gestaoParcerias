<?php

namespace App\Support;

use App\Models\Peca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * PDF de um documento de texto (Peca, ProcessoPeca ou OrdemPagamento), com todas as assinaturas, o QR de validação de
 * cada uma e, em cada página, a barra de validação.
 */
class DocumentoPdf
{
    public static function gerar(Model $doc): string
    {
        $html = self::html($doc);

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled'      => true,   // o brasão vem de endereço remoto
            'isHtml5ParserEnabled' => true,
            'defaultFont'          => 'Helvetica',
        ]);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $pdf = $dompdf->output();
        TrilhaDeAssinatura::registrarEmissao($doc, $pdf);

        return $pdf;
    }

    public static function html(Model $doc): string
    {
        $assinaturas = self::assinaturas($doc);

        return view('documentos.pdf', [
            'conteudo'    => $doc->conteudo,
            'assinaturas' => $assinaturas,
            'hashes'      => TrilhaDeAssinatura::hashesAssinados(array_column($assinaturas, 'codigo')),
        ])->render();
    }

    /** "03-parecer-juridico.pdf"; sem posição, só o nome. */
    public static function nomeArquivo(string $rotulo, ?int $posicao = null, string $extensao = 'pdf'): string
    {
        $nome = Str::slug(Str::before($rotulo, ' (')) ?: 'documento';

        return ($posicao !== null ? sprintf('%02d-', $posicao) : '') . $nome . '.' . $extensao;
    }

    /**
     * As assinaturas do documento, com a hora de Brasília e o CPF parcialmente oculto, gravado na trilha no ato.
     *
     * @return list<array{nome: ?string, cargo: ?string, em: \Illuminate\Support\Carbon, codigo: ?string, qr: ?string, fonte: ?string, cpf: ?string}>
     */
    public static function assinaturas(Model $doc): array
    {
        $lista = [];
        $adicionar = function (?string $nome, ?string $cargo, $em, ?string $codigo) use (&$lista) {
            if ($em === null) {
                return;
            }
            $qr = $codigo
                ? 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(110)->margin(0)->generate(route('validacao.mostrar', $codigo)))
                : null;
            $evento = TrilhaDeAssinatura::eventoDaAssinatura($codigo);
            $lista[] = ['nome' => $nome, 'cargo' => $cargo, 'em' => HoraLegal::emBrasilia($em), 'codigo' => $codigo, 'qr' => $qr,
                'fonte' => $evento?->fonteDaHoraDaAssinatura(), 'cpf' => $evento?->cpfMascarado()];
        };

        if ($doc instanceof Peca && $doc->assinado_em === null && $doc->temAssinaturasEmSequencia()) {
            foreach ($doc->assinaturasPartes as $parte) {
                $adicionar($parte->assinante_nome, $parte->assinante_cargo, $parte->assinado_em, $parte->codigo_validacao);
            }
        } else {
            $adicionar($doc->assinanteNome(), $doc->assinanteCargo(), $doc->assinado_em, $doc->codigo_validacao);
        }

        if ($doc instanceof Peca && $doc->contraAssinado()) {
            $adicionar($doc->contraAssinanteNome(), $doc->contraAssinanteCargo(), $doc->contra_assinado_em, $doc->codigo_validacao_contra);
        }

        return $lista;
    }
}
