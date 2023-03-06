import '../css/admin-external-connection.css';

import jQuery from 'jquery';
import _ from 'underscore';
import { addQueryArgs, isURL, prependHTTP } from '@wordpress/url';
import { speak } from '@wordpress/a11y';
import compareVersions from 'compare-versions';

const { __,sprintf } = wp.i18n;

const { ajaxurl, alert, document, dt, history } = window;

const { body } = document;

const externalConnectionUrlField = document.getElementById(
	'dt_external_connection_url'
);
const externalConnectionMetaBox = document.getElementById(
	'dt_external_connection_details'
);
const externalConnectionTypeField = document.getElementById(
	'dt_external_connection_type'
);
const authFields = document.getElementsByClassName( 'auth-field' );
const rolesAllowed = document.getElementsByClassName( 'dt-roles-allowed' );
const titleField = document.getElementById( 'title' );
const endpointResult = document.querySelector( '.endpoint-result' );
const endpointErrors = document.querySelector( '.endpoint-errors' );
const postIdField = document.getElementById( 'post_ID' );
const createConnection = document.getElementById( 'create-connection' );
const wpbody = document.getElementById( 'wpbody' );
const externalSiteUrlField = document.getElementById( 'dt_external_site_url' );
const wizardError = document.getElementsByClassName( 'dt-wizard-error' );
const [ wizardStatus ] = document.getElementsByClassName( 'dt-wizard-status' );
const authorizeConnectionButton = document.getElementsByClassName(
	'establish-connection-button'
);
const beginOauthConnectionButton = document.getElementById(
	'begin-authorization'
);
const createOauthConnectionButton = document.getElementById(
	'create-oauth-connection'
);
const manualSetupButton = document.getElementsByClassName(
	'manual-setup-button'
);
const titlePrompt = document.getElementById( '#title-prompt-text' );
const slug = externalConnectionTypeField.value;
let $apiVerify = false;
wpbody.className = slug;

// Prevent the `enter` key from submitting the form.
jQuery( '#post' ).on( 'keypress', function ( event ) {
	if ( 13 !== event.which || body.classList.contains( 'post-php' ) ) {
		return true;
	}

	if ( 'wp' === jQuery( externalConnectionTypeField ).val() ) {
		jQuery( authorizeConnectionButton ).trigger( 'click' );
	}

	if ( 'wpdotcom' === jQuery( externalConnectionTypeField ).val() ) {
		if ( ! isHidden( beginOauthConnectionButton ) ) {
			jQuery( beginOauthConnectionButton ).trigger( 'click' );
		}

		if ( ! isHidden( createOauthConnectionButton ) ) {
			jQuery( createOauthConnectionButton ).trigger( 'click' );
		}
	}

	return false;
} );

/**
 * Handle Setup Connection Wizard "Authorize Connection" button.
 */
