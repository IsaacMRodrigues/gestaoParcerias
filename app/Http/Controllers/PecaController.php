<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PecaController extends Controller
{
    /**
     * Autorização das peças. Peças em trâmite (Seleção/Celebração) são gated por
     * setor + etapa — o que também abre a vez da OSC nas peças da própria
     * parceria. Peças fora de trâmite continuam exigindo a permissão da área.
     */
    private function autorizar(Peca $peca, string $acao = 'preencher'): void
    {
        $user = auth()->user();

        $permitido = $acao === 'assinar'
            ? $peca->podeAssinar($user)
            : $peca->podePreencher($user);

        abort_unless($permitido, 403,
            $peca->motivoTrava($user) ?? 'Você não pode alterar esta peça agora.');

        if (!$peca->emTramite()) {
            abort_unless($user?->can('chamamentos') || $user?->can('formalizacao'), 403);
        }
    }

    public function salvar(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);
        $this->autorizar($peca);

        $data = $request->validate([
            'conteudo' => ['nullable', 'string'],
        ]);

        $peca->update($data);

        return back()->with('success', $peca->rotulo . ' salvo.');
    }

    public function assinar(Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);
        abort_if(empty($peca->conteudo), 422, 'Preencha o documento antes de assinar.');
        $this->autorizar($peca, 'assinar');

        $peca->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'codigo_validacao' => $peca->codigo_validacao ?: Peca::gerarCodigoValidacao(),
        ]);

        return back()->with('success', $peca->rotulo . ' assinado.');
    }

    /**
     * Contra-assinatura ("assinatura das partes"): a OSC assina o Termo já
     * assinado pelo Município.
     */
    public function contraAssinar(Peca $peca): RedirectResponse
    {
        abort_unless($peca->exigeContraAssinatura(), 422,
            'Esta peça não exige assinatura das partes.');
        abort_unless($peca->assinado(), 422,
            'O documento ainda não foi assinado pela Administração.');
        abort_unless($peca->podeContraAssinar(auth()->user()), 403,
            'Você não pode contra-assinar este documento agora.');

        $peca->update([
            'contra_assinado_por'     => auth()->id(),
            'contra_assinado_em'      => now(),
            'codigo_validacao_contra' => $peca->codigo_validacao_contra ?: Peca::gerarCodigoValidacao(),
        ]);

        return back()->with('success', $peca->rotulo . ' contra-assinado pela OSC.');
    }

    public function upload(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'arquivo', 422);
        $this->autorizar($peca);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ], [
            'arquivo.max'   => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes' => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        // remove arquivo anterior, se houver
        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store('pecas/' . $peca->id, 'local');

        $peca->update([
            'arquivo_path' => $path,
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'tamanho'      => $arquivo->getSize(),
            'mime_type'    => $arquivo->getMimeType(),
        ]);

        return back()->with('success', $peca->rotulo . ' enviado.');
    }

    /**
     * Puxa um documento que a OSC já enviou no módulo Gestão de Parcerias (proposta)
     * e o anexa à peça, copiando o arquivo para o armazenamento próprio da peça.
     */
    public function puxar(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'arquivo', 422);
        abort_unless($peca->puxavel(), 422, 'Esta peça não permite puxar do módulo Gestão de Parcerias.');
        $this->autorizar($peca);

        $data = $request->validate(['documento_id' => ['required', 'integer']]);

        // só aceita documentos da(s) proposta(s) realmente vinculada(s) a esta peça
        $documento = $peca->documentosDisponiveis()->firstWhere('id', (int) $data['documento_id']);
        abort_unless($documento, 404, 'Documento indisponível para esta peça.');
        abort_unless(Storage::disk('local')->exists($documento->path), 404, 'Arquivo de origem não encontrado.');

        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $ext = pathinfo($documento->nome_original, PATHINFO_EXTENSION);
        $destino = 'pecas/' . $peca->id . '/' . \Illuminate\Support\Str::random(20) . ($ext ? '.' . $ext : '');
        Storage::disk('local')->copy($documento->path, $destino);

        $peca->update([
            'arquivo_path' => $destino,
            'arquivo_nome' => $documento->nome_original,
            'tamanho'      => $documento->tamanho,
            'mime_type'    => $documento->mime_type,
        ]);

        return back()->with('success', $peca->rotulo . ' puxado do módulo Gestão de Parcerias.');
    }

    public function download(Peca $peca): StreamedResponse
    {
        abort_unless($peca->podeVer(auth()->user()), 403);
        abort_unless($peca->arquivo_path && Storage::disk('local')->exists($peca->arquivo_path), 404);

        return Storage::disk('local')->download($peca->arquivo_path, $peca->arquivo_nome);
    }

    public function removerArquivo(Peca $peca): RedirectResponse
    {
        $this->autorizar($peca);

        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $peca->update([
            'arquivo_path' => null,
            'arquivo_nome' => null,
            'tamanho'      => null,
            'mime_type'    => null,
        ]);

        return back()->with('success', 'Arquivo removido.');
    }
}
