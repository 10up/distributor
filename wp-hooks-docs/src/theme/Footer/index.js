import React from 'react';

import clsx from 'clsx';
import { ThemeClassNames } from '@docusaurus/theme-common';
import FooterCopyright from '@theme/Footer/Copyright';
import styles from './footer.module.css';
import Link from '@docusaurus/Link';

/**
 * Get started section component
 *
 * @return {JSX.Element} The get started section component
 */
function GetStartedSection() {
	return (
		<section
			className={ clsx( styles.getStartedSection, styles.greySection ) }
		>
			<div className="container">
				<h2 className={ styles.sectionTitle }>Ready to Get Started?</h2>
				<p className={ styles.sectionDescription }>
					Distributor is a WordPress plugin that makes it easy to
					syndicate and reuse content across your websites — whether
					in a single multisite or across the web.
				</p>
				<div className={ styles.getStartedActions }>
					<Link
						className="button button--primary button--lg"
						to="https://distributorplugin.com/#cta"
					>
						Download Distributor Plugin
					</Link>
				</div>
			</div>
		</section>
	);
}

function Footer() {
	const currentYear = new Date().getFullYear().toString();

	return (
		<>
			<GetStartedSection />
			<footer
				className={ clsx(
					ThemeClassNames.layout.footer.container,
					styles.mainFooter,
					'footer'
				) }
			>
				<span
					aria-hidden="true"
					className={ styles.footerBackground }
				></span>
				<div className="container">
					<div className={ styles.footerContainer }>
						<div className={ styles.footerCopyright }>
							<div className={ styles.footerCopyrightLinks }>
								<FooterCopyright
									copyright={ `Finely crafted by Fueled, ©${ currentYear }` }
								/>
								<a href="https://github.com/10up/distributor/issues/">
									Issues
								</a>
								&nbsp;
								<span>&nbsp;|&nbsp;</span>
								<a href="https://github.com/10up/distributor/">
									Contribute
								</a>
								&nbsp;
								<span>&nbsp;|&nbsp;</span>
								<a href="https://distributorplugin.com/privacy-policy/">
									Privacy Policy
								</a>
							</div>
						</div>
						<div className={ styles.footerLogo }>
							<img
								loading="lazy"
								decoding="async"
								width="172"
								height="33"
								src="https://distributorplugin.com/wp-content/uploads/sites/2/2025/06/lockup_white.svg"
								alt="Fueled"
							/>
							<div className={ styles.footer10upButton }>
								<div className={ styles.footer10upButtonInner }>
									<a
										className="wp-block-button__link wp-element-button"
										href="https://fueled.com/wordpress"
									>
										Engineered by the{ ' ' }
										<img
											loading="lazy"
											decoding="async"
											width="21"
											height="24"
											style={ { width: '21px' } }
											src="https://distributorplugin.com/wp-content/uploads/sites/2/2025/06/svgexport-3-1.svg"
											alt="10up"
										/>{ ' ' }
										WordPress Practice
									</a>
								</div>
							</div>
						</div>
						<div className={ styles.footerSocial }>
							<a
								href="https://www.facebook.com/10up.agency/?ref=distributor"
								title="10up on Facebook"
								aria-hidden="true"
							>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 32 32"
									aria-hidden="true"
								>
									<path d="M16 0c8.837 0 16 7.163 16 16 0 8.159-6.107 14.892-14 15.876V20h5.5l.5-4h-6v-2a2 2 0 0 1 2-2h4V8h-4a6 6 0 0 0-6 6v2h-3v4h3v11.876C6.107 30.892 0 24.159 0 16 0 7.163 7.163 0 16 0z"></path>
								</svg>
							</a>
							<a
								href="https://x.com/10up"
								aria-hidden="true"
								title="10up on X"
							>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 24 24"
									aria-hidden="true"
								>
									<path d="M14.095479,10.316482L22.286354,1h-1.940718l-7.115352,8.087682L7.551414,1H1l8.589488,12.231093L1,23h1.940717  l7.509372-8.542861L16.448587,23H23L14.095479,10.316482z M11.436522,13.338465l-0.871624-1.218704l-6.924311-9.68815h2.981339  l5.58978,7.82155l0.867949,1.218704l7.26506,10.166271h-2.981339L11.436522,13.338465z" />
								</svg>
							</a>
							<a
								href="https://github.com/10up/"
								aria-hidden="true"
								title="10up on GitHub"
							>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 32 32"
									aria-hidden="true"
								>
									<path d="M16 0C7.163 0 0 7.163 0 16s7.163 16 16 16 16-7.163 16-16S24.837 0 16 0zm9.502 25.502a13.4 13.4 0 0 1-5.51 3.334v-2.398c0-1.26-.432-2.188-1.297-2.781.542-.052 1.039-.125 1.492-.219s.932-.229 1.438-.406.958-.388 1.359-.633.786-.563 1.156-.953.68-.833.93-1.328.448-1.089.594-1.781.219-1.456.219-2.289c0-1.615-.526-2.99-1.578-4.125.479-1.25.427-2.609-.156-4.078l-.391-.047c-.271-.031-.758.083-1.461.344s-1.492.688-2.367 1.281a14.367 14.367 0 0 0-3.859-.516c-1.344 0-2.625.172-3.844.516-.552-.375-1.075-.685-1.57-.93s-.891-.411-1.188-.5-.573-.143-.828-.164-.419-.026-.492-.016-.125.021-.156.031c-.583 1.479-.635 2.839-.156 4.078-1.052 1.135-1.578 2.51-1.578 4.125 0 .833.073 1.596.219 2.289s.344 1.286.594 1.781.56.938.93 1.328.755.708 1.156.953.854.456 1.359.633.984.313 1.438.406.95.167 1.492.219c-.854.583-1.281 1.51-1.281 2.781v2.445A13.4 13.4 0 0 1 6.5 25.501a13.4 13.4 0 0 1-3.936-9.502A13.4 13.4 0 0 1 6.5 6.497a13.4 13.4 0 0 1 9.502-3.936 13.4 13.4 0 0 1 9.502 3.936 13.4 13.4 0 0 1 3.936 9.502 13.4 13.4 0 0 1-3.936 9.502z"></path>
								</svg>
							</a>
						</div>
					</div>
				</div>
			</footer>
		</>
	);
}

export default React.memo( Footer );