jQuery( authorizeConnectionButton ).on( 'click', ( event ) => {
	event.preventDefault();

	// Clear any previous errors.
	jQuery( wizardError[ 0 ] ).text( '' );

	// Verify Title and Site URL fields are non-empty.
	const validateTitle = validateField( jQuery( titleField ), event );
	const validateURL = validateField( jQuery( externalSiteUrlField ), event );

	if ( ! validateTitle || ! validateURL ) {
		event.preventDefault();
		return false;
	}

	let siteURL = prependHTTP( externalSiteUrlField.value );
	if ( ! isURL( siteURL ) ) {
		jQuery( wizardError[ 0 ] ).text( __( 'Please enter a valid URL, including the HTTP(S).', 'distributor' ) );
		return false;
	}

	// Show the spinner and loading message.
	wizardStatus.style.display = 'block';

	// Remove wp-json from URL, if that was added
	siteURL = siteURL.replace( /wp-json(\/)*/, '' );

	// Ensure URL ends with trailing slash
	siteURL = siteURL.replace( /\/?$/, '/' );

	jQuery
		.ajax( {
			url: ajaxurl,
			method: 'post',
			data: {
				nonce: dt.nonce,
				action: 'dt_get_remote_info',
				url: siteURL,
			},
		} )
		.done( ( response ) => {
			wizardStatus.style.display = 'none';

			if ( ! response.success ) {
				if (
					Object.prototype.hasOwnProperty.call( response, 'data' ) &&
					Object.prototype.hasOwnProperty.call(
						response.data,
						'rest_url'
					) &&
					! Object.prototype.hasOwnProperty.call(
						response.data,
						'version'
					)
				) {
					jQuery( wizardError[ 0 ] ).text( __( 'Distributor not installed on remote site.', 'distributor' ) );
					return;
				}

				jQuery( wizardError[ 0 ] ).text( __( 'Distributor not installed on remote site.', 'distributor' ) );

				if (
					Object.prototype.hasOwnProperty.call( response, 'data' ) &&
					Array.isArray( response.data ) &&
					0 < response.data.length &&
					Object.prototype.hasOwnProperty.call(
						response.data[ 0 ],
						'code'
					) &&
					Object.prototype.hasOwnProperty.call(
						response.data[ 0 ],
						'message'
					)
				) {
					jQuery( wizardError[ 0 ] ).append( '<br/>' );
					response.data.forEach( ( error ) => {
						jQuery( wizardError[ 0 ] ).append(
							`${ error.message } ${
								error.code ? `(${ error.code })` : ''
							} <br/>`
						);
					} );
				}

				return;
			}

			// Requires Distributor version 1.6.0.
			if (
				compareVersions.compare( response.data.version, '1.6.0', '<' )
			) {
				jQuery( wizardError[ 0 ] ).text( __( 'Remote site requires Distributor version 1.6.0 or greater. Upgrade Distributor on the remote site to use the Authentication Wizard.', 'distributor' ) );
				return;
			}

			if (
				'core_application_passwords_available' in response.data &&
				! response.data.core_application_passwords_available
			) {
				jQuery( wizardError[ 0 ] ).text(
					__( 'Application Passwords is not available on the remote site. Please set up connection manually!', 'distributor' )
				);
				return;
			}

			const successURL = addQueryArgs( document.location.href, {
				setupStatus: 'success',
				titleField: titleField.value,
				externalSiteUrlField: siteURL,
				restRoot: response.data.rest_url,
			} );

			const failureURL = addQueryArgs( document.location.href, {
				setupStatus: 'failure',
			} );

			const auth_page =
				'core_has_application_passwords' in response.data &&
				response.data.core_has_application_passwords
					? 'authorize-application.php'
					: 'admin.php?page=auth_app';

			let auth_url;

			if (
				'core_application_passwords_endpoint' in response.data &&
				response.data.core_application_passwords_available
			) {
				auth_url = response.data.core_application_passwords_endpoint;
			} else {
				auth_url = `${ siteURL }wp-admin/${ auth_page }`;
			}

			const authURL = addQueryArgs( auth_url, {
				/* translators: %1$s: site name, %2$s: site URL */
				app_name: sprintf(__( 'Distributor on %1$s (%2$s)', 'distributor' ), dt.blog_name, dt.home_url ) /*eslint camelcase: 0*/,
				success_url: encodeURI( successURL ) /*eslint camelcase: 0*/,
				reject_url: encodeURI( failureURL ) /*eslint camelcase: 0*/,
			} );
			document.location = authURL;
		} );

	return false;
} );

/**
 * Handle Manual Setup Connection button.
 *
 * This hides the wizard box and shows the
 * default fields.
 */
