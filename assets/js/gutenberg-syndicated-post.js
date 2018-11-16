import { wp, dtGutenberg } from 'window';


wp.i18n.setLocaleData( dtGutenberg.i18n, 'distributor' );

if ( '0' !== dtGutenberg.originalSourceId || '0' !== dtGutenberg.originalBlogId ) {

	let message = '';
	const actions = [];

	if ( parseInt( dtGutenberg.originalDelete ) ) {
		message = wp.i18n.sprintf( wp.i18n.__( 'This %1$s was distributed from %2$s. However, the original has been deleted.' ), dtGutenberg.postTypeSingular, dtGutenberg.originalLocationName );
	} else if ( ! parseInt( dtGutenberg.unlinked ) ) {
		message = wp.i18n.sprintf( wp.i18n.__( 'Distributed from %s. The original will update this unless you', 'distributor' ), dtGutenberg.originalLocationName );

		actions.push( {
			label: wp.i18n. __( 'unlink from original. ', 'distributor' ),
			url: dtGutenberg.unlinkNonceUrl
		} );

		actions.push( {
			label: wp.i18n.__( 'View Original', 'distributor' ),
			url: dtGutenberg.postUrl,
		} );


	} else {
		message = wp.i18n.sprintf( wp.i18n.__( 'Originally distributed from %1$s. This %2$s has been unlinked from the original. However, you can always', 'distributor' ), dtGutenberg.originalLocationName, dtGutenberg.postTypeSingular );

		actions.push( {
			label: wp.i18n. __( 'restore it.', 'distributor' ),
			url: dtGutenberg.linkNonceUrl
		} );
	}

	wp.data.dispatch( 'core/notices' ).createWarningNotice( message, {
		id: 'distributor-notice',
		actions,
	} );
}
