(function($) {

	'use strict';

	var selectedConnections = {},
		searchString = '';

	var processTemplate = _.memoize( function( id ) {
		var element = document.getElementById( id );
		if (!element) {
			return false;
		}

		// Use WordPress style Backbone template syntax
		var options = {
			evaluate:    /<#([\s\S]+?)#>/g,
			interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
			escape:      /\{\{([^\}]+?)\}\}(?!\})/g
		};

		return _.template( element.innerHTML, null, options );
	});

	$(window).load(function() {
		var distributorMenuItem = document.querySelector('#wp-admin-bar-distributor a');
		var distributorPushWrapper = document.querySelector('.distributor-push-wrapper');
		var connectionsSelected = distributorPushWrapper.querySelector('.connections-selected');
		var connectionsSelectedList = connectionsSelected.querySelector('.selected-connections-list');
		var connectionsNewList = distributorPushWrapper.querySelector('.new-connections-list');
		var connectionsSearchInput = document.getElementById('dt-connection-search');
		var syndicateButton = distributorPushWrapper.querySelector('.syndicate-button');
		var actionWrapper = distributorPushWrapper.querySelector('.action-wrapper');

		/**
		 * Handle UI error changes
		 */
		function doError() {
			distributorPushWrapper.classList.add('message-error');

			setTimeout(function() {
				distributorPushWrapper.classList.remove('message-error');
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
					dt_connections['internal' + connectionId].syndicated = result.url;
				}
			});

			_.each(results.external, function(result, connectionId) {
				if ('fail' === result.status) {
					error = true;
				} else {
					dt_connections['external' + connectionId].syndicated = true;
				}
			});

			if (error) {
				doError();
			} else {
				distributorPushWrapper.classList.add('message-success');

				connectionsSelected.classList.add('empty');
				connectionsSelectedList.innerHTML = '';

				setTimeout(function() {
					distributorPushWrapper.classList.remove('message-success');
				}, 6000);
			}

			selectedConnections = {};

			showConnections();
		}

		/**
		 * Show connections. If there is a search string, then filter by it
		 */
		function showConnections() {
			connectionsNewList.innerHTML = '';

			_.each(dt_connections, function(connection, id) {
				if ('' !== searchString) {
					var nameMatch = connection.name.replace(/[^0-9a-zA-Z ]+/, '').match(searchString);
					var urlMatch = connection.url.replace(/https?:\/\//i, '').replace(/www/i, '').replace(/[^0-9a-zA-Z ]+/, '').match(searchString);

					if (!nameMatch && !urlMatch) {
						return;
					}
				}

				var showConnection = processTemplate('dt-add-connection')({
					connection: connection,
					selectedConnections: selectedConnections
				});

				connectionsNewList.innerHTML += showConnection;
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
					action: 'dt_push',
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
		 * Show distributor dropdown
		 */
		$(distributorMenuItem).on('click', function(event) {
			event.preventDefault();

			if (document.body.classList.contains('distributor-show')) {
				distributorMenuItem.blur();
			} else {
				distributorMenuItem.focus();
			}

			document.body.classList.toggle('distributor-show');
		});

		/**
		 * Add a connection to selected connections for ajax and to the UI list.
		 */
		$(distributorPushWrapper).on('click', '.add-connection', function(event) {
			if ('A' === event.target.nodeName) {
				return;
			}

			event.preventDefault();

			if (event.currentTarget.classList.contains('syndicated')) {
				return;
			}

			if (event.currentTarget.classList.contains('added')) {
				return;
			}

			var type = event.currentTarget.getAttribute('data-connection-type');
			var id = event.currentTarget.getAttribute('data-connection-id');

			selectedConnections[type + id] = dt_connections[type + id];

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
		$(distributorPushWrapper).on('click', '.remove-connection', function(event) {
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
				showConnections(dt_connections);
			}

			searchString = event.currentTarget.value.replace(/https?:\/\//i, '').replace(/www/i, '').replace(/[^0-9a-zA-Z ]+/, '');

			showConnections();
		}, 300));
	});
})(jQuery);
