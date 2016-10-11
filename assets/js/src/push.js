(function($) {
	var connections = {};

	$(window).load(function() {
		var syndicateMenuItem = document.querySelector('#wp-admin-bar-syndicate a');
		var syndicatePushWrapper = document.querySelector('.syndicate-push-wrapper');
		var connectionsSelectedList = syndicatePushWrapper.querySelector('.connections-selected .selected-connections-list');

		$(syndicateMenuItem).on('click', function(event) {
			event.preventDefault();

			document.body.classList.toggle('syndicate-show');
		});

		$(syndicatePushWrapper).on('click', '.add-connection', function(event) {
			event.preventDefault();

			event.currentTarget.classList.add('added');

			var type = event.currentTarget.getAttribute('data-connection-type');
			var id = event.currentTarget.getAttribute('data-connection-id');

			connections[type + id] = {
				type: type,
				id: id
			};

			var element = event.currentTarget.cloneNode();
			element.innerText = event.currentTarget.innerText;
			element.innerHTML += '<span class="remove-connection"></span>';
			element.classList = 'added-connection';

			connectionsSelectedList.appendChild(element);
		});

		$(syndicatePushWrapper).on('click', '.remove-connection', function(event) {
			event.currentTarget.parentNode.parentNode.removeChild(event.currentTarget.parentNode);
		});
	});
})(jQuery);
