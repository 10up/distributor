import jQuery from 'jquery';

const chooseConnection = document.getElementById( 'pull_connections' );
const choosePostType = document.getElementById( 'pull_post_type' );

jQuery( chooseConnection ).on( 'change', ( event ) => {

	document.location = event.currentTarget.options[event.currentTarget.selectedIndex].getAttribute( 'data-pull-url' );

	document.body.className += ' ' + 'dt-loading';
} );

if ( chooseConnection && choosePostType ) {
	jQuery( choosePostType ).on( 'change', ( event ) => {

		const postType = event.currentTarget.options[ event.currentTarget.selectedIndex ].value;
		const url = chooseConnection.options[ chooseConnection.selectedIndex ].getAttribute( 'data-pull-url' );

		document.location = url + '&pull_post_type=' + postType;

		document.body.className += ' ' + 'dt-loading';
	} );
}
