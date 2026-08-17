{{-- Abre a barra de comandos. Tem cara de campo de busca porque é assim que o
     usuário procura por "onde eu pesquiso", e mostra o atalho ao lado para que
     na segunda vez ele use o teclado. No celular vira só a lupa. --}}
<button type="button" @click="$dispatch('abrir-paleta')"
        aria-label="Buscar no sistema (Ctrl+K)"
        class="flex items-center gap-2 shrink-0 rounded-lg border border-gray-300 bg-gray-50 text-gray-500
               p-2 sm:pl-3 sm:pr-2 sm:py-2 sm:w-64
               hover:bg-white hover:border-gray-400 hover:text-gray-700
               focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
    </svg>
    <span class="hidden sm:block text-sm">Buscar…</span>
    <span class="hidden sm:flex ml-auto items-center gap-0.5 shrink-0">
        <kbd class="kbd">ctrl</kbd><kbd class="kbd">k</kbd>
    </span>
</button>
