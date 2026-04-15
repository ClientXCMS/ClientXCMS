import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { glob } from "glob";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...glob.sync('resources/themes/*/css/*.scss'),
                ...glob.sync('resources/themes/*/js/*.js'),
                ...glob.sync('resources/global/js/*.js'),
                ...glob.sync('resources/global/css/*.css'),
                ...glob.sync('resources/global/css/*.scss'),
                ...glob.sync('resources/global/js/admin/*.js'),
                ...glob.sync('resources/svg/*.svg'),
                ...glob.sync('resources/themes/*/js/*.js'),
                ...glob.sync('addons/*/resources/js/*.js'),
            ],
            refresh: true,
        }),
    ],
});
