import { dispatch } from '@wordpress/data';
import { __, setLocaleData, sprintf } from '@wordpress/i18n';

const { wp, dtGutenberg } = window;

setLocaleData( dtGutenberg.i18n, 'distributor' );

if ( '0' !== dtGutenberg.originalSourceId || '0' !== dtGutenberg.originalBlogId ) {

	let message = '';
	const actions = [];

	if ( parseInt( dtGutenberg.originalDelete ) ) {
		message = sprintf( __( 'This %1$s was distributed from %2$s. However, the original has been deleted.' ), dtGutenberg.postTypeSingular, dtGutenberg.originalLocationName );
	} else if ( ! parseInt( dtGutenberg.unlinked ) ) {
		message = sprintf( __( 'Distributed from %s. The original will update this version unless you', 'distributor' ), dtGutenberg.originalLocationName );

		actions.push( {
			label: __( 'unlink from original.', 'distributor' ),
			url: dtGutenberg.unlinkNonceUrl
		} );

		actions.push( {
			label: __( 'View Original', 'distributor' ),
			url: dtGutenberg.postUrl,
		} );


	} else {
		message = sprintf( __( 'Originally distributed from %1$s. This %2$s has been unlinked from the original. However, you can always', 'distributor' ), dtGutenberg.originalLocationName, dtGutenberg.postTypeSingular );

		actions.push( {
			label: __( 'restore it.', 'distributor' ),
			url: dtGutenberg.linkNonceUrl
		} );

		actions.push( {
			label: __( 'View Original', 'distributor' ),
			url: dtGutenberg.postUrl
		} );
	}

	dispatch( 'core/notices' ).createWarningNotice( message, {
		id: 'distributor-notice',
		actions,
	} );
}
