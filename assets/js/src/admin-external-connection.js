(function($) {
	var processTemplate = _.memoize(function(id) {
		var element = document.getElementById(id);
		if (!element) {
			return false;
		}

		// Use WordPress style Backbone template syntax
		var options = {
			evaluate:    /<#([\s\S]+?)#>/g,
			interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
			escape:      /\{\{([^\}]+?)\}\}(?!\})/g
		};

		return _.template(element.innerHTML, null, options);
	});

	var externalConnectionUrlField = document.getElementsByClassName('external-connection-url-field')[0];
	var externalConnectionMetaBox = document.getElementById('dt_external_connection_details');
	var externalConnectionVerificationMetaBox = document.getElementById('dt_external_connection_connection');
	var externalConnectionTypeField = document.getElementsByClassName('external-connection-type-field')[0];
	var authFields = document.getElementsByClassName('auth-field');
	var titleField = document.getElementById('title');
	var externalConnectionVerificationWrapper = document.querySelectorAll('#dt_external_connection_connection .inside')[0];
	var endpointResult = document.querySelector('.endpoint-result');
	var postIdField = document.getElementById('post_ID');
	var $apiVerify = false;
	var verificationTemplate = processTemplate('dt-external-connection-verification');

	function checkConnections(event) {
		if ($apiVerify !== false) {
			$apiVerify.abort();
		}

		if ('' == externalConnectionUrlField.value) {
			externalConnectionVerificationWrapper.innerHTML = '<p>' + sy.no_connection_check + '</p>';
			endpointResult.innerText = sy.will_confirm_endpoint;
			return;
		}

		endpointResult.classList.add('loading');
		endpointResult.innerHTML = sy.endpoint_checking_message;

		externalConnectionVerificationMetaBox.classList.add('loading');

		var auth = {};

		_.each(authFields, function(authField) {
			if (authField.disabled) {
				return;
			}

			var key = authField.getAttribute('data-auth-field');

			if (key) {
				auth[key] = authField.value;
			}
		});

		var postId = 0;
		if (postIdField && postIdField.value) {
			postId = postIdField.value;
		}

		$apiVerify = $.ajax({
			url: ajaxurl,
			method: 'post',
			data: {
				nonce: sy.nonce,
				action: 'dt_verify_external_connection',
				auth: auth,
				url: externalConnectionUrlField.value,
				type: externalConnectionTypeField.value,
				endpoint_id: postId
			}
		}).done(function(response) {
			if (!response.success) {
				if (!event || event.currentTarget.classList.contains('external-connection-url-field')) {
					endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
					endpointResult.innerHTML += sy.invalid_endpoint;
				}

				externalConnectionVerificationWrapper.innerHTML = verificationTemplate({
					errors: ['no_external_connection'],
					can_post: [],
					can_get: []
				});
			} else {
				if (response.data.errors.no_external_connection) {
					endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
					endpointResult.innerHTML += sy.invalid_endpoint;

					if (response.data.endpoint_suggestion) {
						endpointResult.innerHTML += ' ' + sy.endpoint_suggestion + ' <a class="suggest">' + response.data.endpoint_suggestion + '</a>'; 
					}
				} else if (!Object.keys(response.data.errors).length || (1 === Object.keys(response.data.errors).length && response.data.errors.no_types)) {
					endpointResult.innerHTML = '<span class="dashicons dashicons-yes"></span>';
					endpointResult.innerHTML += sy.valid_endpoint;
				}

				externalConnectionVerificationWrapper.innerHTML = verificationTemplate({
					errors: response.data.errors,
					can_post: response.data.can_post,
					can_get: response.data.can_get
				});
			}
		}).complete(function() {
			externalConnectionVerificationMetaBox.classList.remove('loading');
			endpointResult.classList.remove('loading');
		});
	}

	setTimeout(function() {
		checkConnections();
	}, 300);

	$(externalConnectionMetaBox).on('click', '.suggest', function(event) {
		externalConnectionUrlField.value = event.currentTarget.innerText;
		$(externalConnectionUrlField).trigger('input');
	});

	$(externalConnectionMetaBox).on('keyup input', '.auth-field, .external-connection-url-field', _.debounce(checkConnections, 250));

	$(externalConnectionUrlField).on('blur', function(event) {
		if ('' === titleField.value && '' !== event.currentTarget.value) {
			titleField.value = event.currentTarget.value.replace(/https?:\/\//i, '');
			titleField.focus();
			titleField.blur();
		}
	});
	/**
	 * JS for basic auth
	 *
	 * @todo  separate
	 */
	var passwordField = document.getElementById('dt_password');
	var usernameField = document.getElementById('dt_username');
	var changePassword = document.querySelector('.change-password');

	$(usernameField).on('keyup change', _.debounce(function() {
		if (changePassword) {
			passwordField.disabled = false;
			passwordField.value = '';
			changePassword.style.display = 'none';
		}
	}, 250));

	$(changePassword).on('click', function(event) {
		event.preventDefault();

		if (passwordField.disabled) {
			passwordField.disabled = false;
			passwordField.value = '';
			event.currentTarget.innerText = sy.cancel;
		} else {
			passwordField.disabled = true;
			passwordField.value = 'sdfdsfsdfdsfdsfsd'; // filler password
			event.currentTarget.innerText = sy.change;
		}

		checkConnections();
	});
})(jQuery);
