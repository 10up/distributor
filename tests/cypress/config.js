const { defineConfig } = require( 'cypress' );
const path = require( 'path' );

// Resolve the package directory
const wpEnvPackagePath = require.resolve( '@wordpress/env/package.json' );
const wpEnvLibPath = path.join( path.dirname( wpEnvPackagePath ), 'lib' );

// Directly require the files using their resolved paths
const { loadConfig } = require(
	path.join( wpEnvLibPath, 'config', 'index.js' )
);
const getCacheDirectory = require(
	path.join( wpEnvLibPath, 'config', 'get-cache-directory.js' )
);

module.exports = defineConfig( {
	chromeWebSecurity: false,
	fixturesFolder: 'tests/cypress/fixtures',
	screenshotsFolder: 'tests/cypress/screenshots',
	videosFolder: 'tests/cypress/videos',
	downloadsFolder: 'tests/cypress/downloads',
	video: true,
	reporter: 'mochawesome',
	reporterOptions: {
		mochaFile: 'mochawesome-[name]',
		reportDir: __dirname + '/reports',
		overwrite: false,
		html: false,
		json: true,
	},
	retries: {
		runMode: 2,
		openMode: 0,
	},
	e2e: {
		setupNodeEvents( on, config ) {
			return setBaseUrl( on, config );
		},
		specPattern: 'tests/cypress/e2e/**/*.test.{js,jsx,ts,tsx}',
		supportFile: 'tests/cypress/support/e2e.js',
		defaultCommandTimeout: 20000,
		experimentalRunAllSpecs: true,
	},
} );

/**
 * Set WP URL as baseUrl in Cypress config.
 *
 * @param {Function} on     function that used to register listeners on various events.
 * @param {Object}   config Cypress Config object.
 * @return {Object} Updated Cypress Config object.
 */
const setBaseUrl = async ( on, config ) => {
	const cacheDirectory = await getCacheDirectory();
	const wpEnvConfig = await loadConfig( cacheDirectory );

	if ( wpEnvConfig ) {
		const port = wpEnvConfig.env.tests.port || null;

		if ( port ) {
			config.baseUrl = 'http://localhost:80/';
		}
	}

	return config;
};
