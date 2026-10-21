{{-- Volta à tela anterior do sistema; sem ela (link aberto direto, outra aba), vai ao início da área. --}}
@props(['inicio'])

<button type="button"
        onclick="if (document.referrer.indexOf(location.origin + '/') === 0 && history.length > 1) { history.back(); } else { location.href = @js($inicio); }"
        aria-label="Voltar à tela anterior"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 shrink-0 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-900 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500']) }}>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    <span class="hidden sm:inline">Voltar</span>
</button>
