<?php

namespace App\Http\Controllers;

use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Rules\Recaptcha;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manifestação de interesse (PMIS, arts. 18 a 21 da Lei 13.019/2014) por formulário público: qualquer pessoa
 * física ou jurídica propõe, sem login. A OSC logada usa o mesmo formulário e fica vinculada. Quem enviou
 * acompanha pelo link pessoal (token); a lista pública mostra os textos, sem e-mail nem o PDF, que traz o
 * documento de identidade de quem assina.
 */
class ManifestacaoPublicaController extends Controller
{
    public function create(): View
    {
        return view('manifestacao-publica.create', [
            'orgaos' => Orgao::where('status', true)->orderBy('name')->get(),
            'osc'    => $this->oscQueManifesta(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $osc = $this->oscQueManifesta();

        $dados = $request->validate([
            'proponente_nome'   => $osc ? ['exclude'] : ['required', 'string', 'max:255'],
            'proponente_tipo'   => $osc ? ['exclude'] : ['required', Rule::in(array_keys(ManifestacaoInteresse::TIPOS_DE_PESSOA))],
            'proponente_email'  => ['required', 'email', 'max:255'],
            'orgao_id'          => ['required', Rule::exists('orgaos', 'id')->where('status', true)],
            'interesse_publico' => ['required', 'string', 'max:5000'],
            'diagnostico'       => ['required', 'string', 'max:5000'],
            'viabilidade'       => ['required', 'string', 'max:5000'],
            'arquivo'           => ['required', 'file', 'mimes:pdf', 'max:25600'],
            'g-recaptcha-response' => [new Recaptcha],
        ], [
            'proponente_nome.required'   => 'Informe o nome ou a razão social.',
            'proponente_tipo.required'   => 'Escolha o tipo de pessoa.',
            'proponente_email.required'  => 'Informe o e-mail: é por ele que chegam o protocolo e a decisão.',
            'orgao_id.required'          => 'Escolha a Secretaria a que a manifestação se destina.',
            'interesse_publico.required' => 'Indique o interesse público envolvido.',
            'diagnostico.required'       => 'Descreva o diagnóstico da realidade.',
            'viabilidade.required'       => 'Indique a viabilidade, os custos, os benefícios e os prazos.',
            'arquivo.required'           => 'Anexe a proposta em PDF.',
            'arquivo.mimes'              => 'O arquivo deve ser PDF.',
            'arquivo.max'                => 'O PDF pode ter no máximo 25 MB.',
        ]);

        $token = Str::random(48);
        $arquivo = $request->file('arquivo');
        $caminho = $arquivo->store("manifestacoes/{$token}", 'local');

        try {
            $manifestacao = DB::transaction(function () use ($dados, $osc, $token, $arquivo, $caminho) {
                $m = ManifestacaoInteresse::create([
                    'tipo'              => 'manifestacao',
                    'token'             => $token,
                    'osc_id'            => $osc?->id,
                    'proponente_nome'   => $osc?->name ?? $dados['proponente_nome'],
                    'proponente_tipo'   => $osc ? 'pj' : $dados['proponente_tipo'],
                    'proponente_email'  => $dados['proponente_email'],
                    'orgao_id'          => $dados['orgao_id'],
                    'interesse_publico' => $dados['interesse_publico'],
                    'diagnostico'       => $dados['diagnostico'],
                    'viabilidade'       => $dados['viabilidade'],
                    'arquivo_path'      => $caminho,
                    'arquivo_nome'      => $arquivo->getClientOriginalName(),
                    'arquivo_tamanho'   => $arquivo->getSize(),
                    'status'            => 'rascunho',
                ]);
                // Como a manifestação de sempre: vai direto à Unidade Gestora da Secretaria escolhida.
                $m->enviar(['status' => 'em_analise', 'setor_atual' => 'ug']);

                return $m;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($caminho);
            throw $e;
        }

        Avisos::manifestacaoRecebida($manifestacao);

        return redirect()->route('manifestacao.acompanhar', $manifestacao->token)->with('success',
            'Manifestação recebida com o protocolo nº ' . $manifestacao->protocolo . '. Enviamos o número e o link '
            . 'desta página para ' . $manifestacao->proponente_email . '.');
    }

    /** O link pessoal de quem enviou: situação, decisão e o próprio PDF. */
    public function acompanhar(string $token): View
    {
        return view('manifestacao-publica.acompanhar', ['manifestacao' => $this->pelaChave($token)]);
    }

    public function arquivo(string $token): StreamedResponse
    {
        $m = $this->pelaChave($token);
        abort_unless($m->arquivo_path && Storage::disk('local')->exists($m->arquivo_path), 404);

        return Storage::disk('local')->download($m->arquivo_path, $m->arquivo_nome);
    }

    /** Lista pública das manifestações recebidas (art. 20: a proposta se torna pública). */
    public function index(Request $request): View
    {
        $manifestacoes = ManifestacaoInteresse::with('orgao')
            ->doTipo('manifestacao')
            ->whereNotNull('interesse_publico')
            ->whereNotNull('protocolo')
            ->latest('submetida_em')
            ->paginate(15);

        return view('manifestacao-publica.index', compact('manifestacoes'));
    }

    public function show(string $ano, string $numero): View
    {
        $manifestacao = ManifestacaoInteresse::with('orgao')
            ->doTipo('manifestacao')
            ->whereNotNull('interesse_publico')
            ->where('protocolo', "{$ano}/{$numero}")
            ->firstOrFail();

        return view('manifestacao-publica.show', compact('manifestacao'));
    }

    private function pelaChave(string $token): ManifestacaoInteresse
    {
        return ManifestacaoInteresse::with('orgao')->where('token', $token)->doTipo('manifestacao')->firstOrFail();
    }

    /** A OSC logada, com a função de manifestar, assina pela organização; os demais, como qualquer pessoa. */
    private function oscQueManifesta(): ?\App\Models\Osc
    {
        $user = auth()->user();

        return $user?->ehRepresentanteOsc() && $user->can('osc_manifestacoes') ? $user->oscVinculada() : null;
    }
}
