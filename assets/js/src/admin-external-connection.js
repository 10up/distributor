(function($) {
	var processTemplate = _.memoize(
		function(id) {
			var element = document.getElementById( id );
			if ( ! element) {
				return false;
			}

			// Use WordPress style Backbone template syntax
			var options = {
				evaluate:    /<#([\s\S]+?)#>/g,
				interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
				escape:      /\{\{([^\}]+?)\}\}(?!\})/g
			};

			return _.template( element.innerHTML, null, options );
		}
	);

	var externalConnectionUrlField  = document.getElementsByClassName( 'external-connection-url-field' )[0];
	var externalConnectionMetaBox   = document.getElementById( 'dt_external_connection_details' );
	var externalConnectionTypeField = document.getElementsByClassName( 'external-connection-type-field' )[0];
	var authFields                  = document.getElementsByClassName( 'auth-field' );
	var rolesAllowed                = document.getElementsByClassName( 'dt-roles-allowed' );
	var titleField                  = document.getElementById( 'title' );
	var endpointResult              = document.querySelector( '.endpoint-result' );
	var endpointErrors              = document.querySelector( '.endpoint-errors' );
	var postIdField                 = document.getElementById( 'post_ID' );
	var $apiVerify                  = false;

	function checkConnections(event) {
		if ($apiVerify !== false) {
			$apiVerify.abort();
		}

		if ('' === externalConnectionUrlField.value) {
			endpointErrors.innerText = '';
			endpointResult.innerText = '';

			endpointResult.removeAttribute( 'data-endpoint-state' );
			return;
		}

		endpointResult.setAttribute( 'data-endpoint-state', 'loading' );
		endpointResult.innerText = dt.endpoint_checking_message;

		endpointErrors.innerText = '';

		var auth = {};

		_.each(
			authFields, function(authField) {
				if (authField.disabled) {
					return;
				}

				var key = authField.getAttribute( 'data-auth-field' );

				if (key) {
					auth[key] = authField.value;
				}
			}
		);

		var postId = 0;
		if (postIdField && postIdField.value) {
			postId = postIdField.value;
		}

		$apiVerify = $.ajax(
			{
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
			}
		).done(
			function(response) {
				if ( ! response.success) {
					endpointResult.setAttribute( 'data-endpoint-state', 'error' );
				} else {
					if (response.data.errors.no_external_connection) {
						endpointResult.setAttribute( 'data-endpoint-state', 'error' );

						if (response.data.endpoint_suggestion) {
							endpointResult.innerText = dt.endpoint_suggestion + ' ';

							var suggestion = document.createElement( 'a' );
							suggestion.classList.add( 'suggest' );
							suggestion.innerText = response.data.endpoint_suggestion;

							endpointResult.appendChild( suggestion );
						} else {
							endpointResult.innerText = dt.bad_connection;
						}
					} else {
						if (response.data.errors.no_distributor || ! response.data.can_post.length) {
							endpointResult.setAttribute( 'data-endpoint-state', 'warning' );
							endpointResult.innerText = dt.limited_connection;

							var warnings = [];

							if ( ! response.data.can_post.length) {
								warnings.push( dt.bad_auth );
							}

							if (response.data.errors.no_distributor) {
								warnings.push( dt.no_distributor );
							}

							warnings.forEach(
								function(warning) {
										var warningNode       = document.createElement( 'li' );
										warningNode.innerText = warning;

										endpointErrors.append( warningNode );
								}
							);
						} else {
							endpointResult.setAttribute( 'data-endpoint-state', 'valid' );
							endpointResult.innerText = dt.good_connection;
						}
					}
				}
			}
		).complete(
			function() {
					endpointResult.classList.remove( 'loading' );
			}
		);
	}

	setTimeout(
		function() {
			checkConnections();
		}, 300
	);

	/**
	 * If the client id and secret are unavailable, hide all '.hide-until-authed' areas.
	 *
	 * For Oauth authentication, simplify the interface by hiding certain elements until the user has
	 * completed the authorization process.
	 *
	 * Creates a cleaner flow for authorization by separating the authorization steps.
	 */
	var $hideUntilAuthed = $( '.hide-until-authed' ),
		$authCredentials = $( '.auth-credentials' ),
		$clientSecret    = $( document.getElementById( 'dt_client_secret' ) ),
		$clientId        = $( document.getElementById( 'dt_client_id' ) ),
		hideItemsRequiringAuth = function() {
			oauthconnectionestablished = document.getElementsByClassName( 'oauth-connection-established' );
			if ( 0 === oauthconnectionestablished.length ) {
				$hideUntilAuthed.hide();
			}
		},

		/**
		 * Validate a form field, ensuring it is non-empty. Add an error class if empty.
		 *
		 * @param  {jQuery DomElement} $field The field to check.
		 */
		validateField = function( $field, event ) {
			if ( '' === $field.val() ) {
				event.preventDefault();
				$field.addClass( 'error-required' );
				return false;
			} else {
				$field.removeClass( 'error-required' );
			}
			return true;
		}

	/**
	 * When the External connection type drop-down is changed, show the corresponding authorization fields.
	 */
	$( externalConnectionTypeField ).on( 'change', function( event ) {
		var slug = externalConnectionTypeField.value;

		$authCredentials.hide();
		$( '.auth-credentials.' + slug ).show();

		// For WordPress.com Oauth authentication, hide fields until authentication is complete.
		if ( 'wpdotcom' === slug ) {
			hideItemsRequiringAuth();
		} else {

			// Otherwise, ensure all areas are showing.
			$hideUntilAuthed.show()
		}
	} );


	/**
	 *
	 * Code for WordPress.com Oauth2 Authentication.
	 *
	 */
	// On load for WordPress.com Oauth authentication, hide fields until authentication is complete.
	if ( 'wpdotcom' === $( externalConnectionTypeField ).val() ) {
		hideItemsRequiringAuth();
	}

	// When authorization is initiated, ensure fields are non-empty.
	var createConnectionButton = document.getElementById( 'create-oauth-connection' );
	if ( createConnectionButton ) {
		$( createConnectionButton ).on( 'click', function( event ) {
			var validateClientSecret = validateField( $clientSecret, event ),
				validateClientId     = validateField( $clientId, event )
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
	var changeCredentials = document.getElementById( 'oauth-authentication-change-credentials' );
	$authenticationDetailsWrapper = $( '.oauth-authentication-details-wrapper' );

	if ( changeCredentials ) {

		$( changeCredentials ).on( 'click', function() {

			// Show the credentials fields.
			$authenticationDetailsWrapper.show();

			// Clear the secret field.
			$clientSecret.val( '' );

			// Remove the authorized message.
			$( '.oauth-connection-established' ).remove();

			// Hide the remaining fields that only show after authorization is complete.
			hideItemsRequiringAuth();
		} );
	}

	// Handle the Authorize Connection button.
	var beginAuthorize = document.getElementById( 'begin-authorization' );
	if ( beginAuthorize ) {

		// Handle click to the wpdotcom begin-authorization button.
		$( beginAuthorize ).on( 'click', function( event ) {
				var $titleEl = $( titleField ),
					title = $titleEl.val();

				// Ensure the connection title is not blank.
				if ( validateField( $titleEl, event ) ) {

					// Disable the button during the ajax request.
					$( beginAuthorize ).addClass( 'disabled' );

					// Remove any error highlighting.
					$titleEl.removeClass( 'error-required' );

					// Make an ajax request to save the connection and retrieve the resulting post id.
					$.ajax(
						{
							url: ajaxurl,
							method: 'post',
							data: {
								nonce: dt.nonce,
								action: 'dt_begin_authorization',
								title: title
							}
						}
					).done( function( response ) {
						if ( response.success && response.data.id ) {

							// The post has been saved, update the url in case the user refreshes.
							var url = dt.admin_url + 'post.php?post=' + response.data.id  + '&action=edit';
							history.pushState( {}, 'Oauth Authorize Details', url );

							// Update the form field for dt_redirect_uri and post id.
							$( document.getElementById( 'dt_redirect_uri' ) ).val( url );
							$( document.getElementById( 'dt_created_post_id' ) ).val( response.data.id );

							// Hide the first step and show the authentication details.
							$( '.oauth-begin-authentication-wrapper' ).hide();
							$authenticationDetailsWrapper.show();
						} else {
							// @todo handle errors.
						}
					} ).complete( function() {

						// Ensure the
						$( beginAuthorize ).removeClass( 'disabled' );
					} );
				}
		} );
	}

	$( externalConnectionMetaBox ).on(
		'click', '.suggest', function(event) {
			externalConnectionUrlField.value = event.currentTarget.innerText;
			$( externalConnectionUrlField ).trigger( 'input' );
		}
	);

	$( externalConnectionMetaBox ).on( 'keyup input', '.auth-field, .external-connection-url-field', _.debounce( checkConnections, 250 ) );

	$( externalConnectionUrlField ).on(
		'blur', function(event) {
			if ('' === titleField.value && '' !== event.currentTarget.value) {
				titleField.value = event.currentTarget.value.replace( /https?:\/\//i, '' );
				titleField.focus();
				titleField.blur();
			}
		}
	);
	/**
	 * JS for basic auth
	 *
	 * @todo  separate
	 */
	var passwordField  = document.getElementById( 'dt_password' );
	var usernameField  = document.getElementById( 'dt_username' );
	var changePassword = document.querySelector( '.change-password' );

	$( usernameField ).on(
		'keyup change', _.debounce(
			function() {
				if (changePassword) {
					passwordField.disabled       = false;
					passwordField.value          = '';
					changePassword.style.display = 'none';
				}
			}, 250
		)
	);

	$( changePassword ).on(
		'click', function(event) {
			event.preventDefault();

			if (passwordField.disabled) {
				passwordField.disabled        = false;
				passwordField.value           = '';
				event.currentTarget.innerText = dt.cancel;
			} else {
				passwordField.disabled        = true;
				passwordField.value           = 'sdfdsfsdfdsfdsfsd'; // filler password
				event.currentTarget.innerText = dt.change;
			}

			checkConnections();
		}
	);

	$( rolesAllowed ).on(
		'click', '.dt-role-checkbox', function(event) {
			if ( ! event.target.classList.contains( 'dt-role-checkbox' )) {
				return;
			}

			if ( ! event.target.checked) {
				return;
			}

			if ('administrator' !== event.target.value && 'editor' !== event.target.value) {
				alert( dt.roles_warning );
			}
		}
	);
})( jQuery );
