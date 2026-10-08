<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Support\DocumentoPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PecaController extends Controller
{
    /** Peças em trâmite: setor + etapa (inclui a vez da OSC); fora de trâmite, a permissão da área. */
    private function autorizar(Peca $peca, string $acao = 'preencher'): void
    {
        $user = auth()->user();

        // Chamamento cancelado: nenhuma peça dele se preenche nem se assina.
        $dono = $peca->pecaable;
        $chamamento = $dono instanceof \App\Models\Chamamento ? $dono
            : ($dono instanceof \App\Models\Proposta ? $dono->chamamento : null);
        abort_if($chamamento?->cancelado(), 422, 'Este chamamento está cancelado: reabra-o para mexer nos documentos.');

        $permitido = $acao === 'assinar'
            ? $peca->podeAssinar($user)
            : $peca->podePreencher($user);

        abort_unless($permitido, 403,
            $peca->motivoTrava($user) ?? 'Você não pode alterar esta peça agora.');

        if (!$peca->emTramite()) {
            abort_unless($user?->can('chamamentos') || $user?->can('formalizacao'), 403);
        }

        // Do lado da OSC, a vez é da organização mas a mão é de quem o
        // responsável legal marcou — os dados bancários da Celebração não são
        // assunto de toda a equipe.
        abort_if($user?->oscSemFuncao('osc_celebracao'), 403,
            'Sua conta não tem a função "Celebração da parceria". '
            .'Peça ao responsável legal da OSC para marcá-la em Usuários da Organização.');
    }

    /** Volta para a linha da peça (#peca-{id}), não para o topo da página. */
    private function voltarParaPeca(Peca $peca, string $mensagem): RedirectResponse
    {
        // 'peca_aberta': o documento em que se trabalhava volta aberto — salvar ou
        // assinar não deve fechá-lo e devolver o usuário à lista.
        return back()->withFragment('peca-' . $peca->id)->with('success', $mensagem)->with('peca_aberta', $peca->id);
    }

    public function salvar(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);
        $this->autorizar($peca);

        $data = $request->validate([
            'conteudo' => ['nullable', 'string'],
        ]);

        $peca->update($data);

        $peca->limparDevolucao();

        return $this->voltarParaPeca($peca, $peca->rotulo . ' salvo.');
    }

    public function assinar(Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);
        abort_if(empty($peca->conteudo), 422, 'Preencha o documento antes de assinar.');
        $this->autorizar($peca, 'assinar');

        // Nome e cargo ficam gravados aqui: o carimbo não pode mudar depois
        // porque a pessoa editou o perfil ou trocou de setor.
        $quem = auth()->user()->identidadeParaAssinatura();

        $peca->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'assinante_nome'   => $quem['nome'],
            'assinante_cargo'  => $quem['cargo'],
            'codigo_validacao' => $peca->codigo_validacao ?: Peca::gerarCodigoValidacao(),
        ]);

        $peca->limparDevolucao();

        return $this->voltarParaPeca($peca, $peca->rotulo . ' assinado.');
    }

    /** Assinatura de uma das partes de documento assinado em sequência (o Termo, a OP Global). */
    public function assinarParte(Peca $peca): RedirectResponse
    {
        abort_unless($peca->temAssinaturasEmSequencia(), 422, 'Este documento não é assinado em sequência.');
        abort_unless($peca->podeAssinarComoParte(auth()->user()), 403,
            'Não é a sua vez de assinar este documento.');

        $papel = $peca->papelDaVez();
        $peca->assinarComoParte(auth()->user());
        $peca->limparDevolucao();

        return $this->voltarParaPeca($peca, $peca->rotulo . ' assinado (' . $peca->sequenciaDeAssinaturas()[$papel]['rotulo'] . ').');
    }

    public function upload(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'arquivo', 422);
        $this->autorizar($peca);

        // Qualquer formato: o que a etapa pede varia (planilha, imagem, .rar de
        // fotos, laudo em .odt) e nenhuma lista prevê tudo. Só o tamanho limita.
        $request->validate([
            'arquivo' => ['required', 'file', 'max:10240'],
        ], [
            'arquivo.max' => 'O arquivo não pode ultrapassar 10 MB.',
        ]);

        // remove arquivo anterior, se houver
        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store('pecas/' . $peca->id, 'local');

        // O arquivo enviado aqui vale por cima do que vinha de "Arquivos da OSC".
        $peca->update([
            'arquivo_path'   => $path,
            'arquivo_nome'   => $arquivo->getClientOriginalName(),
            'tamanho'        => $arquivo->getSize(),
            'mime_type'      => $arquivo->getMimeType(),
            'osc_arquivo_id' => null,
        ]);

        $peca->limparDevolucao();

        return $this->voltarParaPeca($peca, $peca->rotulo . ' enviado.');
    }

    /** Puxa um documento que a OSC já enviou na proposta e o copia para a peça. */
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
            'arquivo_path'   => $destino,
            'arquivo_nome'   => $documento->nome_original,
            'tamanho'        => $documento->tamanho,
            'mime_type'      => $documento->mime_type,
            'osc_arquivo_id' => null,
        ]);

        return $this->voltarParaPeca($peca, $peca->rotulo . ' puxado do módulo Gestão de Parcerias.');
    }

    /** Apaga um anexo avulso (o espaço inteiro). Só os criados à mão; quem preenche pode remover. */
    public function destruirExtra(Peca $peca): RedirectResponse
    {
        abort_unless($peca->extra, 403, 'Este item faz parte do checklist e não pode ser removido.');
        $this->autorizar($peca);
        abort_if($peca->assinado(), 422, 'Documento assinado não é removido.');

        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $rotulo = $peca->rotulo;
        $peca->delete();

        return back()->with('success', '"' . $rotulo . '" removido do checklist.');
    }

    /** Baixa um anexo do documento do Planejamento que satisfaz esta peça, para quem vê a peça. */
    public function baixarAnexoOrigem(Peca $peca, \App\Models\ProcessoPecaAnexo $anexo): StreamedResponse
    {
        abort_unless($peca->podeBaixar(auth()->user()), 403);
        abort_unless($peca->vemDoPlanejamento()
            && $anexo->processo_peca_id === $peca->origem_processo_peca_id, 404);
        abort_unless(Storage::disk('local')->exists($anexo->arquivo_path), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($anexo->arquivo_path, $anexo->arquivo_nome);
    }

    public function download(Peca $peca): StreamedResponse
    {
        abort_unless($peca->podeBaixar(auth()->user()), 403);
        abort_unless($peca->arquivo_path && Storage::disk('local')->exists($peca->arquivo_path), 404);

        return Storage::disk('local')->download($peca->arquivo_path, $peca->arquivo_nome);
    }

    /** O documento de texto em PDF, com as assinaturas. O que vem do Planejamento sai do processo. */
    public function pdf(Peca $peca): Response
    {
        abort_unless($peca->podeBaixar(auth()->user()), 403);
        $doc = $peca->vemDoPlanejamento() ? $peca->origem : $peca;
        abort_unless($peca->tipo === 'modelo' && filled($doc?->conteudo), 404, 'Este documento não tem texto para gerar o PDF.');

        return response(DocumentoPdf::gerar($doc), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . DocumentoPdf::nomeArquivo($peca->rotulo) . '"',
        ]);
    }

    /** Os documentos escolhidos num ZIP: o texto em PDF, o arquivo como foi enviado. Só o que a pessoa pode baixar. */
    public function lote(Request $request): BinaryFileResponse
    {
        $user = auth()->user();
        $pecas = Peca::with('origem.anexos', 'assinaturasPartes')
            ->whereIn('id', array_map('intval', (array) $request->query('pecas', [])))
            ->orderBy('ordem')->get()
            ->filter(fn (Peca $p) => $p->podeBaixar($user))->values();

        $zipPath = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $disco = Storage::disk('local');

        foreach ($pecas as $n => $peca) {
            $doc = $peca->vemDoPlanejamento() ? $peca->origem : $peca;
            if ($peca->tipo === 'modelo' && filled($doc?->conteudo)) {
                $zip->addFromString(DocumentoPdf::nomeArquivo($peca->rotulo, $n + 1), DocumentoPdf::gerar($doc));
            } elseif ($peca->vemDoPlanejamento()) {
                foreach ($doc->anexos as $anexo) {
                    if ($disco->exists($anexo->arquivo_path)) {
                        $zip->addFile($disco->path($anexo->arquivo_path), sprintf('%02d-', $n + 1) . $anexo->arquivo_nome);
                    }
                }
            } elseif ($peca->arquivo_path && $disco->exists($peca->arquivo_path)) {
                $zip->addFile($disco->path($peca->arquivo_path), sprintf('%02d-', $n + 1) . $peca->arquivo_nome);
            }
        }

        abort_if($zip->numFiles === 0, 404, 'Nenhum documento disponível para baixar.');
        $zip->close();

        $nome = \Illuminate\Support\Str::slug($request->query('nome', 'documentos')) ?: 'documentos';

        return response()->download($zipPath, $nome . '.zip')->deleteFileAfterSend(true);
    }

    public function removerArquivo(Peca $peca): RedirectResponse
    {
        $this->autorizar($peca);
        abort_if($peca->vemDaAreaDaOsc(), 422, 'Este arquivo vem de "Arquivos da OSC": troque a versão lá ou envie outro arquivo aqui.');

        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $peca->update([
            'arquivo_path' => null,
            'arquivo_nome' => null,
            'tamanho'      => null,
            'mime_type'    => null,
        ]);

        return $this->voltarParaPeca($peca, 'Arquivo removido.');
    }
}
