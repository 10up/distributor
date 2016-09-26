(function($) {
	var syndicateButton = document.getElementsByClassName('js-syndicate')[0],
		postIdField = document.getElementById('post_ID'),
		pushMetaBox = document.getElementById('sy_push');
	
	var message = pushMetaBox.querySelector('.message');

	var externalConnectionsTable = document.querySelector('.external-connections-table');

	if (externalConnectionsTable) {
		var externalConnectionCheckboxes = externalConnectionsTable.getElementsByClassName('js-connection-checkbox');
	}
	var internalConnectionsTable = document.querySelector('.internal-connections-table');

	if (internalConnectionsTable) {
		var internalConnectionCheckboxes = internalConnectionsTable.getElementsByClassName('js-connection-checkbox');
	}

	var checked = 0;

	$(pushMetaBox).on('click', 'input[type=checkbox]', function(event) {
		if (event.currentTarget.checked) {
			checked++;
		} else {
			checked--;
		}

		if (0 >= checked) {
			syndicateButton.disabled = true;
		} else {
			syndicateButton.disabled = false;
		}
	});

	/**
	 * Check all/none
	 */
	$(pushMetaBox).on('click', '.check', function(event) {
		event.preventDefault();

		if (event.currentTarget.classList.contains('check')) {
			var status = event.currentTarget.getAttribute('data-check');
			var parentTable = event.currentTarget.parentNode.parentNode.parentNode;

			var boxes = parentTable.querySelectorAll('.js-connection-checkbox');

			$.each(boxes, function(index, box) {
				if ( 'all' === status ) {
					box.click();
					box.checked = true;
				} else {
					box.click();
					box.checked = false;
				}
			});
		}
	});

	function showMessage(messageText) {
		if (!messageText) {
			message.innerText = '';
			return;
		}

		message.innerText = messageText;

		setTimeout(function() {
			message.innerText = '';
		}, 4000);
	}

	function showFeedback(results) {
		$.each(results.internal, function(blogId, result) {
			var row = internalConnectionsTable.querySelector('.row.connection[data-connection-id="' + parseInt(blogId) + '"]');

			if ('success' === result.status) {
				row.classList.add('success');

				var dateColumn = row.querySelector('.last-syndicated');
				dateColumn.innerText = result.date;
			} else {
				row.classList.add('fail');
			}

			setTimeout(function() {
				row.classList.remove('success');
				row.classList.remove('fail');
			}, 4000);
		});

		$.each(results.external, function(externalId, result) {
			var row = externalConnectionsTable.querySelector('.row.connection[data-connection-id="' + parseInt(externalId) + '"]');

			if ('success' === result.status) {
				row.classList.add('success');

				var dateColumn = row.querySelector('.last-syndicated');
				dateColumn.innerText = result.date;
			} else {
				row.classList.add('fail');
			}

			setTimeout(function() {
				row.classList.remove('success');
				row.classList.remove('fail');
			}, 4000);
		});
	}

	/**
	 * Push content
	 */
	$(syndicateButton).on('click', function(event) {
		event.preventDefault();
		event.stopPropagation();

		showMessage(false);

		pushMetaBox.classList.add('loading');

		var external_connections = [];
		var internal_connections = [];

		if (externalConnectionsTable) {
			$.each(externalConnectionCheckboxes, function(index, externalConnectionElement) {
				if (externalConnectionElement.checked) {
					external_connections.push(externalConnectionElement.getAttribute('data-external-connection-id'));
				}
			});
		}

		if (internalConnectionsTable) {
			$.each(internalConnectionCheckboxes, function(index, internalConnectionElement) {
				if (internalConnectionElement.checked) {
					internal_connections.push(internalConnectionElement.getAttribute('data-blog-id'));
				}
			});
		}

		$.ajax({
			url: ajaxurl,
			method: 'post',
			data: {
				action: 'sy_push',
				nonce: sy.nonce,
				external_connections: external_connections,
				internal_connections: internal_connections,
				post_id: postIdField.value
			}
		}).done(function(response) {
			setTimeout(function() {
				pushMetaBox.classList.remove('loading');

				showFeedback(response.data.results);

				showMessage(sy.successful_syndication);
			}, 500);
		}).error(function() {
			setTimeout(function() {
				pushMetaBox.classList.remove('loading');

				showMessage(sy.failed_syndication);
			}, 500);
		});
	});
})(jQuery);
