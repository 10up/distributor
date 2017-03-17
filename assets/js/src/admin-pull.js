(function($) {
	'use strict';

	var chooseConnection = document.getElementById('pull_connections'),
		modal = document.getElementById('dt-locked-screen'),
		unlocked = false;

	$(chooseConnection).on('change', function(event) {
		document.location = event.currentTarget.options[event.currentTarget.selectedIndex].getAttribute('data-pull-url');
	});

	$(document).on('heartbeat-send', function(e, data) {
		data.nonce = DtData.nonce;
	});

	$(document).on('heartbeat-tick', function(e, data) {
		if ( true === data.unlocked && 'undefined' !== modal ) {
			unlocked = true;
			$(modal).dialog('close');
		}
	});

	if ( 'undefined' !== modal ) {
		$(modal).dialog({
			dialogClass : 'wp-dialog',
			title       : DtData.modal_title,
			autoOpen    : true,
			modal       : true,
			draggable   : false,
			resizable   : false,
			close       : function() {
				if ( true !== unlocked ) {
					window.location = DtData.dashboard_url;
				}
			}
		});
	}
})(jQuery);