jQuery( manualSetupButton ).on( 'click', ( event ) => {
	event.preventDefault();

	jQuery( '.external-connection-wizard' ).hide();
	jQuery( '.external-connection-setup, .hide-until-authed' ).show();
} );

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
	endpointResult.innerText = __( 'Checking endpoint...', 'distributor' );

	endpointErrors.innerText = '';

	const auth = {};

	_.each( authFields, ( authField ) => {
		if ( authField.disabled ) {
			return;
		}

		const key = authField.getAttribute( 'data-auth-field' );

		if ( key ) {
			auth[ key ] = authField.value;
		}
	} );

	let postId = 0;
	if ( postIdField && postIdField.value ) {
		postId = postIdField.value;
	}

	$apiVerify = jQuery
		.ajax( {
			url: ajaxurl,
			method: 'post',
			data: {
				nonce: dt.nonce,
				action: 'dt_verify_external_connection',
				auth,
				url: externalConnectionUrlField.value,
				type: externalConnectionTypeField.value,
				endpointId: postId,
			},
		} )
		.done( ( response ) => {
			if ( ! response.success ) {
				endpointResult.setAttribute( 'data-endpoint-state', 'error' );
			} else if ( response.data.errors.no_external_connection ) {
				endpointResult.setAttribute( 'data-endpoint-state', 'error' );

				if ( response.data.endpoint_suggestion ) {
					endpointResult.innerText = `${ __( 'Did you mean: ', 'distributor' ) } `;

					const suggestion = document.createElement( 'button' );
					suggestion.classList.add( 'suggest' );
					suggestion.classList.add( 'button-link' );
					suggestion.setAttribute( 'type', 'button' );
					suggestion.innerText = response.data.endpoint_suggestion;

					endpointResult.appendChild( suggestion );

					speak(
						`${ __( 'Did you mean: ', 'distributor' ) } ${ response.data.endpoint_suggestion }`,
						'polite'
					);
				} else {
					endpointResult.innerText = __( 'No connection found.', 'distributor' );

					speak( __( 'No connection found.', 'distributor' ), 'polite' );
				}
			} else if (
				response.data.errors.no_distributor ||
				! response.data.can_post.length
			) {
				endpointResult.setAttribute( 'data-endpoint-state', 'warning' );
				endpointResult.innerText = __( 'Limited connection established.', 'distributor' );

				const warnings = [];

				if ( response.data.errors.no_distributor ) {
					endpointResult.innerText += ` ${ __( 'Distributor not installed on remote site.', 'distributor' ) }`;
					speak(
						`${ __( 'Limited connection established.', 'distributor' ) } ${ __( 'Distributor not installed on remote site.', 'distributor' ) }`,
						'polite'
					);
				} else {
					speak( `${ __( 'Limited connection established.', 'distributor' ) }`, 'polite' );
				}

				if ( 'no' === response.data.is_authenticated ) {
					warnings.push( __( 'Authentication failed due to invalid credentials.', 'distributor' ) );
				}

				if ( 'yes' === response.data.is_authenticated ) {
					warnings.push( __( 'Authentication succeeded but your account does not have permissions to create posts on the external site.', 'distributor' ) );
				}

				warnings.push( __( 'Push distribution unavailable.', 'distributor' ) );
				warnings.push( __( 'Pull distribution limited to basic content, i.e. title and content body.', 'distributor' ) );

				warnings.forEach( ( warning ) => {
					const warningNode = document.createElement( 'li' );
					warningNode.innerText = warning;

					endpointErrors.append( warningNode );
				} );
			} else {
				endpointResult.setAttribute( 'data-endpoint-state', 'valid' );
				endpointResult.innerText = __( 'Connection established.', 'distributor' );

				speak( __( 'Connection established.', 'distributor' ), 'polite' );
			}
		} )
		.always( () => {
			endpointResult.classList.remove( 'loading' );
		} );
}

// Initialize after load.
setTimeout( () => {
	// Repopulate fields on wizard flow.
	const { wizard_return } = dt;

	if ( wizard_return ) {
		if ( '' === titleField.value ) {
			jQuery( titleField ).val( wizard_return.titleField ).focus().blur();
			jQuery( titlePrompt ).empty();
		}
		jQuery( usernameField ).val( wizard_return.user_login );
		jQuery( passwordField ).val( wizard_return.password );
		jQuery( externalConnectionUrlField ).val( wizard_return.restRoot );
		wpbody.className = 'wizard-return';
		createConnection.click();
	}
	checkConnections();
}, 300 );

jQuery( externalConnectionMetaBox ).on( 'click', '.suggest', ( event ) => {
	externalConnectionUrlField.value = event.currentTarget.innerText;
	jQuery( externalConnectionUrlField ).trigger( 'input' );
} );

jQuery( externalConnectionUrlField ).on( 'focus click', ( event ) => {
	event.target.setAttribute( 'initial-url', event.target.value );
} );

jQuery( externalConnectionUrlField ).on(
	'keyup input',
	_.debounce( () => {
		if (
			externalConnectionUrlField.value.replace( /\/$/, '' ) ===
			externalConnectionUrlField
				.getAttribute( 'initial-url' )
				.replace( /\/$/, '' )
		) {
			return;
		}

		externalConnectionUrlField.setAttribute(
			'initial-url',
			externalConnectionUrlField.value
		);
		checkConnections();
	}, 250 )
);

jQuery( externalConnectionUrlField ).on( 'blur', ( event ) => {
	if ( '' === titleField.value && '' !== event.currentTarget.value ) {
		titleField.value = event.currentTarget.value.replace(
			/https?:\/\//i,
			''
		);
		titleField.focus();
		titleField.blur();
	}
} );
/**
 * JS for basic auth
 *
 * @todo  separate
 */
const passwordField = document.getElementById( 'dt_password' );
const usernameField = document.getElementById( 'dt_username' );
const changePassword = document.querySelector( '.change-password' );

jQuery( usernameField ).on( 'focus click', ( event ) => {
	event.target.setAttribute( 'initial-username', event.target.value );
} );

jQuery( usernameField ).on(
	'keyup input',
	_.debounce( () => {
		if (
			usernameField.getAttribute( 'initial-username' ) ===
			usernameField.value
		) {
			return;
		}
		if ( changePassword ) {
			passwordField.disabled = false;
			passwordField.value = '';
			changePassword.style.display = 'none';
		}
	}, 250 )
);

