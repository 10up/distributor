import jQuery from 'jquery';

const chooseConnection = document.getElementById( 'pull_connections' );
const choosePostType = document.getElementById( 'pull_post_type' );
const choosePostTypeBtn = document.getElementById( 'pull_post_type_submit' );
const form = document.getElementById( 'posts-filter' );

jQuery( chooseConnection ).on( 'change', ( event ) => {

	document.location = event.currentTarget.options[event.currentTarget.selectedIndex].getAttribute( 'data-pull-url' );

	document.body.className += ' ' + 'dt-loading';
} );

if ( chooseConnection && choosePostType && choosePostTypeBtn && form ) {
	jQuery( choosePostTypeBtn ).on( 'click', ( event ) => {

		event.preventDefault();

		const postType = choosePostType.options[ choosePostType.selectedIndex ].value;
		const url = chooseConnection.options[ chooseConnection.selectedIndex ].getAttribute( 'data-pull-url' );
		let status = 'new';

		if ( -1 < ( ' ' + form.className + ' ' ).indexOf( ' status-skipped ' ) ) {
			status = 'skipped';
		} else if ( -1 < ( ' ' + form.className + ' ' ).indexOf( ' status-pulled ' ) ) {
			status = 'pulled';
		}

		document.location = url + '&pull_post_type=' + postType + '&status=' + status;

		document.body.className += ' ' + 'dt-loading';
	} );
}
