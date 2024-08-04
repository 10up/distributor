import '../css/admin-pull-table.scss';

import jQuery from 'jquery';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import _ from 'underscore';

const { document } = window;

const chooseConnection = document.getElementsByClassName(
	'searchable-select__input'
)[ 0 ];
const choosePostType = document.getElementById( 'pull_post_type' );
const choosePostTypeBtn = document.getElementById( 'pull_post_type_submit' );
const searchField = document.getElementById( 'post-search-input' );
const searchBtn = document.getElementById( 'search-submit' );
const form = document.getElementById( 'posts-filter' );
const asDraftCheckboxes = document.querySelectorAll( '[name=dt_as_draft]' );
const pullLinks = document.querySelectorAll( '.distributor_page_pull .pull a' );

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
	const postType =
		choosePostType.options[ choosePostType.selectedIndex ].value;
	const baseURL = chooseConnection.getAttribute( 'data-pull-url' );
	let status = 'new';

	if ( -1 < ` ${ form.className } `.indexOf( ' status-skipped ' ) ) {
		status = 'skipped';
	} else if ( -1 < ` ${ form.className } `.indexOf( ' status-pulled ' ) ) {
		status = 'pulled';
	}

	return `${ baseURL }&pull_post_type=${ postType }&status=${ status }`;
};

document.addEventListener( 'DOMContentLoaded', async function () {
	const container = document.querySelector( '.searchable-select' );
	const inputContainer = container.querySelector(
		'.searchable-select__input-container'
	);
	const input = container.querySelector( '.searchable-select__input' );
	const icon = container.querySelector(
		'.searchable-select__input-container > .dashicons-arrow-down'
	);
	const dropdown = container.querySelector( '.searchable-select__dropdown' );

	const itemss = await fetch( '/wp-admin/admin-ajax.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: 'action=dt_load_connections_pull',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			return data.data;
		} );

	function htmlDecode( inputText ) {
		const doc = new DOMParser().parseFromString( inputText, 'text/html' );
		return doc.documentElement.textContent;
	}

	function setInputDefault() {
		const params = new URL( document.location.toString() ).searchParams;
		const connection_id = params.get( 'connection_id' );

		if ( connection_id ) {
			const connection = itemss.find(
				( item ) => item.id === connection_id
			);

			if ( connection ) {
				input.value = connection.name;
				input.setAttribute(
					'data-pull-url',
					htmlDecode( connection.pull_url )
				);
			}
		}
	}

	setInputDefault();

	function createDropdownItems( items ) {
		dropdown.innerHTML = '';
		items.forEach( ( item ) => {
			const div = document.createElement( 'div' );
			div.classList.add( 'searchable-select__item' );
			div.textContent = item.name;
			div.setAttribute( 'data-url', item.url );

			div.addEventListener( 'click', () => {
				input.value = item.name;
				input.setAttribute(
					'data-pull-url',
					htmlDecode( item.pull_url )
					// item.pull_url
				);
				document.location = getURL();

				hideDropdown();
			} );
			dropdown.appendChild( div );
		} );
	}

	function showDropdown() {
		createDropdownItems( itemss );
		dropdown.style.display = 'block';
	}

	function hideDropdown() {
		dropdown.style.display = 'none';
	}

	function filterItems( searchTerm ) {
		const filteredItems = itemss.filter( ( item ) =>
			item.toLowerCase().includes( searchTerm.toLowerCase() )
		);
		createDropdownItems( filteredItems );
	}

	inputContainer.addEventListener( 'click', function () {
		input.focus();
		showDropdown();
	} );

	icon.addEventListener( 'click', function ( event ) {
		event.stopPropagation();
		input.focus();
		showDropdown();
	} );

	input.addEventListener( 'input', function () {
		filterItems( this.value );
	} );

	input.addEventListener( 'focus', showDropdown );

	document.addEventListener( 'click', function ( event ) {
		if ( ! container.contains( event.target ) ) {
			hideDropdown();
		}
	} );
} );
