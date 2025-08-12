import '../css/admin-pull-table.scss';

import jQuery from 'jquery';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

const { document, DISTRIBUTOR } = window;
const { getPullUrl } = DISTRIBUTOR;

/**
 * Escape special characters in URL components.
 *
 * @param {string} str The string to escape.
 * @return {string} The escaped string.
 */
const escapeURLComponent = ( str ) => {
	return encodeURIComponent( str ).replace( /[!'()*]/g, ( c ) => {
		return '%' + c.charCodeAt( 0 ).toString( 16 );
	} );
};

const chooseConnection = document.getElementById( 'pull_connections' );
const choosePostType = document.getElementById( 'pull_post_type' );
const choosePostTypeBtn = document.getElementById( 'pull_post_type_submit' );
const choosePostTypeReset = document.getElementById( 'pull_post_type_reset' );
const searchField = document.getElementById( 'post-search-input' );
const searchBtn = document.getElementById( 'search-submit' );
const form = document.getElementById( 'posts-filter' );
const asDraftCheckboxes = document.querySelectorAll( '[name=dt_as_draft]' );
const pullLinks = document.querySelectorAll( '.distributor_page_pull .pull a' );
const pullTaxonomies = document.querySelectorAll( '.pull-taxonomy' );

jQuery( chooseConnection ).on( 'change', ( event ) => {
	const pullUrlId =
		event.currentTarget.options[
			event.currentTarget.selectedIndex
		].getAttribute( 'data-pull-url-id' );

	document.location = getPullUrl( pullUrlId );
	document.body.className += ' ' + 'dt-loading';
} );

if ( chooseConnection && choosePostType && form ) {
	/**
	 * When the post type is changed, show/hide the taxonomy fields based on the post type.
	 */
	jQuery( choosePostType ).on( 'change', ( event ) => {
		const selectedPostType =
			event.currentTarget.options[ event.currentTarget.selectedIndex ];
		if ( selectedPostType ) {
			const dataTaxonomies =
				selectedPostType.getAttribute( 'data-taxonomies' );
			if ( dataTaxonomies ) {
				const supportedTaxonomies = JSON.parse( dataTaxonomies );
				if ( supportedTaxonomies.length > 0 ) {
					pullTaxonomies.forEach( ( taxonomyField ) => {
						if (
							supportedTaxonomies.includes(
								taxonomyField.id.replace( 'pull_', '' )
							)
						) {
							jQuery( taxonomyField ).addClass( 'show' );
							jQuery( taxonomyField ).removeClass( 'hide' );
						} else {
							jQuery( taxonomyField ).addClass( 'hide' );
							jQuery( taxonomyField ).removeClass( 'show' );
						}
					} );
				} else {
					pullTaxonomies.forEach( ( taxonomyField ) => {
						jQuery( taxonomyField ).addClass( 'hide' );
						jQuery( taxonomyField ).removeClass( 'show' );
					} );
				}
			}
		}
	} );

	if ( choosePostTypeBtn ) {
		jQuery( choosePostTypeBtn ).on( 'click', ( event ) => {
			event.preventDefault();

			document.location = getURL();

			document.body.className += ' ' + 'dt-loading';
		} );
	}

	/**
	 * When the reset filters button is clicked, reset the filters and reload the page.
	 */
	if ( choosePostTypeReset ) {
		jQuery( choosePostTypeReset ).on( 'click', ( event ) => {
			event.preventDefault();

			const pullUrlId = escapeURLComponent(
				chooseConnection.options[
					chooseConnection.selectedIndex
				].getAttribute( 'data-pull-url-id' )
			);

			const baseURL = getPullUrl( pullUrlId );
			let status = 'new';

			if ( -1 < ` ${ form.className } `.indexOf( ' status-skipped ' ) ) {
				status = 'skipped';
			} else if (
				-1 < ` ${ form.className } `.indexOf( ' status-pulled ' )
			) {
				status = 'pulled';
			}

			document.location = `${ baseURL }&status=${ status }`;
			document.body.className += ' ' + 'dt-loading';
		} );
	}

	if ( searchField && searchBtn ) {
		jQuery( searchBtn ).on( 'click', ( event ) => {
			event.preventDefault();

			const search = encodeURIComponent( searchField.value );

			document.location = `${ getURL() }&s=${ search }`;

			document.body.className += ' dt-loading';
		} );
	}

	if ( asDraftCheckboxes && pullLinks ) {
		jQuery( asDraftCheckboxes ).on( 'change', ( event ) => {
			if ( event.currentTarget.checked ) {
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[ i ].checked = true;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[ i ].href = addQueryArgs( pullLinks[ i ].href, {
						dt_as_draft: 'draft' /*eslint camelcase: 0*/,
					} );
					pullLinks[ i ].text = __( 'Pull as draft', 'distributor' );
				}
			} else {
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[ i ].checked = false;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[ i ].href = addQueryArgs( pullLinks[ i ].href, {
						dt_as_draft: '' /*eslint camelcase: 0*/,
					} );
					pullLinks[ i ].text = __( 'Pull', 'distributor' );
				}
			}
		} );
	}
}

/**
 * Build our Distribution URL.
 *
 * @return {string} Distribution URL.
 */
const getURL = () => {
	const postType = escapeURLComponent(
		choosePostType.options[ choosePostType.selectedIndex ].value
	);

	// Build the taxonomies query string.
	let taxonomies = '';
	if ( pullTaxonomies ) {
		pullTaxonomies.forEach( ( taxonomyField ) => {
			if ( jQuery( taxonomyField ).hasClass( 'show' ) ) {
				taxonomies += `${ taxonomyField.id }=${
					escapeURLComponent(
						taxonomyField.options[ taxonomyField.selectedIndex ].value
					)
				}&`;
			}
		} );
	}

	if ( taxonomies ) {
		taxonomies = taxonomies.slice( 0, -1 );
	}

	const pullUrlId = escapeURLComponent(
		chooseConnection.options[ chooseConnection.selectedIndex ].getAttribute(
			'data-pull-url-id'
		)
	);
	const baseURL = getPullUrl( pullUrlId );
	let status = 'new';

	if ( -1 < ` ${ form.className } `.indexOf( ' status-skipped ' ) ) {
		status = 'skipped';
	} else if ( -1 < ` ${ form.className } `.indexOf( ' status-pulled ' ) ) {
		status = 'pulled';
	}

	return `${ baseURL }&pull_post_type=${ postType }&status=${ status }&${ taxonomies }`;
};
