// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
	title: 'Distributor Developer Documentation',
	tagline:
		'Documentation for action and filter hooks found in the Distributor plugin',
	favicon: 'img/favicon.png',

	// Future flags, see https://docusaurus.io/docs/api/docusaurus-config#future
	future: {
		v4: true, // Improve compatibility with the upcoming Docusaurus v4
	},

	url: 'https://distributorplugin.com',
	baseUrl: '/distributor/',

	organizationName: '10up',
	projectName: 'distributor',

	onBrokenLinks: 'throw',
	onBrokenMarkdownLinks: 'warn',

	presets: [
		[
			'classic',
			/** @type {import('@docusaurus/preset-classic').Options} */
			{
				docs: {
					sidebarPath: './sidebars.js',
					routeBasePath: '/',
					breadcrumbs: true,
				},
				blog: false,
				theme: {
					customCss: './src/css/custom.css',
				},
			},
		],
	],

	themes: [
		[
			'@easyops-cn/docusaurus-search-local',
			{
				indexDocs: true,
				docsRouteBasePath: '/',
				docsDir: 'docs',
				hashed: true,
				highlightSearchTermsOnTargetPage: true,
				searchBarPosition: 'right',
			},
		],
	],

	themeConfig: {
		navbar: {
			title: 'Distributor Documentation',
			logo: {
				alt: 'Distributor Logo',
				src: 'img/favicon.png',
			},
			items: [
				{
					type: 'docSidebar',
					sidebarId: 'hooksSidebar',
					position: 'left',
					label: 'Get Started',
					href: '/get-started/',
					sidebarCollapsed: false,
				},
				{
					type: 'docSidebar',
					sidebarId: 'hooksSidebar',
					position: 'left',
					label: 'Hooks',
					href: '/hooks',
					sidebarCollapsed: false,
				},

				{
					type: 'docSidebar',
					sidebarId: 'hooksSidebar',
					position: 'left',
					label: 'Live Demo',
					href: 'https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/10up/distributor/refs/heads/develop/.github/blueprints/blueprint.json',
					sidebarCollapsed: false,
				},
				{
					href: 'https://github.com/10up/distributor',
					label: 'GitHub',
					position: 'right',
				},
			],
		},
		colorMode: {
			defaultMode: 'dark',
			disableSwitch: false,
			respectPrefersColorScheme: true,
		},
		footer: {
			style: 'dark',
			copyright:
				'Copyright © 2025 Distributor Developer Documentation. Built with WP Hooks Documentor.',
		},
		prism: {
			theme: prismThemes.github,
			darkTheme: prismThemes.dracula,
			additionalLanguages: [ 'php' ],
		},
	},
};

export default config;
