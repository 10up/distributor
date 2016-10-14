(function($) {

	var selectedConnections = {},
		searchString = '';

	$(window).load(function() {
		var syndicateMenuItem = document.querySelector('#wp-admin-bar-syndicate a');
		var syndicatePushWrapper = document.querySelector('.syndicate-push-wrapper');
		var connectionsSelected = syndicatePushWrapper.querySelector('.connections-selected');
		var connectionsSelectedList = connectionsSelected.querySelector('.selected-connections-list');
		var connectionsNewList = syndicatePushWrapper.querySelector('.new-connections-list');
		var connectionsSearchInput = document.getElementById('sy-connection-search');
		var asDraftInput = document.getElementById('syndicate-as-draft');
		var syndicateButton = syndicatePushWrapper.querySelector('.syndicate-button');
		var actionWrapper = syndicatePushWrapper.querySelector('.action-wrapper');

		/**
		 * Handle UI error changes
		 */
		function doError() {
			syndicatePushWrapper.classList.add('message-error');

			setTimeout(function() {
				syndicatePushWrapper.classList.remove('message-error');
			}, 6000);
		}

		/**
		 * Handle UI success changes
		 */
		function doSuccess(results) {
			var error = false;

			_.each(results.internal, function(result, connectionId) {
				if ('fail' === result.status) {
					error = true;
				} else {
					sy_connections['internal' + connectionId].syndicated = true;
				}
			});

			_.each(results.external, function(result) {
				if ('fail' === result.status) {
					error = true;
				} else {
					sy_connections['external' + connectionId].syndicated = true;
				}
			});

			if (error) {
				doError();
			} else {
				syndicatePushWrapper.classList.add('message-success');

				_.each(selectedConnections, function(connection, connectionId) {
					sy_connections[connectionId].syndicated = true;
				});

				selectedConnections = {};

				connectionsSelected.classList.add('empty');
				connectionsSelectedList.innerHTML = '';

				setTimeout(function() {
					syndicatePushWrapper.classList.remove('message-success');
				}, 6000);
			}

			showConnections();
		}

		/**
		 * Show connections. If there is a search string, then filter by it
		 */
		function showConnections() {
			connectionsNewList.innerHTML = '';

			_.each(sy_connections, function(connection, id) {
				if ('' !== searchString) {
					var nameMatch = connection.name.replace(/[^0-9a-zA-Z ]+/, '').match(searchString);
					var urlMatch = connection.url.replace(/https?:\/\//i, '').replace(/www/i, '').replace(/[^0-9a-zA-Z ]+/, '').match(searchString);

					if (!nameMatch && !urlMatch) {
						return;
					}
				}

				var showConnection = document.createElement('a');

				if ('external' === sy_connections[id].type) {
					showConnection.innerText = connection.name;
				} else {
					showConnection.innerText = connection.url;
				}

				showConnection.setAttribute('data-connection-type', connection.type);
				showConnection.setAttribute('data-connection-id', connection.id);

				if (selectedConnections[connection.type + connection.id]) {
					showConnection.classList.add('added');
				}

				showConnection.classList.add('add-connection');

				if (connection.syndicated) {
					showConnection.classList.add('syndicated');
				}

				connectionsNewList.appendChild(showConnection);
			});
		}

		/**
		 * Do syndication ajax
		 */
		$(syndicateButton).on('click', function(event) {
			if (actionWrapper.classList.contains('loading')) {
				return;
			}

			actionWrapper.classList.add('loading');

			$.ajax({
				url: sy.ajaxurl,
				method: 'post',
				data: {
					action: 'sy_push',
					draft: (asDraftInput.checked) ? 1 : 0,
					nonce: sy.nonce,
					connections: selectedConnections,
					post_id: sy.post_id
				}
			}).done(function(response) {
				setTimeout(function() {
					actionWrapper.classList.remove('loading');

					if (!response.data || !response.data.results) {
						doError();
						return;
					}

					doSuccess(response.data.results);
				}, 500);
			}).error(function() {
				setTimeout(function() {
					actionWrapper.classList.remove('loading');

					doError();
				}, 500);
			});
		});

		/**
		 * Show syndication dropdown
		 */
		$(syndicateMenuItem).on('click', function(event) {
			event.preventDefault();

			if (document.body.classList.contains('syndicate-show')) {
				syndicateMenuItem.blur();
			} else {
				syndicateMenuItem.focus();
			}

			document.body.classList.toggle('syndicate-show');
		});

		/**
		 * Add a connection to selected connections for ajax and to the UI list.
		 */
		$(syndicatePushWrapper).on('click', '.add-connection', function(event) {
			event.preventDefault();

			if (event.currentTarget.classList.contains('syndicated')) {
				return;
			}

			if (event.currentTarget.classList.contains('added')) {
				return;
			}

			var type = event.currentTarget.getAttribute('data-connection-type');
			var id = event.currentTarget.getAttribute('data-connection-id');

			selectedConnections[type + id] = sy_connections[type + id];

			connectionsSelected.classList.remove('empty');

			var element = event.currentTarget.cloneNode();
			element.innerText = event.currentTarget.innerText;
			element.innerHTML += '<span class="remove-connection"></span>';
			element.classList = 'added-connection';

			connectionsSelectedList.appendChild(element);

			showConnections();
		});

		/**
		 * Remove a connection from selected connections and the UI list
		 */
		$(syndicatePushWrapper).on('click', '.remove-connection', function(event) {
			event.currentTarget.parentNode.parentNode.removeChild(event.currentTarget.parentNode);
			var type = event.currentTarget.parentNode.getAttribute('data-connection-type');
			var id = event.currentTarget.parentNode.getAttribute('data-connection-id');

			delete selectedConnections[type + id];

			if (!Object.keys(selectedConnections).length) {
				connectionsSelected.classList.add('empty');
			}

			showConnections();
		});

		/**
		 * List for connection filtering
		 */
		$(connectionsSearchInput).on('keyup change', _.debounce(function(event) {
			if('' === event.currentTarget.value) {
				showConnections(sy_connections);
			}

			searchString = event.currentTarget.value.replace(/https?:\/\//i, '').replace(/www/i, '').replace(/[^0-9a-zA-Z ]+/, '');

			showConnections();
		}, 300));
	});
})(jQuery);
