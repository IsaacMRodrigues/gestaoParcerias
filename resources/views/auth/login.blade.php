{{-- Uma tela só para os dois públicos: ?acesso=osc é a entrada da OSC, com o cadastro de OSC; sem ele, a da Prefeitura. --}}
@php $daOsc = request('acesso') === 'osc'; @endphp
<x-guest-layout>
    @if($daOsc)
        <x-slot name="titulo">Acessar o Portal da OSC</x-slot>
        <x-slot name="subtitulo">Entre com a conta da sua organização.</x-slot>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Um campo para e-mail ou nome de usuário, por isso type="text". --}}
        <div>
            <x-input-label for="login" value="E-mail ou usuário" />
            <x-text-input id="login" class="block mt-1.5 w-full py-2.5" type="text" name="login"
                          :value="old('login')" required autofocus autocomplete="username"
                          placeholder="{{ $daOsc ? 'seu@email.com' : 'nome@saogoncalo.mg.gov.br' }}" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline rounded
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                       href="{{ route('password.request') }}">
                        Esqueci minha senha
                    </a>
                @endif
            </div>

            <x-input-senha id="password" class="mt-1.5" required placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex items-center gap-2.5 cursor-pointer">
            <input id="remember_me" type="checkbox"
                   class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
            <span class="text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full !py-3 text-base">
            {{ __('Log in') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </x-primary-button>
    </form>

    <p class="text-sm text-gray-500 text-center mt-6">
        @if($daOsc)
            Sua organização ainda não tem cadastro?
            <a href="{{ route('portal.osc.create') }}" class="font-semibold text-brand-700 hover:underline">
                Cadastre sua OSC
            </a>
        @else
            É servidor da Prefeitura?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">
                Solicite seu acesso
            </a>
        @endif
    </p>
    <p class="text-xs text-gray-400 text-center mt-3">
        O uso do sistema segue os <a href="{{ route('termos.index') }}" class="hover:text-brand-700 hover:underline">Termos de Uso</a>
        e a <a href="{{ route('cookies.index') }}" class="hover:text-brand-700 hover:underline">Política de Cookies</a>.
    </p>
</x-guest-layout>
