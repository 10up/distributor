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
	var externalConnectionMetaBox = document.getElementById('sy_external_connection_details');
	var externalConnectionVerificationMetaBox = document.getElementById('sy_external_connection_connection');
	var externalConnectionTypeField = document.getElementsByClassName('external-connection-type-field')[0];
	var authFields = document.getElementsByClassName('auth-field');
	var externalConnectionVerificationWrapper = document.querySelectorAll('#sy_external_connection_connection .inside')[0];
	var endpointResult = document.querySelector('.endpoint-result');
	var $apiVerify = false;
	var verificationTemplate = processTemplate('sy-external-connection-verification');

	$(externalConnectionMetaBox).on('keyup change input', '.auth-field, .external-connection-url-field', _.debounce(function(event) {
		if ($apiVerify !== false) {
			$apiVerify.abort();
		}

		if (event.currentTarget.classList.contains('external-connection-url-field')) {
			endpointResult.classList.add('loading');
			endpointResult.innerHTML = sy.endpoint_checking_message;
		}

		externalConnectionVerificationMetaBox.classList.add('loading');

		var auth = {};

		_.each(authFields, function(authField) {
			var key = authField.getAttribute('data-auth-field');

			if (key) {
				auth[key] = authField.value;
			}
		});

		$apiVerify = $.ajax({
			url: ajaxurl,
			method: 'post',
			data: {
				nonce: sy.nonce,
				action: 'sy_verify_external_connection',
				auth: auth,
				url: externalConnectionUrlField.value,
				type: externalConnectionTypeField.value
			}
		}).done(function(response) {
			if (!response.success) {
				if (event.currentTarget.classList.contains('external-connection-url-field')) {
					endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
					endpointResult.innerHTML += sy.invalid_endpoint;
				}

				externalConnectionVerificationWrapper.innerHTML = verificationTemplate({
					errors: ['no_external_connection'],
					can_post: [],
					can_get: []
				});
			} else {
				if (event.currentTarget.classList.contains('external-connection-url-field')) {
					if (response.data.errors.no_external_connection) {
						endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
						endpointResult.innerHTML += sy.invalid_endpoint;

						if (response.data.endpoint_suggestion) {
							endpointResult.innerHTML += ' ' + sy.endpoint_suggestion + ' <strong>' + response.data.endpoint_suggestion + '</strong>'; 
						}
					}

					if (!Object.keys(response.data.errors).length) {
						endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
						endpointResult.innerHTML += sy.valid_endpoint;
					}
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
	}, 250));
})(jQuery);