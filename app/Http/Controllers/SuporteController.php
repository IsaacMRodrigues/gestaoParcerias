<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\ChamadoMensagem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Painel de suporte.
 *
 * Aberto a quem está logado, e só a quem está logado: é o próprio acesso ao
 * sistema que filtra quem escreve — não há formulário público a ser varrido.
 *
 * Duas leituras da mesma tela: quem abriu vê os seus chamados; quem tem a
 * permissão `suporte` (hoje a TI e a SCP) vê todos e responde. A categoria
 * ajuda a triar sem criar fila separada, para que nenhuma pergunta fique
 * esperando alguém reparar que caiu no lugar errado.
 */
class SuporteController extends Controller
{
    private function atende(): bool
    {
        return auth()->user()->can('suporte');
    }

    /** Vê o chamado quem o abriu e quem atende — mais ninguém. */
    private function autorizarVer(Chamado $chamado): void
    {
        abort_unless($this->atende() || $chamado->user_id === auth()->id(), 403,
            'Este chamado é de outra pessoa.');
    }

    public function index(Request $request): View
    {
        $atende = $this->atende();

        $chamados = Chamado::with('autor')
            ->unless($atende, fn ($q) => $q->where('user_id', auth()->id()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('suporte.index', [
            'chamados' => $chamados,
            'atende'   => $atende,
            'filtros'  => $request->only(['status', 'categoria']),
            'abertos'  => $atende ? Chamado::emAberto()->count() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'categoria'  => ['required', Rule::in(array_keys(Chamado::CATEGORIAS))],
            'assunto'    => ['required', 'string', 'max:160'],
            'mensagem'   => ['required', 'string'],
            'origem_url' => ['nullable', 'string', 'max:255'],
            'arquivo'    => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ], [
            'assunto.required'  => 'Dê um assunto ao chamado — é por ele que a equipe encontra você.',
            'mensagem.required' => 'Descreva a dúvida, o problema ou a sugestão.',
            'arquivo.max'       => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes'     => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        $user = auth()->user();

        $chamado = DB::transaction(function () use ($dados, $user, $request) {
            $chamado = Chamado::create([
                'numero'        => Chamado::proximoNumero(),
                'user_id'       => $user->id,
                'autor_nome'    => $user->name,
                'autor_vinculo' => Chamado::vinculoDe($user),
                'categoria'     => $dados['categoria'],
                'assunto'       => $dados['assunto'],
                'status'        => 'aberto',
                'origem_url'    => $dados['origem_url'] ?? null,
            ]);

            $this->registrarMensagem($chamado, $dados['mensagem'], false, $request->file('arquivo'));

            return $chamado;
        });

        return redirect()->route('suporte.show', $chamado)
            ->with('success', 'Chamado ' . $chamado->numero . ' aberto. A equipe responde por aqui mesmo.');
    }

    public function show(Chamado $chamado): View
    {
        $this->autorizarVer($chamado);

        $chamado->load(['autor', 'conta.roles', 'conta.osc', 'resolvidoPor', 'mensagens.autor']);
        $atende = $this->atende();

        return view('suporte.show', [
            'chamado'   => $chamado,
            'atende'    => $atende,
            'bloqueioSenha' => $atende && $chamado->ehPedidoDeSenha()
                ? $this->motivoParaNaoDefinirSenha($chamado->conta)
                : null,
            // A nota interna não sai da equipe: nem na tela, nem no HTML.
            'mensagens' => $chamado->mensagens->filter(fn ($m) => $atende || !$m->interna),
        ]);
    }

    public function responder(Request $request, Chamado $chamado): RedirectResponse
    {
        $this->autorizarVer($chamado);

        $dados = $request->validate([
            'mensagem' => ['required', 'string'],
            'interna'  => ['nullable', 'boolean'],
            'arquivo'  => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ], [
            'mensagem.required' => 'Escreva a resposta.',
        ]);

        // Nota interna é da equipe; de quem abriu, tudo é resposta.
        $interna = $this->atende() && (bool) ($dados['interna'] ?? false);

        $this->registrarMensagem($chamado, $dados['mensagem'], $interna, $request->file('arquivo'));

        return back()->with('success', $interna ? 'Nota interna registrada.' : 'Mensagem enviada.');
    }

    public function status(Request $request, Chamado $chamado): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(array_keys(Chamado::STATUS))],
        ]);

        // Encerrar é de quem atende. Quem abriu acompanha a situação e escreve
        // de volta se não ficou resolvido — e escrever reabre o chamado (ver
        // registrarMensagem), o que basta para nada morrer sem resposta.
        abort_unless($this->atende(), 403,
            'Quem encerra o chamado é a equipe de suporte.');

        $chamado->update([
            'status'        => $dados['status'],
            'resolvido_em'  => $dados['status'] === 'resolvido' ? now() : null,
            'resolvido_por' => $dados['status'] === 'resolvido' ? auth()->id() : null,
        ]);

        return back()->with('success', 'Chamado marcado como ' . Chamado::STATUS[$dados['status']] . '.');
    }

