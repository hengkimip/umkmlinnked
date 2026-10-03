import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            // Selaras dengan token di resources/css/direktori-layout.css
            colors: {
                navy: {
                    950: '#0a1f3d',
                    900: '#0f2a52',
                    800: '#14356a',
                    700: '#1a4180',
                },
                gold: {
                    500: '#d4a017',
                    400: '#e8b923',
                },
            },
        },
    },

    plugins: [forms],
};
