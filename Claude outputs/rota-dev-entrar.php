/*
|--------------------------------------------------------------------------
| Atalho de desenvolvimento — troca de usuário sem senha
|--------------------------------------------------------------------------
| Existe apenas quando APP_ENV=local: fora daí o bloco não é executado, as
| rotas nem chegam a ser registradas e as URLs respondem 404. Serve para
| percorrer o fluxo entre setores (UG, SCP, SEPLAN, PJ, OSC) em teste sem
| ficar entrando e saindo do sistema.
|
|   GET /dev/entrar          lista os usuários cadastrados
|   GET /dev/entrar/{user}   autentica como o usuário informado
*/
if (app()->environment('local')) {
    Route::get('dev/entrar', function () {
        $linhas = \App\Models\User::query()
            ->orderBy('setor')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => sprintf(
                '<tr><td><a href="/dev/entrar/%d">%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $u->id,
                e($u->name),
                e($u->email),
                e($u->setorLabel()),
                e($u->getRoleNames()->implode(', ')),
            ))
            ->implode('');

        return response(
            '<!doctype html><meta charset="utf-8"><title>Entrar como…</title>'
            .'<style>body{font:14px system-ui;margin:2rem}h1{font-size:1.25rem}'
            .'td{padding:.25rem .75rem;border-bottom:1px solid #eee}</style>'
            .'<h1>Entrar como…</h1>'
            .'<p>Atalho de desenvolvimento — disponível somente com APP_ENV=local.</p>'
            .'<table>'.$linhas.'</table>'
        );
    })->name('dev.entrar.lista');

    Route::get('dev/entrar/{user}', function (\App\Models\User $user) {
        \Illuminate\Support\Facades\Auth::login($user);
        request()->session()->regenerate();

        // Mesma régua do AuthenticatedSessionController: quem é da OSC vai
        // para o portal, o pessoal da Administração para o painel interno.
        return $user->temAcessoInterno()
            ? redirect()->route('dashboard')
            : redirect()->route('portal.index');
    })->name('dev.entrar');
}