jQuery( passwordField ).on(
	'keyup input',
	_.debounce( () => {
		checkConnections();
	}, 250 )
);

jQuery( changePassword ).on( 'click', ( event ) => {
	event.preventDefault();

	if ( passwordField.disabled ) {
		passwordField.disabled = false;
		passwordField.value = '';
		event.currentTarget.innerText = esc_html__( 'Cancel', 'distributor' );
	} else {
		passwordField.disabled = true;
		passwordField.value = 'sdfdsfsdfdsfdsfsd'; // filler password
		event.currentTarget.innerText = esc_html__( 'Change', 'distributor' );
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

	if (
		'administrator' !== event.target.value &&
		'editor' !== event.target.value
	) {
		alert( __( 'Be careful assigning less trusted roles push privileges as they will inherit the capabilities of the user on the remote site.', 'distributor' ) ); // eslint-disable-line no-alert
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
	$clientSecret = jQuery( document.getElementById( 'dt_client_secret' ) ),
	$clientId = jQuery( document.getElementById( 'dt_client_id' ) ),
	hideItemsRequiringAuth = () => {
		const oauthconnectionestablished = document.getElementsByClassName(
			'oauth-connection-established'
		);
		if ( 0 === oauthconnectionestablished.length ) {
			$hideUntilAuthed.hide();
		}
	},
	/**
	 * Validate a form field, ensuring it is non-empty. Add an error class if empty.
	 *
	 * @param {jQuery.DomElement} $field The field to check.
	 * @param {jQuery.Event}      event  The event that triggered the validation.
	 */
	validateField = ( $field, event ) => {
		if ( '' === $field.val() ) {
			event.preventDefault();
			$field.addClass( 'error-required' );
			return false;
		}
		$field.removeClass( 'error-required' );

		return true;
	};

/**
 * When the External connection type drop-down is changed, show the corresponding authorization fields.
 */
jQuery( externalConnectionTypeField ).on( 'change', () => {
	// eslint-disable-next-line no-shadow
	const slug = externalConnectionTypeField.value;

	wpbody.className = slug;

	if ( 'wp' === slug ) {
		jQuery( '.external-connection-wizard' ).show();
	} else {
		jQuery( '.external-connection-setup, .hide-until-authed' ).hide();
	}
} );

// On load for WordPress.com Oauth authentication, hide fields until authentication is complete.
if ( 'wpdotcom' === externalConnectionTypeField.value ) {
	hideItemsRequiringAuth();
}

// When authorization is initiated, ensure fields are non-empty.
if ( createOauthConnectionButton ) {
	jQuery( createOauthConnectionButton ).on( 'click', ( event ) => {
		const validateClientSecret = validateField( $clientSecret, event ),
			validateClientId = validateField( $clientId, event );
		if ( ! validateClientSecret || ! validateClientId ) {
			event.preventDefault();
			return false;
		}
	} );
}

// Handle the changeCredentials link.
const changeCredentials = document.getElementById(
		'oauth-authentication-change-credentials'
	),
	$authenticationDetailsWrapper = jQuery(
		'.oauth-authentication-details-wrapper'
	);

if ( changeCredentials ) {
	jQuery( changeCredentials ).on( 'click', function () {
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
			jQuery
				.ajax( {
					url: ajaxurl,
					method: 'post',
					data: {
						nonce: dt.nonce,
						action: 'dt_begin_authorization',
						title,
						id: jQuery(
							document.getElementById( 'post_ID' )
						).val(),
					},
				} )
				.done( ( response ) => {
					if ( response.success && response.data.id ) {
						// The post has been saved, update the url in case the user refreshes.
						const url = `${ dt.admin_url }post.php?post=${ response.data.id }&action=edit`;
						history.pushState( {}, 'Oauth Authorize Details', url );

						// Update the form field for dt_redirect_uri and post id.
						jQuery(
							document.getElementById( 'dt_redirect_uri' )
						).val( url );
						jQuery(
							document.getElementById( 'dt_created_post_id' )
						).val( response.data.id );
						jQuery(
							document.getElementById( 'original_post_status' )
						).val( 'publish' );

						// Hide the first step and show the authentication details.
						jQuery( '.oauth-begin-authentication-wrapper' ).hide();
						$authenticationDetailsWrapper.show();
					} else {
						// @todo handle errors.
					}
				} )
				.always( () => {
					// Ensure the
					jQuery( beginAuthorize ).removeClass( 'disabled' );
				} );
		}
	} );
}

/**
 * Check if an element is hidden or not.
 *
 * @param {*} el Element to check.
 */
function isHidden( el ) {
	return null === el.offsetParent;
}
