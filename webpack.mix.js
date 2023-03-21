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
	.webpackConfig({
		module: {
			rules:[
				{
					test: /\.txt$/i,
					use: ['raw-loader'],
				},
				{
					test:/\.twig$/,
					use:['twig-loader']
				},
				{
					test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/i,
					type: "asset/inline"
				}
			]
		},
		resolve: {
			fallback: {
				"fs": false,
				"path": false,
				"crypto": false,
				"stream": false,
			}
		}
	})


	// --------------------------------------
	// CSS
	.sass('src_frontend/styles/pagelist.css.scss', 'resources/styles/pagelist.css')
	.copy('src_frontend/styles/pagelist.css.scss', 'broccoli_modules/blog-kit/list-page/article-list/module.css.scss')

	// --------------------------------------
	// Custom Console Extension: blog-kit
	.js('src_frontend/customConsole/blogKit.js', 'customConsole/frontend/blogKit.js')
	.sass('src_frontend/customConsole/blogKit.scss', 'customConsole/frontend/blogKit.css')
;
