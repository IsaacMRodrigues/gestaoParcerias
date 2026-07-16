@props(['label', 'hint' => 'Em breve'])

{{-- Etapa do ciclo ainda sem tela própria: mostra a trilha completa, sem link.
     Fica oculta abaixo de xl para não apertar a navbar em telas médias. --}}
<span {{ $attributes->merge(['class' => 'hidden xl:inline-flex items-center whitespace-nowrap px-1 pt-1 text-sm font-medium text-gray-300 border-b-2 border-transparent cursor-not-allowed select-none']) }}
      title="{{ $hint }}">
    {{ $label }}
    <span class="ml-1 text-[10px] font-normal text-gray-300">•</span>
</span>
