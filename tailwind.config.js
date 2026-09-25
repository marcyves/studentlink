import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                surface: 'rgb(var(--sl-surface) / <alpha-value>)',
                'surface-container': 'rgb(var(--sl-surface-container) / <alpha-value>)',
                card: 'rgb(var(--sl-card) / <alpha-value>)',
                background: 'rgb(var(--sl-surface) / <alpha-value>)',
                primary: {
                    DEFAULT: 'rgb(var(--sl-primary) / <alpha-value>)',
                    container: 'rgb(var(--sl-primary-container) / <alpha-value>)',
                },
                secondary: {
                    DEFAULT: 'rgb(var(--sl-secondary) / <alpha-value>)',
                    container: 'rgb(var(--sl-secondary-container) / <alpha-value>)',
                },
                tertiary: {
                    DEFAULT: 'rgb(var(--sl-tertiary) / <alpha-value>)',
                    container: 'rgb(var(--sl-tertiary-container) / <alpha-value>)',
                },
                'on-surface': 'rgb(var(--sl-on) / <alpha-value>)',
            },
            borderRadius: {
                studentlink: '0.5rem',
            },
        },
    },

    plugins: [forms],
};
