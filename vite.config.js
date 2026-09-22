import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * One family per theme. A browser downloads only the faces the page
             * actually paints with, so the themes nobody selected cost nothing.
             */
            fonts: [
                bunny('Instrument Sans', { weights: [400, 500, 600, 700] }),
                bunny('IBM Plex Sans', { weights: [400, 500, 600] }),
                bunny('IBM Plex Serif', { weights: [400, 600] }),
                bunny('IBM Plex Mono', { weights: [400, 500] }),
                bunny('Fredoka', { weights: [400, 500, 600] }),
                bunny('Inter', { weights: [400, 500, 600] }),
                bunny('JetBrains Mono', { weights: [400, 500, 700] }),
                bunny('Space Grotesk', { weights: [400, 500, 600, 700] }),
                bunny('Outfit', { weights: [400, 500, 600, 700] }),
                bunny('Lora', { weights: [400, 500, 600, 700] }),
                bunny('Caveat', { weights: [400, 500, 600, 700] }),
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
