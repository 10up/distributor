import jQuery from 'jquery';
import _ from 'underscores';
import { dt, ajaxurl } from 'window';

const [ externalConnectionUrlField ]  = document.getElementsByClassName( 'external-connection-url-field' );
const externalConnectionMetaBox       = document.getElementById( 'dt_external_connection_details' );
const [ externalConnectionTypeField ] = document.getElementsByClassName( 'external-connection-type-field' );
const authFields                      = document.getElementsByClassName( 'auth-field' );
const rolesAllowed                    = document.getElementsByClassName( 'dt-roles-allowed' );
const titleField                      = document.getElementById( 'title' );
const endpointResult                  = document.querySelector( '.endpoint-result' );
const endpointErrors                  = document.querySelector( '.endpoint-errors' );
const postIdField                     = document.getElementById( 'post_ID' );
let $apiVerify                        = false;

/**
 * Check the external connection.
 */
function checkConnections() {
	if ( false !== $apiVerify ) {
		$apiVerify.abort();
	}

	if ( '' === externalConnectionUrlField.value ) {
		endpointErrors.innerText = '';
		endpointResult.innerText = '';

		endpointResult.removeAttribute( 'data-endpoint-state' );
		return;
	}

	endpointResult.setAttribute( 'data-endpoint-state', 'loading' );
	endpointResult.innerText = dt.endpoint_checking_message;

	endpointErrors.innerText = '';

	const auth = {};

	_.each( authFields, ( authField ) => {
		if ( authField.disabled ) {
			return;
		}

		const key = authField.getAttribute( 'data-auth-field' );

		if ( key ) {
			auth[key] = authField.value;
		}
	} );

	let postId = 0;
	if ( postIdField && postIdField.value ) {
		postId = postIdField.value;
	}

	$apiVerify = jQuery.ajax( {
		url: ajaxurl,
		method: 'post',
		data: {
			nonce: dt.nonce,
			action: 'dt_verify_external_connection',
			auth: auth,
			url: externalConnectionUrlField.value,
			type: externalConnectionTypeField.value,
			endpointId: postId
		}
	} ).done( ( response ) => {
		if ( ! response.success ) {
			endpointResult.setAttribute( 'data-endpoint-state', 'error' );
		} else {
			if ( response.data.errors.no_external_connection ) {
				endpointResult.setAttribute( 'data-endpoint-state', 'error' );

				if ( response.data.endpoint_suggestion ) {
					endpointResult.innerText = `${ dt.endpoint_suggestion } `;

					const suggestion = document.createElement( 'a' );
					suggestion.classList.add( 'suggest' );
					suggestion.innerText = response.data.endpoint_suggestion;

					endpointResult.appendChild( suggestion );
				} else {
					endpointResult.innerText = dt.bad_connection;
				}
			} else {
				if ( response.data.errors.no_distributor || ! response.data.can_post.length ) {
					endpointResult.setAttribute( 'data-endpoint-state', 'warning' );
					endpointResult.innerText = dt.limited_connection;

					const warnings = [];

					if ( response.data.errors.no_distributor ) {
						endpointResult.innerText += ` ${ dt.no_distributor }`;
					} else {
						endpointResult.innerText += ` ${ dt.bad_auth }`;
					}

					warnings.push( dt.no_push );
					warnings.push( dt.pull_limited );

					warnings.forEach( ( warning ) => {
						const warningNode       = document.createElement( 'li' );
						warningNode.innerText = warning;

						endpointErrors.append( warningNode );
					} );
				} else {
					endpointResult.setAttribute( 'data-endpoint-state', 'valid' );
					endpointResult.innerText = dt.good_connection;
				}
			}
		}
	} ).complete( () => {
		endpointResult.classList.remove( 'loading' );
	} );
}

setTimeout( () => {
	checkConnections();
}, 300 );

jQuery( externalConnectionMetaBox ).on( 'click', '.suggest', ( event ) => {
	externalConnectionUrlField.value = event.currentTarget.innerText;
	jQuery( externalConnectionUrlField ).trigger( 'input' );
} );

jQuery( externalConnectionMetaBox ).on( 'keyup input', '.auth-field, .external-connection-url-field', _.debounce( checkConnections, 250 ) );

