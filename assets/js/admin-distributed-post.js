import '../css/admin-distributed-post.css';

import jQuery from 'jquery';

const openLinks      = document.querySelectorAll( '.open-distributor-help' );
const helpLink       = document.getElementById( 'contextual-help-link' );
const distributorTab = document.querySelector( '#tab-link-distributer a' );

for ( let i = 0; i < openLinks.length; i++ ) {
	jQuery( openLinks[i] ).on( 'click', () => {
		helpLink.click();
		distributorTab.click();
	} );
}
