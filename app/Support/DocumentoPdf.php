<?php

namespace App\Support;

use App\Models\Peca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** PDF de um documento de texto (Peca ou ProcessoPeca), com todas as assinaturas e o QR de validação de cada uma. */
class DocumentoPdf
{
    public static function gerar(Model $doc): string
    {
        $html = view('documentos.pdf', ['conteudo' => $doc->conteudo, 'assinaturas' => self::assinaturas($doc)])->render();

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled'      => true,   // o brasão vem de endereço remoto
            'isHtml5ParserEnabled' => true,
            'defaultFont'          => 'Helvetica',
        ]);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    /** "03-parecer-juridico.pdf"; sem posição, só o nome. */
    public static function nomeArquivo(string $rotulo, ?int $posicao = null, string $extensao = 'pdf'): string
    {
        $nome = Str::slug(Str::before($rotulo, ' (')) ?: 'documento';

        return ($posicao !== null ? sprintf('%02d-', $posicao) : '') . $nome . '.' . $extensao;
    }

    /** @return list<array{nome: ?string, cargo: ?string, em: \Illuminate\Support\Carbon, codigo: ?string, qr: ?string}> */
    private static function assinaturas(Model $doc): array
    {
        $lista = [];
        $adicionar = function (?string $nome, ?string $cargo, $em, ?string $codigo) use (&$lista) {
            if ($em === null) {
                return;
            }
            $qr = $codigo
                ? 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(110)->margin(0)->generate(route('validacao.mostrar', $codigo)))
                : null;
            $lista[] = ['nome' => $nome, 'cargo' => $cargo, 'em' => $em, 'codigo' => $codigo, 'qr' => $qr];
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
