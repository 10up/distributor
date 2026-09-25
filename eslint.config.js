const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );
const cypress = require( 'eslint-plugin-cypress/flat' );

module.exports = [
	...defaultConfig,
	{
		ignores: [
			'release/**',
			'dist/**',
			'docs-built/**',
			'gulp-tasks/**',
			'wp-hooks-docs/**',
			'tests/cypress/reports/**',
			'tests/cypress/videos/**',
			'tests/cypress/screenshots/**',
		],
	},
	{
		settings: {
			'import/core-modules': [
				'@wordpress/a11y',
				'@wordpress/api-fetch',
				'@wordpress/api-request',
				'@wordpress/blob',
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/compose',
				'@wordpress/data',
				'@wordpress/date',
				'@wordpress/dom-ready',
				'@wordpress/dom',
				'@wordpress/edit-post',
				'@wordpress/element',
				'@wordpress/hooks',
				'@wordpress/i18n',
				'@wordpress/keyboard-shortcuts',
				'@wordpress/keycodes',
				'@wordpress/plugins',
				'@wordpress/primitives',
				'@wordpress/rich-text',
				'@wordpress/url',
				'jquery',
				'underscore',
			],
		},
		rules: {
			'import/no-extraneous-dependencies': [
				'error',
				{
					packageDir: [ './', './node_modules/@wordpress/scripts' ],
				},
			],
		},
	},
	{
		files: [ 'tests/cypress/**/*.js' ],
		plugins: cypress.configs.recommended.plugins,
		languageOptions: {
			globals: cypress.configs.recommended.languageOptions.globals,
		},
		rules: {
			...cypress.configs.recommended.rules,
			'jest/no-disabled-tests': 'off',
			'jest/no-focused-tests': 'off',
			'jest/no-identical-title': 'off',
			'jest/prefer-to-have-length': 'off',
			'jest/valid-expect': 'off',
			'jest/expect-expect': 'off',
		},
	},
];
