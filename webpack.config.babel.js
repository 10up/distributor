import path from 'path'
import webpack from 'webpack'
import UglifyJSPlugin from 'uglifyjs-webpack-plugin'

const DIST_PATH = path.resolve('./dist/js');

module.exports = {
	cache: true,
	devtool: 'source-map',
	entry: {
		'admin-external-connection': './assets/js/admin-external-connection.js',
		'admin-pull': './assets/js/admin-pull.js',
		'admin-distributed-post': './assets/js/admin-distributed-post.js',
		push: './assets/js/push.js',
		'gutenberg-syndicated-post': './assets/js/gutenberg-syndicated-post.js',
		'gutenberg-status-plugin': './assets/js/gutenberg-status-plugin.js',
	},
	output: {
		path: DIST_PATH,
		filename: '[name].min.js',
	},
	resolve: {
		modules: ['node_modules'],
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				use: [{
					loader: 'babel-loader'
				}]
			},
			{
				test: /\.js$/,
				loader: 'eslint-loader',
				query: {
					configFile: './.eslintrc.json'
				}
			}
		]
	},
	plugins: [
	    new webpack.NoEmitOnErrorsPlugin(),
		new UglifyJSPlugin( {
			sourceMap: true,
			compress: {
				drop_debugger: false
			},
			uglifyOptions: {
				output: {
					comments: false
				}
			}
		} )
	],
	externals: {
		jquery: 'jQuery',
		underscores: '_',
		window: 'window',
		wp: 'wp'
	},
	stats: { colors: true },
}
