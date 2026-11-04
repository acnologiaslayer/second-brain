import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * "Ivory Signal" theme. Console-style UI in the spirit of Arcanum:
 * ivory paper canvas, ink text, hairline rules, square corners, mono type,
 * a single signal-orange accent. Colour carries meaning (accent / status) only.
 * The zinc + brand scales are remapped so every view inherits the palette.
 */
const ink = {
    50: '#fbf8f1',  // canvas (ivory)
    100: '#f3eee2', // raised / hover
    200: '#e3dccb', // hairline
    300: '#cfc6b1',
    400: '#a39a86', // dim
    500: '#7a7262', // muted
    600: '#5a5346',
    700: '#3d382f',
    800: '#26231d',
    900: '#16140f', // ink
    950: '#0c0b08',
};
const signal = {
    50: '#fff4ec',
    100: '#ffe4cf',
    200: '#ffc69c',
    300: '#ffa05f',
    400: '#ff8433',
    500: '#f2650f', // accent
    600: '#d9540a',
    700: '#b3440a',
    800: '#8a3609',
    900: '#6b2b0a',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            colors: {
                zinc: ink, gray: ink,
                brand: signal, orange: signal,
                violet: signal, // no purple "AI" tint anywhere
                paper: '#fffdf8',
                ok: '#2f9e5b', warn: '#c98a00', err: '#d33a2c',
            },
            borderRadius: { none: '0', sm: '1px', DEFAULT: '2px', md: '2px', lg: '3px', xl: '3px', '2xl': '4px', full: '9999px' },
            boxShadow: {
                sm: '0 1px 0 0 rgba(22,20,15,0.04)',
                DEFAULT: '2px 2px 0 0 rgba(22,20,15,0.06)',
                md: '3px 3px 0 0 rgba(22,20,15,0.08)',
                xl: '6px 6px 0 0 rgba(22,20,15,0.10)',
            },
            letterSpacing: { label: '0.18em' },
        },
    },
    plugins: [forms],
};
