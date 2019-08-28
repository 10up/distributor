import jQuery from 'jquery';
import _ from 'underscores';
import { dt } from 'window';

let selectedConnections = {},
	searchString        = '';

const processTemplate = _.memoize( ( id ) => {
	const element = document.getElementById( id );
	if ( ! element ) {
		return false;
	}

	// Use WordPress style Backbone template syntax
	const options = {
		evaluate:    /<#([\s\S]+?)#>/g,
		interpolate: /{{{([\s\S]+?)}}}/g,
		escape:      /{{([^}]+?)}}(?!})/g
	};

	return _.template( element.innerHTML, null, options );
} );

jQuery( window ).on( 'load', () => {
	const distributorMenuItem     = document.querySelector( '#wp-admin-bar-distributor' );
	const distributorPushWrapper  = document.querySelector( '#distributor-push-wrapper' );

	if ( ! distributorMenuItem || ! distributorPushWrapper ) {
		return;
	}

	let dtConnections           = '';
	let connectionsSelected     = '';
	let connectionsSelectedList = '';
	let connectionsNewList      = '';
	let connectionsSearchInput  = '';
	let actionWrapper           = '';
	let postStatusInput         = '';
	let asDraftInput            = '';

	distributorMenuItem.appendChild( distributorPushWrapper );

	/**
	 * Set variables after connections have been rendered
	 */
	function setVariables() {
		connectionsSelected     = distributorPushWrapper.querySelector( '.connections-selected' );
		connectionsSelectedList = distributorPushWrapper.querySelector( '.selected-connections-list' );
		connectionsNewList      = distributorPushWrapper.querySelector( '.new-connections-list' );
		connectionsSearchInput  = document.getElementById( 'dt-connection-search' );
		actionWrapper           = distributorPushWrapper.querySelector( '.action-wrapper' );
		postStatusInput         = document.getElementById( 'dt-post-status' );
		asDraftInput            = document.getElementById( 'dt-as-draft' );

		/**
		 * Listen for connection filtering
		 */
		jQuery( connectionsSearchInput ).on( 'keyup change', _.debounce( ( event ) => {
			if ( '' === event.currentTarget.value ) {
				showConnections( dtConnections );
			}

			searchString = event.currentTarget.value.replace( /https?:\/\//i, '' ).replace( /www/i, '' ).replace( /[^0-9a-zA-Z ]+/, '' );

			showConnections();
		}, 300 ) );
	}

	/**
	 * Handle UI error changes
	 */
	function doError() {
		distributorPushWrapper.classList.add( 'message-error' );

		setTimeout( () => {
			distributorPushWrapper.classList.remove( 'message-error' );
		}, 6000 );
	}

	/**
	 * Handle UI success changes
	 */
	function doSuccess( results ) {
		let error = false;

		_.each( results.internal, ( result, connectionId ) => {
			if ( 'fail' === result.status ) {
				error = true;
			} else {
				dtConnections[ `internal${ connectionId}` ].syndicated = result.url;
			}
		} );

		_.each( results.external, ( result, connectionId ) => {
			if ( 'fail' === result.status ) {
				error = true;
			} else {
				dtConnections[ `external${ connectionId }` ].syndicated = true;
			}
		} );

		if ( error ) {
			doError();
		} else {
			distributorPushWrapper.classList.add( 'message-success' );

			connectionsSelected.classList.add( 'empty' );
			connectionsSelectedList.innerText = '';

			setTimeout( () => {
				distributorPushWrapper.classList.remove( 'message-success' );
			}, 6000 );
		}

		selectedConnections = {};

		showConnections();
	}

	/**
		 * Show connections. If there is a search string, then filter by it
		 */
	function showConnections() {
		connectionsNewList.innerText = '';

		_.each( dtConnections, ( connection ) => {
			if ( '' !== searchString ) {
				const nameMatch = connection.name.replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() );
				const urlMatch  = connection.url.replace( /https?:\/\//i, '' ).replace( /www/i, '' ).replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() );

				if ( ! nameMatch && ! urlMatch ) {
					return;
				}
			}

			const showConnection = processTemplate( 'dt-add-connection' )( {
				connection: connection,
				selectedConnections: selectedConnections
			} );

			connectionsNewList.innerHTML += showConnection;
		} );
	}

	/**
	 * Handle distributor push dropdown menu hover using hoverIntent.
	 */
	function distributorMenuEntered() {
		distributorMenuItem.focus();
		document.body.classList.toggle( 'distributor-show' );

		if ( distributorPushWrapper.classList.contains( 'loaded' ) ) {
			return;
		}

		distributorPushWrapper.classList.remove( 'message-error' );
		distributorPushWrapper.classList.add( 'loaded' );

		const data = {
			action: 'dt_load_connections',
			loadConnectionsNonce: dt.loadConnectionsNonce,
			postId: dt.postId
		};

		const xhr = dt.usexhr ? { withCredentials: true } : false;

		jQuery.ajax( {
			url: dt.ajaxurl,
			xhrFields: xhr,
			method: 'post',
			data: data
		} ).done( ( response ) => {
			if ( ! response.success || ! response.data ) {
				distributorPushWrapper.classList.remove( 'loaded' );
				distributorPushWrapper.classList.add( 'message-error' );
				return;
			}

			dtConnections = response.data;

			// Allowing innerHTML because processTemplate escapes values
			distributorPushWrapper.innerHTML = processTemplate( 'dt-show-connections' )( {
				connections: dtConnections,
			} );

			setVariables();
		} ).error( () => {
			distributorPushWrapper.classList.remove( 'loaded' );
			distributorPushWrapper.classList.add( 'message-error' );
		} );
	}

	/**
	 * Handle exiting the distributor menu.
	 */
	function distributorMenuExited() {
		distributorMenuItem.blur();
		document.body.classList.toggle( 'distributor-show' );
	}

	jQuery( distributorMenuItem ).hoverIntent( distributorMenuEntered, 300, distributorMenuExited );

	/**
	 * Do syndication ajax
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.syndicate-button', () => {
		if ( actionWrapper.classList.contains( 'loading' ) ) {
			return;
		}

		actionWrapper.classList.add( 'loading' );

		const data = {
			action: 'dt_push',
			nonce: dt.nonce,
			connections: selectedConnections,
			postId: dt.postId
		};

		data.postStatus = ( null !== asDraftInput && asDraftInput.checked ) ? 'draft' : postStatusInput.value;

		const xhr = dt.usexhr ? { withCredentials: true } : false;

		jQuery.ajax( {
			url: dt.ajaxurl,
			xhrFields: xhr,
			method: 'post',
			data: data
		} ).done( ( response ) => {
			setTimeout( () => {
				actionWrapper.classList.remove( 'loading' );

				if ( ! response.data || ! response.data.results ) {
					doError();
					return;
				}

				doSuccess( response.data.results );
			}, 500 );
		} ).error( () => {
			setTimeout( () => {
				actionWrapper.classList.remove( 'loading' );

				doError();
			}, 500 );
		} );
	} );

	/**
	 * Add a connection to selected connections for ajax and to the UI list.
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.add-connection', ( event ) => {
		if ( 'A' === event.target.nodeName ) {
			return;
		}

		event.preventDefault();

		if ( event.currentTarget.classList.contains( 'syndicated' ) ) {
			return;
		}

		if ( event.currentTarget.classList.contains( 'added' ) ) {

			const type = event.currentTarget.getAttribute( 'data-connection-type' );
			const id   = event.currentTarget.getAttribute( 'data-connection-id' );

			const deleteNode = connectionsSelectedList.querySelector( `[data-connection-id="${ id }"][data-connection-type="${ type }"]` );

			deleteNode.parentNode.removeChild( deleteNode );

			delete selectedConnections[type + id];

			if ( ! Object.keys( selectedConnections ).length ) {
				connectionsSelected.classList.add( 'empty' );
			}

			showConnections();
		} else {

			const type = event.currentTarget.getAttribute( 'data-connection-type' );
			const id   = event.currentTarget.getAttribute( 'data-connection-id' );

			selectedConnections[type + id] = dtConnections[type + id];

			connectionsSelected.classList.remove( 'empty' );

			const element       = event.currentTarget.cloneNode();
			element.innerText = event.currentTarget.innerText;

			const removeLink = document.createElement( 'span' );
			removeLink.classList.add( 'remove-connection' );

			element.appendChild( removeLink );
			element.classList = 'added-connection';

			connectionsSelectedList.appendChild( element );

			showConnections();
		}
	} );

	/**
	 * Remove a connection from selected connections and the UI list
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.remove-connection', ( event ) => {
		event.currentTarget.parentNode.parentNode.removeChild( event.currentTarget.parentNode );
		const type = event.currentTarget.parentNode.getAttribute( 'data-connection-type' );
		const id   = event.currentTarget.parentNode.getAttribute( 'data-connection-id' );

		delete selectedConnections[type + id];

		if ( ! Object.keys( selectedConnections ).length ) {
			connectionsSelected.classList.add( 'empty' );
		}

		showConnections();
	} );
} );
