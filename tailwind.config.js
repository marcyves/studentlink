import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                surface: '#f8f9ff',
                'surface-container': '#e5eeff',
                background: '#f8f9ff',
                primary: {
                    DEFAULT: '#004ac6',
                    container: '#2563eb',
                },
                secondary: {
                    DEFAULT: '#712ae2',
                    container: '#8a4cfc',
                },
                tertiary: {
                    DEFAULT: '#006242',
                    container: '#007d55',
                },
                'on-surface': '#0b1c30',
            },
            borderRadius: {
                studentlink: '0.5rem',
            },
        },
    },

    plugins: [forms],
};
