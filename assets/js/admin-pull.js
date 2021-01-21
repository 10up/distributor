import jQuery from 'jquery';
import { addQueryArgs } from '@wordpress/url';

const chooseConnection = document.getElementById( 'pull_connections' );
const choosePostType = document.getElementById( 'pull_post_type' );
const choosePostTypeBtn = document.getElementById( 'pull_post_type_submit' );
const searchField = document.getElementById( 'post-search-input' );
const searchBtn = document.getElementById( 'search-submit' );
const form = document.getElementById( 'posts-filter' );
const asDraftCheckboxes = document.querySelectorAll( '[name=dt_as_draft]' );
const pullLinks = document.querySelectorAll( '.distributor_page_pull .pull a' );

jQuery( chooseConnection ).on( 'change', ( event ) => {

	document.location = event.currentTarget.options[event.currentTarget.selectedIndex].getAttribute( 'data-pull-url' );

	document.body.className += ' ' + 'dt-loading';
} );

if ( chooseConnection && choosePostType && form ) {

	if ( choosePostTypeBtn ) {
		jQuery( choosePostTypeBtn ).on( 'click', ( event ) => {

			event.preventDefault();

			document.location = getURL();

			document.body.className += ' ' + 'dt-loading';
		} );
	}

	if ( searchField && searchBtn ) {
		jQuery( searchBtn ).on( 'click', ( event ) => {
			event.preventDefault();

			const search = searchField.value;

			document.location = `${ getURL() }&s=${ search }`;

			document.body.className += ' dt-loading';
		} );
	}

	if ( asDraftCheckboxes && pullLinks ) {
		jQuery( asDraftCheckboxes ).on( 'change', ( event ) => {
			if ( event.currentTarget.checked ) {
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[i].checked = true;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[i].href = addQueryArgs( pullLinks[i].href,
						{
							dt_as_draft: 'draft', /*eslint camelcase: 0*/
						}
					);
				}
			} else {
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[i].checked = false;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[i].href = addQueryArgs( pullLinks[i].href,
						{
							dt_as_draft: '', /*eslint camelcase: 0*/
						}
					);
				}
			}
		} );
	}
}

/**
 * Build our Distribution URL.
 *
 * @return {string}
 */
const getURL = () => {
	const postType = choosePostType.options[ choosePostType.selectedIndex ].value;
	const baseURL = chooseConnection.options[ chooseConnection.selectedIndex ].getAttribute( 'data-pull-url' );
	let status = 'new';

	if ( -1 < ( ` ${ form.className } ` ).indexOf( ' status-skipped ' ) ) {
		status = 'skipped';
	} else if ( -1 < ( ` ${ form.className } ` ).indexOf( ' status-pulled ' ) ) {
		status = 'pulled';
	}

	return `${ baseURL }&pull_post_type=${ postType }&status=${ status }`;
};
