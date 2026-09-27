import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/guest.css', 'resources/js/app.js'],
            refresh: true,
            // Downloaded at build time and served from /build, so text renders the same offline.
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700, 800],
                    // No preloading: each @font-face has a unicode-range, so browsers only fetch
                    // the Latin files a page actually uses (Bunny ships every script subset).
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
