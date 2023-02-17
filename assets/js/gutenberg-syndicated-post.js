import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

const { dtGutenberg } = window;

if (
	'0' !== dtGutenberg.originalSourceId ||
	'0' !== dtGutenberg.originalBlogId
) {
	let message = '';
	const actions = [];

	if ( parseInt( dtGutenberg.originalDelete ) ) {
		message = sprintf(
			/* translators: 1) Distributor post type singular name, 2) Source of content. */
			__(
				'This %1$s was distributed from %2$s. However, the origin %1$s has been deleted.'
			),
			dtGutenberg.postTypeSingular,
			dtGutenberg.originalLocationName
		);
	} else if ( ! parseInt( dtGutenberg.unlinked ) ) {
		message = sprintf(
			/* translators: 1) Source of content, 2) Distributor post type singular name. */
			__(
				'Distributed from %1$s. This %2$s is linked to the origin %2$s. Edits to the origin %2$s will update this remote version.',
				'distributor'
			),
			dtGutenberg.originalLocationName,
			dtGutenberg.postTypeSingular
		);

		actions.push( {
			label: sprintf(
				/* translators: 1) Distributor post type singular name. */
				__( 'Unlink from the origin %1$s.', 'distributor' ),
				dtGutenberg.postTypeSingular
			),
			url: dtGutenberg.unlinkNonceUrl,
		} );

		actions.push( {
			label: sprintf(
				/* translators: 1) Distributor post type singular name. */
				__( 'View the origin %1$s', 'distributor' ),
				dtGutenberg.postTypeSingular
			),
			url: dtGutenberg.postUrl,
		} );
	} else {
		message = sprintf(
			/* translators: 1) Source of content, 2) Distributor post type singular name. */
			__(
				'Originally distributed from %1$s. This %2$s has been unlinked from the origin %2$s. Edits to the origin %2$s will not update this remote version.',
				'distributor'
			),
			dtGutenberg.originalLocationName,
			dtGutenberg.postTypeSingular
		);

		actions.push( {
			label: sprintf(
				/* translators: 1) Distributor post type singular name. */
				__( 'Relink to the origin %1$s.', 'distributor' ),
				dtGutenberg.postTypeSingular
			),
			url: dtGutenberg.linkNonceUrl,
		} );

		actions.push( {
			label: sprintf(
				/* translators: 1) Distributor post type singular name. */
				__( 'View the origin %1$s', 'distributor' ),
				dtGutenberg.postTypeSingular
			),
			url: dtGutenberg.postUrl,
		} );
	}

	dispatch( 'core/notices' ).createWarningNotice( message, {
		id: 'distributor-notice',
		actions,
	} );
}
