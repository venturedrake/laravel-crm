const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.copy(
    'node_modules/bootstrap-multiselect/dist/js/bootstrap-multiselect.min.js',
    'resources/assets/libs/bootstrap-multiselect/bootstrap-multiselect.min.js'
)

mix.js('resources/js/app.js', 'resources/assets/js')
    .sass('resources/sass/app.scss', 'resources/assets/css')
    .sass('resources/sass/document.scss', 'resources/assets/css');
