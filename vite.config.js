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
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        sourcemap: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios', 'alpinejs'],
                    echo: ['laravel-echo', 'pusher-js'],
                },
            },
        },
    },
});