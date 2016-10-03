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
	var postIdField = document.getElementById('post_ID');
	var $apiVerify = false;
	var verificationTemplate = processTemplate('sy-external-connection-verification');

	function checkConnections(event) {
		if ($apiVerify !== false) {
			$apiVerify.abort();
		}

		if ('' == externalConnectionUrlField.value) {
			externalConnectionVerificationWrapper.innerHTML = '<p>' + sy.no_connection_check + '</p>';
			endpointResult.innerText = sy.will_confirm_endpoint;
			return;
		}

		if (!event || event.currentTarget.classList.contains('external-connection-url-field')) {
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

		var postId = 0;
		if (postIdField && postIdField.value) {
			postId = postIdField.value;
		}

		$apiVerify = $.ajax({
			url: ajaxurl,
			method: 'post',
			data: {
				nonce: sy.nonce,
				action: 'sy_verify_external_connection',
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
				if (!event || event.currentTarget.classList.contains('external-connection-url-field')) {
					if (response.data.errors.no_external_connection) {
						endpointResult.innerHTML = '<span class="dashicons dashicons-warning"></span>';
						endpointResult.innerHTML += sy.invalid_endpoint;

						if (response.data.endpoint_suggestion) {
							endpointResult.innerHTML += ' ' + sy.endpoint_suggestion + ' <strong>' + response.data.endpoint_suggestion + '</strong>'; 
						}
					}

					if (!Object.keys(response.data.errors).length || (1 === Object.keys(response.data.errors).length && response.data.errors.no_types)) {
						endpointResult.innerHTML = '<span class="dashicons dashicons-yes"></span>';
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
	}

	setTimeout(function() {
		checkConnections();
	}, 300)

	$(externalConnectionMetaBox).on('keyup input', '.auth-field, .external-connection-url-field', _.debounce(checkConnections, 250));
})(jQuery);