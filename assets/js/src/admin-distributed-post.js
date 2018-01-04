(function() {
	var openLinks      = document.querySelectorAll( '.open-distributor-help' );
	var helpLink       = document.getElementById( 'contextual-help-link' );
	var distributorTab = document.querySelector( '#tab-link-distributer a' );

	for ( var i = 0; i < openLinks.length; i++ ) {
		$( openLinks[i] ).on(
			'click', function() {
				helpLink.click();
				distributorTab.click();
			}
		);
	}
})();
