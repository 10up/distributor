import '../css/admin-pull-table.scss';

import jQuery from 'jquery';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

const { document } = window;

const chooseConnection = document.getElementById( 'pull_connections' );
const choosePostType = document.getElementById( 'pull_post_type' );
const choosePostTypeBtn = document.getElementById( 'pull_post_type_submit' );
const searchField = document.getElementById( 'post-search-input' );
const searchBtn = document.getElementById( 'search-submit' );
const form = document.getElementById( 'posts-filter' );
const asDraftCheckboxes = document.querySelectorAll( '[name=dt_as_draft]' );
const pullLinks = document.querySelectorAll( '.distributor_page_pull .pull a' );

// Change target website to pull contents from
jQuery( chooseConnection ).on( 'change', ( event ) => {
	document.location =
		event.currentTarget.options[
			event.currentTarget.selectedIndex
		].getAttribute( 'data-pull-url' );

	document.body.className += ' ' + 'dt-loading';
} );

if ( chooseConnection && choosePostType && form ) {
	// Handle post type selection
	if ( choosePostTypeBtn ) {
		jQuery( choosePostTypeBtn ).on( 'click', ( event ) => {
			event.preventDefault();

			document.location = getURL();

			document.body.className += ' ' + 'dt-loading';
		} );
	}

	// Handle search button click
	if ( searchField && searchBtn ) {
		jQuery( searchBtn ).on( 'click', ( event ) => {
			event.preventDefault();

			const search = searchField.value;

			document.location = `${ getURL() }&s=${ search }`;

			document.body.className += ' dt-loading';
		} );
	}

	// Handle pull mode checkbox event
	if ( asDraftCheckboxes ) {
		jQuery( asDraftCheckboxes ).on( 'change', ( event ) => {
			if ( event.currentTarget.checked ) {
				// Check all pull mode checkbox as there are multiple. Ideally before and after post list.
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[ i ].checked = true;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[ i ].text = __( 'Pull as draft', 'distributor' );
				}
			} else {
				// Uncheck all pull mode checkbox as there are multiple. Ideally before and after post list.
				for ( let i = 0; i < asDraftCheckboxes.length; ++i ) {
					asDraftCheckboxes[ i ].checked = false;
				}

				for ( let i = 0; i < pullLinks.length; ++i ) {
					pullLinks[ i ].text = __( 'Pull', 'distributor' );
				}
			}
		} );
	}

	// Pull content via ajax
	jQuery( '#doaction, #doaction2' ).on( 'click', e => {
		// Check action
		var action = jQuery( '[name="action"]' ).val();
		if ( action != 'bulk-syndicate' ) {
			return;
		}
		e.preventDefault();
		openModal();
	});

	jQuery( '.distributor_page_pull .pull a' ).on( 'click', function( e ) {
		e.preventDefault();
		jQuery( this ).closest( 'tr' ).find( '.check-column input[type="checkbox"]' ).prop( 'checked', true );
		openModal();
	});
	
	function openModal() {
		// Prepare data
		var aborted        = false;
		var post_ids       = [];
		var source_label   = jQuery('#pull_connections option:selected').text();

		jQuery( '#the-list .check-column input[type="checkbox"]:checked' ).each( function() {
			var id = parseInt( jQuery( this ).val() );
			if ( id && post_ids.indexOf( id ) === -1 ) {
				post_ids.push( id );
			}
		} );

		var post_ids_count = post_ids.length;
		if ( ! post_ids_count ) {
			alert( 'Please select posts to pull' );
			return;
		}

		function log( custom_content ) {
			jQuery( '#distributor-pull-modal .pull-progress' ).html( custom_content || `Pulled: ${post_ids_count-post_ids.length}/${post_ids_count}` );
		}

		// Create modal for pulling via ajax
		jQuery( '#distributor-pull-modal' ).remove();
		jQuery( 'body' ).append(
			`
			<div id="distributor-pull-modal">
				<div>
					<div class="pull-head-section">
						<h3>Pulling from <b>${source_label}</b></h3>
						<div class="pull-progress">Selected: ${post_ids_count}</div>
					</div>
					<br/>
					<div id="pull-button-container">
						<button class="button button-secondary" data-action="cancel">Cancel</button>
						<button class="button button-primary" data-action="start">Start</button>
					</div>
				</div>
			</div>
			`
		);

		jQuery( '#distributor-pull-modal' ).on( 'click', '[data-action="start"]', function() {
			jQuery( this ).prop( 'disabled', true );
			
			var excludes  = [ 'post[]', 'action2', 'page', 'paged' ];
			var form_data = {};
			jQuery( '#posts-filter' ).serializeArray().forEach( field => {
				if ( excludes.indexOf( field.name ) == -1 ) {
					form_data[ field.name ] = field.value;
				}
			});
			form_data.action = 'distributor_pull_content';

			function looper() {
				if ( aborted ) {
					jQuery('#distributor-pull-modal').remove()
				}

				log();

				form_data.post_id = post_ids.shift();
				var xhr = new XMLHttpRequest();

				jQuery.ajax({
					url: window.ajaxurl,
					type: 'POST',
					data: form_data,
					xhr: function() {
						return xhr;
					},
					success: function (resp) {
						if ( aborted ) {
							return;
						}
						
						if ( ! resp.success || ! resp.data?.redirect_to ) {
							log( `<span style="color:#a00;">${resp.data?.message || 'Something went wrong!'}</span>`);
							return;
						}

						log();

						if ( post_ids.length ) {
							// Call the pull again for remaing post
							looper();
						} else {
							// Redirect to where it asks to
							window.location.assign( resp.data.redirect_to );
						}
					}
				});
			}

			looper();
		}).on( 'click', '[data-action="cancel"]', function() {
			aborted = true;
			jQuery( '#distributor-pull-modal' ).remove();
			
			// Refresh the page if any post already pulled.
			if ( post_ids_count > post_ids.length ) {
				window.location.reload();
			}
		} );
	};
}

/**
 * Build our Distribution URL.
 *
 * @return {string} Distribution URL.
 */
const getURL = () => {
	const postType =
		choosePostType.options[ choosePostType.selectedIndex ].value;
	const baseURL =
		chooseConnection.options[ chooseConnection.selectedIndex ].getAttribute(
			'data-pull-url'
		);
	let status = 'new';

	if ( -1 < ` ${ form.className } `.indexOf( ' status-skipped ' ) ) {
		status = 'skipped';
	} else if ( -1 < ` ${ form.className } `.indexOf( ' status-pulled ' ) ) {
		status = 'pulled';
	}

	return `${ baseURL }&pull_post_type=${ postType }&status=${ status }`;
};
