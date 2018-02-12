import jQuery from 'jquery';
import _ from 'underscores';
import { dt, ajaxurl } from 'window';

const externalConnectionUrlField  = document.getElementsByClassName( 'external-connection-url-field' )[0];
const externalConnectionMetaBox   = document.getElementById( 'dt_external_connection_details' );
const externalConnectionTypeField = document.getElementsByClassName( 'external-connection-type-field' )[0];
const authFields                  = document.getElementsByClassName( 'auth-field' );
const rolesAllowed                = document.getElementsByClassName( 'dt-roles-allowed' );
const titleField                  = document.getElementById( 'title' );
const endpointResult              = document.querySelector( '.endpoint-result' );
const endpointErrors              = document.querySelector( '.endpoint-errors' );
const postIdField                 = document.getElementById( 'post_ID' );
let $apiVerify                  = false;

function checkConnections() {
	if ( $apiVerify !== false ) {
		$apiVerify.abort();
	}

	if ( externalConnectionUrlField.value === '' ) {
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

		var key = authField.getAttribute( 'data-auth-field' );

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
			endpoint_id: postId
		}
	} ).done( ( response ) => {
		if ( ! response.success ) {
			endpointResult.setAttribute( 'data-endpoint-state', 'error' );
		} else {
			if ( response.data.errors.no_external_connection ) {
				endpointResult.setAttribute( 'data-endpoint-state', 'error' );

				if ( response.data.endpoint_suggestion ) {
					endpointResult.innerText = dt.endpoint_suggestion + ' ';

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

					if ( ! response.data.can_post.length ) {
						warnings.push( dt.bad_auth );
					}

					if ( response.data.errors.no_distributor ) {
						warnings.push( dt.no_distributor );
					}

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
	if ( titleField.value === '' && event.currentTarget.value !== '' ) {
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

	if ( event.target.value !== 'administrator' && event.target.value !== 'editor' ) {
		alert( dt.roles_warning );
	}
} );
