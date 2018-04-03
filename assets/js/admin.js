import jQuery from 'jquery'
import { dtAdmin, ajaxurl } from 'window'

jQuery( '.notice' ).on( 'click', '.notice-dismiss', ( event ) => {
	const notice = event.delegateTarget.getAttribute( 'data-notice' );
	if ( ! notice ) {
		return;
	}

	jQuery.ajax( {
		method: 'post',
		data: {
			nonce: dtAdmin.nonce,
			action: 'dt_notice_dismiss',
			notice: notice
		},
		url: ajaxurl
	} );
} );
