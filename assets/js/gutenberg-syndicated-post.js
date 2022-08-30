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
			/** translators: 1) Distributor post type singular name, 2) Source of content. */
			__(
				'This %1$s was distributed from %2$s. However, the original has been deleted.'
			),
			dtGutenberg.postTypeSingular,
			dtGutenberg.originalLocationName
		);
	} else if ( ! parseInt( dtGutenberg.unlinked ) ) {
		message = sprintf(
			/** translators: 1) Source of content, 2) Distributor post type singular name. */
			__(
				'Distributed from %1$s. This %2$s is linked to the original. Edits to the original will update this version.',
				'distributor'
			),
			dtGutenberg.originalLocationName,
			dtGutenberg.postTypeSingular
		);

		actions.push( {
			label: __( 'Unlink from original.', 'distributor' ),
			url: dtGutenberg.unlinkNonceUrl,
		} );

		actions.push( {
			label: __( 'View Original', 'distributor' ),
			url: dtGutenberg.postUrl,
		} );
	} else {
		message = sprintf(
			/** translators: 1) Source of content, 2) Distributor post type singular name. */
			__(
				'Originally distributed from %1$s. This %2$s has been unlinked from the original. Edits to the original will not update this version.',
				'distributor'
			),
			dtGutenberg.originalLocationName,
			dtGutenberg.postTypeSingular
		);

		actions.push( {
			label: __( 'Relink to original.', 'distributor' ),
			url: dtGutenberg.linkNonceUrl,
		} );

		actions.push( {
			label: __( 'View Original', 'distributor' ),
			url: dtGutenberg.postUrl,
		} );
	}

	dispatch( 'core/notices' ).createWarningNotice( message, {
		id: 'distributor-notice',
		actions,
	} );
}
