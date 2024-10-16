const CopyPlugin = require( 'copy-webpack-plugin' );
const { resolve } = require( 'path' );
module.exports = {
	entry: {},
	output: {
		path: resolve( process.cwd(), 'release' ),
		clean: true,
	},
	plugins: [
		new CopyPlugin( {
			patterns: [
				{ from: 'readme.txt', to: './' },
				{ from: 'uninstall.php', to: './' },
				{ from: 'README.md', to: './' },
				{ from: 'CHANGELOG.md', to: './' },
				{ from: 'composer.json', to: './' },
				{ from: 'distributor.php', to: './' },
				{ from: '.github/workflows/*', to: './' },
				{ from: '.gitattributes', to: './' },
				{ from: 'assets/img/*', to: './' },
				{ from: 'dist/**/*', to: './' },
				{ from: 'includes/**/*', to: './' },
				{ from: 'lang/**/*', to: './' },
				{ from: 'templates/**/*', to: './' },
				{
					from: 'vendor/yahnis-elsts/plugin-update-checker/**/*',
					to: './',
				},
			],
		} ),
	],
};
