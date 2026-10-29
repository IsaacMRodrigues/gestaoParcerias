{{-- "Não sou um robô" (reCAPTCHA v2) dos formulários públicos; o servidor confere em App\Rules\Recaptcha. Sem as chaves
     (desenvolvimento e testes), só o lugar do erro. --}}
@if(\App\Rules\Recaptcha::configurado())
    <div {{ $attributes }}>
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
        <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-1" />
        <p class="text-xs text-gray-400 mt-1">A verificação usa o reCAPTCHA do Google, que grava cookies próprios
            (<a href="{{ route('cookies.index') }}" class="hover:underline">Política de Cookies</a>).</p>
    </div>
    @once
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endonce
@else
    <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-1" />
@endif
