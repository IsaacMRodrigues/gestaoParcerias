@props(['label', 'active' => false])

{{-- Item de etapa da navbar com submenu (ver x-nav-link para itens simples). --}}
<div x-data="{ open: false }" class="relative flex items-center">
    <button type="button" @click="open = !open" @click.outside="open = false"
            class="inline-flex items-center gap-1 whitespace-nowrap px-1 pt-1 text-sm border-b-2 transition duration-150 ease-in-out
                   {{ $active
                        ? 'border-brand-600 text-gray-900 font-semibold'
                        : 'border-transparent text-gray-500 font-medium hover:text-gray-900 hover:border-brand-300' }}">
        {{ $label }}
        @isset($badge){{ $badge }}@endisset
        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
    <div x-show="open" x-transition style="display: none"
         class="absolute top-full left-0 mt-1 w-60 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-1">
        {{ $slot }}
    </div>
</div>
