import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Classes montadas em tempo de execução (ex.: bg-{{ $color }}-100, a partir
    // dos mapas STATUS_COLORS dos models) não aparecem no código-fonte e seriam
    // removidas na purga. O safelist garante que as famílias usadas existam.
    safelist: [
        {
            pattern: /(bg|text|border)-(gray|green|red|blue|amber|yellow|purple|brand|accent)-(50|100|200|300|600|700|800)/,
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Identidade visual da Prefeitura de São Gonçalo do Rio Abaixo.
                // brand-600 é o verde principal; brand-700 o verde escuro;
                // brand-50 o verde claro. Demais tons interpolados.
                brand: {
                    50:  '#E6F9F0',
                    100: '#C7F0DF',
                    200: '#93E3C1',
                    300: '#5AD29F',
                    400: '#22BE7B',
                    500: '#00B061',
                    600: '#00A859',
                    700: '#008A48',
                    800: '#006D3A',
                    900: '#005A30',
                },
                // Laranja de destaque (accent-500 principal, 700 escuro, 50 claro).
                accent: {
                    50:  '#FEF3EC',
                    100: '#FDE3D1',
                    200: '#FAC5A3',
                    300: '#F6A171',
                    400: '#F28C4F',
                    500: '#EE7736',
                    600: '#E06A2C',
                    700: '#D4622A',
                    800: '#A94D21',
                    900: '#8A3F1B',
                },
            },
        },
    },

    plugins: [forms],
};
