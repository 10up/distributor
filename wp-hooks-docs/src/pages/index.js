/* eslint-disable @typescript-eslint/explicit-function-return-type */
import React from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';

import styles from './index.module.css';

/**
 * Homepage header component
 *
 * @return {JSX.Element} The header component
 */
function HomepageHeader() {
	return (
		<header className={ clsx( 'hero', styles.heroBanner ) }>
			<div className="container">
				<div className={ styles.headerContent }>
					<p className={ styles.wpPluginLabel }>
						Developer Documentation
					</p>
					<h1 className={ styles.heroTitle }>Distributor</h1>
					<h2 className={ styles.heroSubtitle }>Documentation</h2>
					<p className={ styles.heroDescription }>
						Your complete guide to Distributor&apos;s hooks and
						APIs. Build and extend content syndication the WordPress
						way.
					</p>
					<div className={ styles.buttons }>
						<Link
							className="button button--primary button--lg"
							to="/get-started/"
						>
							Get Started
						</Link>
						<Link
							className="button button--outline button--lg"
							to="/hooks"
						>
							Hooks Reference
						</Link>
					</div>
				</div>
			</div>
		</header>
	);
}

/**
 * Developer Features section component
 *
 * @return {JSX.Element} The developer features section
 */
function DeveloperFeaturesSection() {
	const features = [
		{
			title: 'Distribution Guide',
			description:
				'Learn the fundamentals of content distribution, setting up connections, and managing content syndication workflows.',
			link: '/get-started/how-to-distribute-content/',
			buttonText: 'View Guide',
		},
		{
			title: 'Code Snippets',
			description:
				'Ready-to-use code snippets and practical examples for common Distributor customizations and integrations.',
			link: '/advanced-docs/snippets/',
			buttonText: 'Browse Snippets',
		},
		{
			title: 'Stored ID Handling',
			description:
				'Understand how Distributor manages content relationships and handles stored IDs across distributed content.',
			link: '/advanced-docs/stored-id-handling/',
			buttonText: 'Learn More',
		},
		{
			title: 'Auto Distribution',
			description:
				'Set up automated content distribution workflows using hooks and filters to streamline your content syndication.',
			link: '/advanced-docs/auto-distribution/',
			buttonText: 'Setup Guide',
		},
		{
			title: 'Migration Guide',
			description:
				'Complete migration guide for upgrading from Distributor version 1 to version 2 with breaking changes and updates.',
			link: '/advanced-docs/migration-guide-version-1-to-version-2/',
			buttonText: 'Migrate Now',
		},
		{
			title: 'Hooks & Filters',
			description:
				'Comprehensive reference of all available hooks, actions, and filters with parameters and usage examples.',
			link: '/hooks/',
			buttonText: 'View Reference',
		},
	];

	return (
		<section className={ styles.featureSection }>
			<div className="container">
				<h2 className={ styles.sectionTitle }>Build & Extend</h2>
				<p className={ styles.sectionDescription }>
					Everything you need to customize, integrate, and extend
					Distributor. From quick start guides to advanced hooks and
					migration resources.
				</p>
				<div className={ styles.featureGrid }>
					{ features.map( ( feature, idx ) => (
						<div key={ idx } className={ styles.featureCard }>
							<h3>{ feature.title }</h3>
							<p>{ feature.description }</p>
							<Link
								className="button button--primary"
								to={ feature.link }
							>
								{ feature.buttonText }
							</Link>
						</div>
					) ) }
				</div>
			</div>
		</section>
	);
}

/**
 * Contribution Guide section component
 *
 * @return {JSX.Element} The contribution guide section
 */
function ContributionGuideSection() {
	const contributions = [
		{
			title: 'Report Issues',
			description:
				'Found a bug, have a feature request, or discovered a security vulnerability? Help us improve by reporting issues.',
			action: 'Report an Issue',
			link: 'https://github.com/10up/distributor/issues',
		},
		{
			title: 'Contribute Code',
			description:
				'Submit pull requests with bug fixes, new features, or improvements. All skill levels welcome.',
			action: 'View Contributing Guide',
			link: 'https://github.com/10up/distributor/blob/develop/CONTRIBUTING.md',
		},
		{
			title: 'Share Feedback',
			description:
				'Share your experience, suggest improvements, or discuss ideas with the development team and community.',
			action: 'Start a Discussion',
			link: 'https://github.com/10up/distributor/issues',
		}
	];

	return (
		<section className={ styles.contributionSection }>
			<div className="container">
				<h2 className={ styles.sectionTitle }>Get Involved</h2>
				<p className={ styles.sectionDescription }>
					Join our community and help make Distributor better for
					everyone. There are many ways to contribute, regardless of
					your skill level.
				</p>
				<div className={ styles.contributionGrid }>
					{ contributions.map( ( item, idx ) => (
						<div key={ idx } className={ styles.contributionCard }>
							<h3>{ item.title }</h3>
							<p>{ item.description }</p>
							<div className={ styles.buttons }>
								<Link
									className="button button--primary"
									to={ item.link }
								>
									{ item.action }
								</Link>
							</div>
						</div>
					) ) }
				</div>
			</div>
		</section>
	);
}

/**
 * Homepage component
 *
 * @return {JSX.Element} The homepage component
 */
export default function Home() {
	const { siteConfig } = useDocusaurusContext();

	return (
		<Layout title={ siteConfig.title } description={ siteConfig.tagline }>
			<HomepageHeader />
			<main>
				<DeveloperFeaturesSection />
				<ContributionGuideSection />
			</main>
		</Layout>
	);
}
