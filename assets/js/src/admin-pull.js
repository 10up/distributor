(function($) {
	var chooseConnection = document.getElementById('pull_connections');

	$(chooseConnection).on('change', function(event) {
		document.location = event.currentTarget.options[event.currentTarget.selectedIndex].getAttribute('data-pull-url');
	});

})(jQuery);