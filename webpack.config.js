const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const { resolve } = require( 'path' );

/**
 * Generate CSS file name.
 *
 * CSS only entry points are indicated with the suffix `-css` in the entry. This
 * suffix is removed from the file name to avoid the tortologist `-css.css`.
 *
 * In some cases the CSS file name differs from the JavaScript filename. In a
 * possibly foolhardy attempt to maintain backward compatibility, these values are
 * hard coded here.
 *
 * @param {Object} pathData Entry point path data.
 * @return {string} Resolved file name.
 */
const cssFileName = ( pathData ) => {
	let name = pathData.chunk.name;
	name = name.replace( /\-css$/, '' );

	// Backward compatibility for divergent file names.
	switch ( name ) {
		case 'admin-pull':
			name = 'admin-pull-table';
			break;
	}

	return `css/${ name }.min.css`;
};

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		filename: 'js/[name].min.js',
		path: resolve( process.cwd(), 'dist' ),
	},
	entry: {
		// ...defaultConfig.entry(),
		'admin-external-connection': resolve(
			process.cwd(),
			'assets/js',
			'admin-external-connection.js'
		),
		'admin-pull': resolve( process.cwd(), 'assets/js', 'admin-pull.js' ),
		'admin-distributed-post': resolve(
			process.cwd(),
			'assets/js',
			'admin-distributed-post.js'
		),
		push: resolve( process.cwd(), 'assets/js', 'push.js' ),
		'gutenberg-syndicated-post': resolve(
			process.cwd(),
			'assets/js',
			'gutenberg-syndicated-post.js'
		),
		'gutenberg-plugin': resolve(
			process.cwd(),
			'assets/js',
			'gutenberg-plugin.js'
		),

		// CSS Only
		'admin-css': resolve( process.cwd(), 'assets/css', 'admin.css' ),
		'admin-site-health-css': resolve(
			process.cwd(),
			'assets/css',
			'admin-site-health.css'
		),
		'admin-external-connections-css': resolve(
			process.cwd(),
			'assets/css',
			'admin-external-connections.css'
		), // Note the plural.
		'admin-settings-css': resolve(
			process.cwd(),
			'assets/css',
			'admin-settings.css'
		),
		'gutenberg-syndicated-post-css': resolve(
			process.cwd(),
			'assets/css',
			'gutenberg-syndicated-post.scss'
		),
		'admin-syndicated-post-css': resolve(
			process.cwd(),
			'assets/css',
			'admin-syndicated-post.scss'
		),
		'admin-edit-table-css': resolve(
			process.cwd(),
			'assets/css',
			'admin-edit-table.css'
		),
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !==
					'DependencyExtractionWebpackPlugin' &&
				plugin.constructor.name !== 'MiniCssExtractPlugin'
		),
		new MiniCSSExtractPlugin( { filename: cssFileName } ),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			combineAssets: false,
			requestToExternal( request ) {
				if ( request === 'underscore' ) {
					return '_';
				}
			},
		} ),
	],
};
