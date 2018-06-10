let mix = require('laravel-mix');

let tailwindcss = require('tailwindcss');

require('laravel-mix-purgecss');

mix.postCss('css/base.css', 'css/my-theme.css')
      .options({
        postCss: [tailwindcss('tailwind.js')],
        processCssUrls: false,
    })
    .js([
        'js/base.js',
    ], 'js/my-theme.js');

mix.disableSuccessNotifications();

if (mix.inProduction()) {
    mix.purgeCss({
        enabled: true,
        globs: [
            path.join(__dirname, 'layouts/*.html'),
            path.join(__dirname, 'templates/*.html'),
            path.join(__dirname, 'templates/**/*.html'),
            path.join(__dirname, 'partials/*.html'),
            path.join(__dirname, 'partials/**/*.html'),
            path.join(__dirname, 'js/**.js'),
            path.join(__dirname, 'img/**.svg'),
        ],
        extensions: ['html', 'js', 'php', 'vue', 'svg'],
    })
};