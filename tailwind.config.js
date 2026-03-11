/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Http/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#f0f4ff',
                    100: '#dce6ff',
                    200: '#b9cdff',
                    300: '#8aabff',
                    400: '#567dff',
                    500: '#2952ff',
                    600: '#1a3ef5',
                    700: '#132de1',
                    800: '#1526b6',
                    900: '#17268f',
                    950: '#111756',
                },
                slate: {
                    925: '#0d1321',
                },
            },
            fontFamily: {
                sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui'],
                mono: ['JetBrains Mono', 'ui-monospace'],
            },
            boxShadow: {
                'card': '0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08)',
                'card-hover': '0 4px 12px 0 rgb(0 0 0 / 0.12)',
            },
        },
    },
    plugins: [],
};
