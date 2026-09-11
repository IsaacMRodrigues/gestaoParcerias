{{-- Campo de senha com o olho de exibir.

     Digita-se senha às cegas, e no celular ou num teclado desconhecido o erro
     de digitação só aparece depois de a entrada ser recusada — com a conta mais
     perto do bloqueio a cada tentativa. O olho deixa conferir antes de enviar.

     Começa oculto, sempre: a senha à mostra por padrão exporia quem abre a tela
     de entrada diante de outra pessoa, que é justamente o caso mais comum num
     balcão de repartição. --}}
@props(['id', 'name' => null, 'autocomplete' => 'current-password'])

@php $nome = $name ?? $id; @endphp

<div x-data="{ visivel: false }" class="relative">
    {{-- pr-11 reserva o lugar do botão: sem isso o texto da senha passa por
         baixo do ícone nas senhas longas. --}}
    <x-text-input :id="$id" :name="$nome"
                  ::type="visivel ? 'text' : 'password'"
                  type="password"
                  :autocomplete="$autocomplete"
                  {{ $attributes->merge(['class' => 'block w-full py-2.5 pr-11']) }} />

    <button type="button" @click="visivel = !visivel" tabindex="-1"
            :aria-label="visivel ? 'Ocultar a senha' : 'Mostrar a senha'"
            :aria-pressed="visivel ? 'true' : 'false'"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400
                   hover:text-gray-700 transition rounded-r-lg
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        {{-- Olho aberto quando está oculta (clique para ver); olho cortado
             quando está à mostra (clique para esconder). --}}
        <svg x-show="!visivel" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
        </svg>
        <svg x-show="visivel" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
        </svg>
    </button>
</div>
