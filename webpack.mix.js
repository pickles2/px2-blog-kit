const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
	.webpackConfig({})


	// --------------------------------------
	// CSS
	.sass('src_gulp/styles/pagelist.css.scss', 'resources/styles/pagelist.css')
	.copy('src_gulp/styles/pagelist.css.scss', 'broccoli_modules/list-page/article-list/module.css.scss')
;