jQuery( externalConnectionUrlField ).on( 'blur', ( event ) => {
	if ( '' === titleField.value && '' !== event.currentTarget.value ) {
		titleField.value = event.currentTarget.value.replace( /https?:\/\//i, '' );
		titleField.focus();
		titleField.blur();
	}
} );
/**
 * JS for basic auth
 *
 * @todo  separate
 */
const passwordField  = document.getElementById( 'dt_password' );
const usernameField  = document.getElementById( 'dt_username' );
const changePassword = document.querySelector( '.change-password' );

jQuery( usernameField ).on( 'keyup change', _.debounce( () => {
	if ( changePassword ) {
		passwordField.disabled       = false;
		passwordField.value          = '';
		changePassword.style.display = 'none';
	}
}, 250 ) );

jQuery( changePassword ).on( 'click', ( event ) => {
	event.preventDefault();

	if ( passwordField.disabled ) {
		passwordField.disabled        = false;
		passwordField.value           = '';
		event.currentTarget.innerText = dt.cancel;
	} else {
		passwordField.disabled        = true;
		passwordField.value           = 'sdfdsfsdfdsfdsfsd'; // filler password
		event.currentTarget.innerText = dt.change;
	}

	checkConnections();
} );

jQuery( rolesAllowed ).on( 'click', '.dt-role-checkbox', ( event ) => {
	if ( ! event.target.classList.contains( 'dt-role-checkbox' ) ) {
		return;
	}

	if ( ! event.target.checked ) {
		return;
	}

	if ( 'administrator' !== event.target.value && 'editor' !== event.target.value ) {
		alert( dt.roles_warning ); // eslint-disable-line no-alert
	}
} );

/**
 * Code for WordPress.com Oauth2 Authentication.
 *
 * @todo separate out code.
 */

/**
 * If the client id and secret are unavailable, hide all '.hide-until-authed' areas.
 *
 * For Oauth authentication, simplify the interface by hiding certain elements until the user has
 * completed the authorization process.
 *
 * Creates a cleaner flow for authorization by separating the authorization steps.
 */
const $hideUntilAuthed = jQuery( '.hide-until-authed' ),
	$authCredentials = jQuery( '.auth-credentials' ),
	$clientSecret    = jQuery( document.getElementById( 'dt_client_secret' ) ),
	$clientId        = jQuery( document.getElementById( 'dt_client_id' ) ),
	hideItemsRequiringAuth = () => {
		const oauthconnectionestablished = document.getElementsByClassName( 'oauth-connection-established' );
		if ( 0 === oauthconnectionestablished.length ) {
			$hideUntilAuthed.hide();
		}
	},

	/**
	 * Validate a form field, ensuring it is non-empty. Add an error class if empty.
	 *
	 * @param  {jQuery DomElement} $field The field to check.
	 */
	validateField = ( $field, event ) => {
		if ( '' === $field.val() ) {
			event.preventDefault();
			$field.addClass( 'error-required' );
			return false;
		} else {
			$field.removeClass( 'error-required' );
		}
		return true;
	};

/**
 * When the External connection type drop-down is changed, show the corresponding authorization fields.
 */
jQuery( externalConnectionTypeField ).on( 'change', () => {
	const slug = externalConnectionTypeField.value;

	$authCredentials.hide();
	jQuery( `.auth-credentials.${ slug }` ).show();

	// For WordPress.com Oauth authentication, hide fields until authentication is complete.
	if ( 'wpdotcom' === slug ) {
		hideItemsRequiringAuth();
	} else {

		// Otherwise, ensure all areas are showing.
		$hideUntilAuthed.show();
	}
} );


// On load for WordPress.com Oauth authentication, hide fields until authentication is complete.
if ( 'wpdotcom' === externalConnectionTypeField.value ) {
	hideItemsRequiringAuth();
}

// When authorization is initiated, ensure fields are non-empty.
const createConnectionButton = document.getElementById( 'create-oauth-connection' );
if ( createConnectionButton ) {
	jQuery( createConnectionButton ).on( 'click', ( event ) => {
		const validateClientSecret = validateField( $clientSecret, event ),
			validateClientId     = validateField( $clientId, event );
		if (
			! validateClientSecret ||
			! validateClientId
		) {
			event.preventDefault();
			return false;
		}
	} );
}

// Handle the changeCredentials link.
const changeCredentials             = document.getElementById( 'oauth-authentication-change-credentials' ),
	$authenticationDetailsWrapper = jQuery( '.oauth-authentication-details-wrapper' );

if ( changeCredentials ) {

	jQuery( changeCredentials ).on( 'click', function() {

		// Show the credentials fields.
		$authenticationDetailsWrapper.show();

		// Clear the secret field.
		$clientSecret.val( '' );

		// Remove the authorized message.
		jQuery( '.oauth-connection-established' ).remove();

		// Hide the remaining fields that only show after authorization is complete.
		hideItemsRequiringAuth();
	} );
}

// Handle the Authorize Connection button.
const beginAuthorize = document.getElementById( 'begin-authorization' );
if ( beginAuthorize ) {

	// Handle click to the wpdotcom begin-authorization button.
	jQuery( beginAuthorize ).on( 'click', ( event ) => {
		const $titleEl = jQuery( titleField ),
			title = $titleEl.val();

		// Ensure the connection title is not blank.
		if ( validateField( $titleEl, event ) ) {

			// Disable the button during the ajax request.
			jQuery( beginAuthorize ).addClass( 'disabled' );

			// Remove any error highlighting.
			$titleEl.removeClass( 'error-required' );

			// Make an ajax request to save the connection and retrieve the resulting post id.
			jQuery.ajax( {
				url: ajaxurl,
				method: 'post',
				data: {
					nonce: dt.nonce,
					action: 'dt_begin_authorization',
					title: title,
					id: jQuery( document.getElementById( 'post_ID' ) ).val()
				}
			} ).done( ( response ) => {
				if ( response.success && response.data.id ) {

					// The post has been saved, update the url in case the user refreshes.
					const url = `${ dt.admin_url }post.php?post=${ response.data.id }&action=edit`;
					history.pushState( {}, 'Oauth Authorize Details', url );

					// Update the form field for dt_redirect_uri and post id.
					jQuery( document.getElementById( 'dt_redirect_uri' ) ).val( url );
					jQuery( document.getElementById( 'dt_created_post_id' ) ).val( response.data.id );
					jQuery( document.getElementById( 'original_post_status' ) ).val( 'publish' );

					// Hide the first step and show the authentication details.
					jQuery( '.oauth-begin-authentication-wrapper' ).hide();
					$authenticationDetailsWrapper.show();
				} else {
					// @todo handle errors.
				}
			} ).complete( () => {

				// Ensure the
				jQuery( beginAuthorize ).removeClass( 'disabled' );
			} );
		}
	} );
}
