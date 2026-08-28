<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'string', não 'email': o campo aceita as duas formas de entrar.
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Um campo só, duas formas de entrar: quem digita um endereço é
        // procurado por `email`; o resto, pelo nome de usuário. Sem isto, a
        // conta de administração — que não tem e-mail próprio — não entraria.
        $credenciais = [
            $this->colunaDeAcesso() => $this->input('login'),
            'password'              => $this->input('password'),
        ];

        if (! Auth::attempt($credenciais, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Bloqueia cadastros pendentes de aprovação, recusados ou inativos.
        $user = Auth::user();
        if (! $user->podeAutenticar()) {
            $mensagem = $user->mensagemBloqueioLogin();
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => $mensagem,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }

    /**
     * Por qual coluna procurar quem está tentando entrar.
     *
     * Pelo nome de usuário quando ele existe; senão, pelo e-mail. Antes a
     * escolha vinha do formato do que foi digitado (`FILTER_VALIDATE_EMAIL`), e
     * isso dependia de sorte: um login com cara de endereço — "admin@parcerias"
     * — só não caía na coluna errada porque o filtro do PHP recusa domínio sem
     * ponto. Bastaria alguém cadastrar "admin@parcerias.net" para a conta
     * sumir do login sem explicação. Uma consulta a mais, e não se erra.
     */
    private function colunaDeAcesso(): string
    {
        return \App\Models\User::where('login', $this->input('login'))->exists()
            ? 'login'
            : 'email';
    }
}
