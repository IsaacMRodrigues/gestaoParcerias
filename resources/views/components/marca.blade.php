{{-- Logotipo oficial da Prefeitura de São Gonçalo do Rio Abaixo.

     $variant:
       'color'  — fundo claro, logotipo nas cores originais.
       'branco' — fundo escuro. O logotipo tem texto em cinza-escuro, ilegível
                  sobre o verde, então é rebatido para branco sólido
                  (brightness-0 zera a cor, invert leva ao branco). Antes havia
                  um quadro branco atrás dele, que sobre a coluna verde lia como
                  um adesivo colado. --}}
{{-- O dimensionamento fica todo por conta de $class: quem chama pode fixar a
     altura ('h-9') ou mandar ocupar a largura disponível ('w-full h-auto'). A
     proporção se mantém sozinha, porque só uma das dimensões é definida. --}}
@props(['variant' => 'color', 'class' => 'h-9'])

<img src="https://pmsgra.net/logotipo.png" alt="Prefeitura de São Gonçalo do Rio Abaixo"
     class="{{ $class }} shrink-0 {{ $variant === 'branco' ? 'brightness-0 invert' : '' }}">
