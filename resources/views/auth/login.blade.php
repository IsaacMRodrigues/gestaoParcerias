<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1.5 w-full py-2.5" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username"
                          placeholder="nome@prefeitura.mg.gov.br" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline rounded
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                       href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block mt-1.5 w-full py-2.5"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

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
        É uma organização da sociedade civil?
        <a href="{{ route('portal.osc.create') }}" class="font-semibold text-brand-700 hover:underline">
            Cadastre sua OSC
        </a>
    </p>
</x-guest-layout>
