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
	 * When the External connection type drop-down is changed, show the corresponding authorization fields.
	 */
	$( externalConnectionTypeField ).on( 'change', function( event ) {
		var slug = externalConnectionTypeField.value;
		console.log( externalConnectionTypeField.value, event );
		$( '.auth-credentials' ).hide();
		$( '.auth-credentials.' + slug ).show();
	} );

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
