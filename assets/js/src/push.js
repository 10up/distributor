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
		var syndicateMenuItem = document.querySelector('#wp-admin-bar-syndicate a');
		var syndicatePushWrapper = document.querySelector('.syndicate-push-wrapper');
		var connectionsSelected = syndicatePushWrapper.querySelector('.connections-selected');
		var connectionsSelectedList = connectionsSelected.querySelector('.selected-connections-list');
		var connectionsNewList = syndicatePushWrapper.querySelector('.new-connections-list');
		var connectionsSearchInput = document.getElementById('sy-connection-search');
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
					sy_connections['internal' + connectionId].syndicated = result.url;
				}
			});

			_.each(results.external, function(result, connectionId) {
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

				connectionsSelected.classList.add('empty');
				connectionsSelectedList.innerHTML = '';

				setTimeout(function() {
					syndicatePushWrapper.classList.remove('message-success');
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

			_.each(sy_connections, function(connection, id) {
				if ('' !== searchString) {
					var nameMatch = connection.name.replace(/[^0-9a-zA-Z ]+/, '').match(searchString);
					var urlMatch = connection.url.replace(/https?:\/\//i, '').replace(/www/i, '').replace(/[^0-9a-zA-Z ]+/, '').match(searchString);

					if (!nameMatch && !urlMatch) {
						return;
					}
				}

				var showConnection = processTemplate('sy-add-connection')({
					connection: connection,
					selected: selectedConnections[connection.type + connection.id]
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
					action: 'sy_push',
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
