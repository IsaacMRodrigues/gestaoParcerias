@props(['label', 'hint' => 'Em breve'])

{{-- Etapa do ciclo exibida sem link: ou o usuário não tem acesso, ou a tela
     ainda não existe. Mantém a trilha completa visível na navbar. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap px-1 pt-1 text-sm font-medium text-gray-300 border-b-2 border-transparent cursor-not-allowed select-none']) }}
      title="{{ $hint }}">
    {{ $label }}
</span>
