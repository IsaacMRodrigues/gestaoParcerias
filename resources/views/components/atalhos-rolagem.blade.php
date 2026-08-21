{{-- Atalhos de rolagem: topo e fim da página.

     A tela da Celebração é longa — trilha de 15 etapas, histórico e um
     checklist de 18 documentos. Depois de descer até o último item, voltar ao
     trâmite (onde ficam os botões de encaminhar e devolver) era rolagem cega.

     Cada seta some quando não teria efeito: no topo da página não há "subir",
     no fim não há "descer". Fica fora do fluxo do texto (aria-hidden não: são
     botões de verdade, com rótulo, para quem navega por teclado ou leitor). --}}
<div x-data="{
        noTopo: true,
        noFim: false,
        medir() {
            const doc = document.documentElement;
            this.noTopo = window.scrollY < 120;
            this.noFim  = window.scrollY + window.innerHeight >= doc.scrollHeight - 120;
        },
        ir(destino) {
            window.scrollTo({ top: destino === 'fim' ? document.documentElement.scrollHeight : 0, behavior: 'smooth' });
        },
     }"
     x-init="medir()"
     @scroll.window.passive="medir()"
     @resize.window.passive="medir()"
     {{-- Escondido no celular: com a coluna estreita, o botão flutuante cobriria
          o próprio conteúdo que se quer ler. --}}
     class="hidden lg:flex fixed right-5 top-1/2 -translate-y-1/2 z-30 flex-col gap-2 print:hidden">

    <button type="button" @click="ir('topo')" x-show="!noTopo" x-cloak
            title="Ir para o topo da página" aria-label="Ir para o topo da página"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-white text-gray-500
                   border border-gray-200 shadow-sm hover:text-brand-700 hover:border-brand-300
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/>
        </svg>
    </button>

    <button type="button" @click="ir('fim')" x-show="!noFim" x-cloak
            title="Ir para o fim da página" aria-label="Ir para o fim da página"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-white text-gray-500
                   border border-gray-200 shadow-sm hover:text-brand-700 hover:border-brand-300
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>
</div>