    /**
     * Define uma senha provisória para a conta do pedido de Acesso.
     *
     * A senha é gerada aqui, e não digitada por quem atende: sai aleatória, é
     * mostrada uma única vez (não fica em lugar nenhum, nem na conversa) e a
     * conta passa a exigir troca no próximo acesso — quem atende a conhece, e
     * a pessoa não deve ficar com uma senha que outro sabe.
     */
    public function senhaProvisoria(Chamado $chamado): RedirectResponse
    {
        abort_unless($this->atende(), 403, 'Quem define a senha é a equipe de suporte.');
        abort_unless($chamado->ehPedidoDeSenha(), 422, 'Este chamado não é um pedido de nova senha.');

        $conta = $chamado->conta;

        if ($motivo = $this->motivoParaNaoDefinirSenha($conta)) {
            abort(403, $motivo);
        }

        $senha = self::gerarSenhaProvisoria();

        DB::transaction(function () use ($chamado, $conta, $senha) {
            $conta->forceFill([
                'password'          => $senha,
                'deve_trocar_senha' => true,
                // Derruba o "lembrar de mim" de quem estivesse com a conta.
                'remember_token'    => null,
            ])->save();

            // O registro do que foi feito, sem a senha: nota interna, porque é
            // a equipe que precisa saber quem definiu e quando.
            $this->registrarMensagem($chamado,
                'Senha provisória definida para ' . $conta->name . ' (' . $conta->email . '). '
                .'A troca é obrigatória no próximo acesso.', true);

            if ($chamado->status === 'aberto') {
                $chamado->update(['status' => 'em_andamento', 'respondido_em' => now()]);
            }
        });

        return back()->with('senha_provisoria', $senha);
    }

    /**
     * Por que esta conta não pode ter a senha definida por quem está atendendo.
     *
     * Quem atende sem `cadastros` (a SCP) não mexe na senha de quem tem: sem
     * isto, definir a senha do administrador seria o atalho para entrar como
     * ele. E ninguém redefine a própria senha por aqui — para isso há o perfil.
     */
    private function motivoParaNaoDefinirSenha(?User $conta): ?string
    {
        $eu = auth()->user();

        if (!$conta) {
            return 'Nenhuma conta com o e-mail ou usuário informado.';
        }

        if ($conta->id === $eu->id) {
            return 'Para trocar a sua própria senha, use o seu perfil.';
        }

        if (($conta->can('cadastros') || $conta->hasRole('administrador_setorial')) && !$eu->can('cadastros')) {
            return 'Esta conta administra os cadastros do sistema: a senha dela só o TI redefine.';
        }

        return null;
    }

    /** Legível ao telefone: sem símbolos e sem os caracteres que se confundem (0/O, 1/l/I). */
    public static function gerarSenhaProvisoria(): string
    {
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $senha = '';

        for ($i = 0; $i < 10; $i++) {
            $senha .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return substr($senha, 0, 5) . '-' . substr($senha, 5);
    }

    public function baixarAnexo(Chamado $chamado, ChamadoMensagem $mensagem)
    {
        $this->autorizarVer($chamado);

        abort_unless($mensagem->chamado_id === $chamado->id, 404);
        abort_if($mensagem->interna && !$this->atende(), 404);
        abort_unless($mensagem->temArquivo() && Storage::disk('local')->exists($mensagem->arquivo_path), 404);

        return Storage::disk('local')->download($mensagem->arquivo_path, $mensagem->arquivo_nome);
    }

    /**
     * Grava a fala e move o chamado.
     *
     * A primeira resposta de quem atende tira o chamado de "aberto" sozinha —
     * ninguém precisa lembrar de mudar um seletor, e a fila do suporte passa a
     * dizer a verdade.
     */
    private function registrarMensagem(Chamado $chamado, string $texto, bool $interna, $arquivo = null): ChamadoMensagem
    {
        $user = auth()->user();

        $dados = [
            'user_id'       => $user->id,
            'autor_nome'    => $user->name,
            'autor_vinculo' => Chamado::vinculoDe($user),
            'mensagem'      => $texto,
            'interna'       => $interna,
        ];

        if ($arquivo) {
            $dados += [
                'arquivo_path'     => $arquivo->store("suporte/{$chamado->id}", 'local'),
                'arquivo_nome'     => $arquivo->getClientOriginalName(),
                'arquivo_mime'     => $arquivo->getMimeType(),
                'arquivo_tamanho'  => $arquivo->getSize(),
            ];
        }

        $mensagem = $chamado->mensagens()->create($dados);

        $daEquipe = $this->atende() && $chamado->user_id !== $user->id;

        if (!$interna && $daEquipe && $chamado->status === 'aberto') {
            $chamado->update(['status' => 'em_andamento', 'respondido_em' => $chamado->respondido_em ?? now()]);
        }

        // Quem abriu voltou a escrever num chamado dado por resolvido: é
        // continuação, não chamado novo.
        if (!$interna && !$daEquipe && $chamado->resolvido()) {
            $chamado->update(['status' => 'em_andamento', 'resolvido_em' => null, 'resolvido_por' => null]);
        }

        return $mensagem;
    }
}
