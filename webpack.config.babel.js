import path from 'path';

const DIST_PATH = path.resolve( './dist/js' );

module.exports = {
	cache: true,
	devtool: 'source-map',
	entry: {
		'admin-external-connection': './assets/js/admin-external-connection.js',
		'admin-pull': './assets/js/admin-pull.js',
		'admin-distributed-post': './assets/js/admin-distributed-post.js',
		push: './assets/js/push.js',
		'gutenberg-syndicated-post': './assets/js/gutenberg-syndicated-post.js',
		'gutenberg-plugin': './assets/js/gutenberg-plugin.js',
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
	mode: 'production',

	externals: {
		jquery: 'jQuery',
		underscores: '_',
		window: 'window',
		wp: 'wp'
	},
	stats: { colors: true },
};
