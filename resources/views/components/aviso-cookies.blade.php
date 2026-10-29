{{-- Aviso de cookies: o sistema só usa cookies necessários, então o aviso informa (não há o que recusar). "Entendi"
     grava pgp_aviso_cookies por um ano, fora da criptografia de cookies (bootstrap/app.php), e o aviso não volta. --}}
@unless(request()->cookie('pgp_aviso_cookies'))
    <div x-data="{ aberto: true }" x-show="aberto" role="region" aria-label="Aviso de cookies"
         class="fixed z-50 rounded-xl border border-gray-200 bg-white shadow-lg p-4 text-sm text-gray-700"
         style="left:4.75rem;right:1rem;bottom:1rem;max-width:28rem;margin-left:auto;">
        <p>
            Este sistema usa apenas <strong>cookies necessários</strong> para manter você conectado e proteger os
            formulários — nenhum de propaganda ou de estatística.
            <a href="{{ route('cookies.index') }}" class="font-semibold text-brand-700 hover:underline">Política de Cookies</a>.
        </p>
        <div class="mt-3 flex justify-end">
            <button type="button" class="btn btn-primary btn-sm"
                    @click="document.cookie = 'pgp_aviso_cookies=1; max-age=31536000; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : ''); aberto = false">
                Entendi
            </button>
        </div>
    </div>
@endunless
