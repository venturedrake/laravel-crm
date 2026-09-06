import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            buildDirectory: 'vendor/laravel-crm',
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        /*outDir: 'resources/build',*/
        // `public/vendor/laravel-crm/` is build output only. This wipes the
        // whole directory on every build, including any file the build did
        // not produce — hand-authored artwork committed there is deleted
        // silently. Static assets belong in `resources/assets/`, which
        // publishes to the same destination and the build never touches.
        emptyOutDir: true,
    }
});
