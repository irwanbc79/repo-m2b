import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
            colors: {
                // Warna M2B berdasarkan Logo
                m2b: {
                    primary: '#0F2C59',   // Navy Blue Gelap (Dominan - Trust)
                    secondary: '#1e3a8a', // Blue Navy Terang (Hover/Active)
                    accent: '#B91C1C',    // Maroon/Merah (Action/Button)
                    light: '#F8FAFC',     // Background abu sangat muda
                }
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};