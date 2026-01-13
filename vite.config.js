import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],

    // TANPA BUILD – langsung serve file mentah
    build: {
        minify: false,
        sourcemap: false,
        target: 'es2018',
        // hindari esbuild
        rollupOptions: {
            output: {
                manualChunks: undefined
            }
        }
    },

    esbuild: false, // MATIKAN ESBUILD total
});
