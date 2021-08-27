import jQuery from 'jquery';
import _ from 'underscores';
import { dt } from 'window';
import Mustache from 'mustache';

let selectedConnections = {},
	searchString        = '';
const processTemplate = _.memoize( ( id ) => {
	const element = document.getElementById( id );
	if ( ! element ) {
		return false;
	}

	if ( element.attributes.template ) {
		Mustache.parse( element.innerHTML );
		return 'mustache';
	} else {
		// Use WordPress style Backbone template syntax
		const options = {
			evaluate:    /<#([\s\S]+?)#>/g,
			interpolate: /{{{([\s\S]+?)}}}/g,
			escape:      /{{([^}]+?)}}(?!})/g
		};

		return _.template( element.innerHTML, null, options );
	}
} );

jQuery( window ).on( 'load', () => {
	const distributorAdminItem    = document.querySelector( '#wp-admin-bar-distributor > a' );
	const distributorTopMenu      = document.querySelector( '#wp-admin-bar-distributor' );
	const distributorMenuItem     = document.querySelector( '#wp-admin-bar-distributor #wp-admin-bar-distributor-placeholder' );
	const distributorPushWrapper  = document.querySelector( '#distributor-push-wrapper' );

	if ( ! distributorMenuItem || ! distributorPushWrapper ) {
		return;
	}

	let dtConnections           		= '';
	let connectionsSelected     		= '';
	let connectionsSelectedList 		= '';
	let connectionsNewList      		= '';
	let connectionsNewListChildren    	= '';
	let connectionsAvailableTotal		= '';
	let selectAllConnections 			= '';
	let selectNoConnections				= '';
	let connectionsSearchInput  		= '';
	let postStatusInput         		= '';
	let asDraftInput            		= '';
	let errorDetails            		= '';

	distributorMenuItem.appendChild( distributorPushWrapper );

	// Add our overlay div
	const overlayDiv = document.createElement( 'div' );
	overlayDiv.id = 'distributor-overlay';
	const contentNode = document.getElementById( 'wpadminbar' );
	contentNode.parentNode.insertBefore( overlayDiv, contentNode );

	/**
	 * Set variables after connections have been rendered
	 */
	function setVariables() {
		connectionsSelected     	= distributorPushWrapper.querySelector( '.connections-selected' );
		connectionsSelectedList 	= distributorPushWrapper.querySelector( '.selected-connections-list' );
		connectionsNewList      	= distributorPushWrapper.querySelector( '.new-connections-list' );
		selectAllConnections 		= distributorPushWrapper.querySelector( '.selectall-connections' );
		selectNoConnections 		= distributorPushWrapper.querySelector( '.selectno-connections' );
		connectionsSearchInput  	= document.getElementById( 'dt-connection-search' );
		postStatusInput         	= document.getElementById( 'dt-post-status' );
		asDraftInput            	= document.getElementById( 'dt-as-draft' );
		errorDetails                = document.querySelector( '.dt-error ul.details' );

		if ( null !== connectionsNewList ){
			connectionsNewListChildren  = connectionsNewList.querySelectorAll( '.add-connection' );
		}

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

		/**
		 * Disable select all button if all connections are syndicated and set variable for total connections available
		 */
		_.each( connectionsNewListChildren, ( element ) => {
			if ( ! element.classList.contains ( 'syndicated' ) ) {
				selectAllConnections.classList.remove( 'unavailable' );
				connectionsAvailableTotal ++;
			}
		} );

	}

	/**
	 * Set the disabled attribute on connections already syndicated
	 *
	 * This is currently only used for the mustache templates, which
	 * are only used in AMP contexts. Seems the AMP plugin will
	 * strip out any normal disabled attributes, so we handle that here
	 * instead of in the template.
	 */
	function setDisabledConnections() {
		connectionsNewList = distributorPushWrapper.querySelector( '.new-connections-list' );

		if ( null !== connectionsNewList ) {
			connectionsNewListChildren = connectionsNewList.querySelectorAll( '.add-connection' );

			_.each( connectionsNewListChildren, ( element ) => {
				if ( element.classList.contains ( 'syndicated' ) ) {
					element.disabled = true;
				}
			} );
		}
	}

	/**
	 * Handle UI error changes
	 */
	function doError( messages ) {
		distributorPushWrapper.classList.add( 'message-error' );
		errorDetails.innerText = '';

		_.each( prepareMessages( messages ), function( message ) {
			const errorItem = document.createElement( 'li' );
			errorItem.innerText = message;
			errorDetails.appendChild( errorItem );
		} );
	}

	/**
	 * Prepare error messages for printing.
	 *
	 * @param {string|array} messages Error messages.
	 */
	function prepareMessages( messages ) {
		if ( ! _.isArray( messages ) ) {
			return [ messages ];
		}

		return _.map( messages, function( message ) {
			if ( _.isString( message ) ) {
				return message;
			}
			if ( _.has( message, 'message' ) ) {
				return message.message;
			}
		} );
	}

	/**
	 * Handle UI success changes
	 */
	function doSuccess( results ) {
		let success = false;
		const errors = {};

		[ 'internal', 'external' ].map( type => {
			_.each( results[type], ( result, connectionId ) => {
				if ( 'success' === result.status ) {
					dtConnections[ `${type}${ connectionId }` ].syndicated = result.url;
					success = true;
				}

				if ( ! _.isEmpty( result.errors ) ) {
					errors[ `${type}${ connectionId }` ] = result.errors;
				}
			} );
		} );

		if ( ! _.isEmpty( errors ) ) {
			const formattedErrors = _.map( errors, function( messages, connectionId ) {
				return `${dtConnections[ connectionId ].name  }:\n${
					_.map( messages, function( message ) {
						return `- ${message}\n`;
					} )}`;
			} );

			doError( formattedErrors );
		}

		if ( success && _.isEmpty( errors ) ) {
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
		const template = processTemplate( 'dt-add-connection' );
		let showConnection = '';

		_.each( dtConnections, ( connection ) => {
			if ( '' !== searchString ) {
				const nameMatch = connection.name.replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() );
				const urlMatch  = connection.url.replace( /https?:\/\//i, '' ).replace( /www/i, '' ).replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() );

				if ( ! nameMatch && ! urlMatch ) {
					return;
				}
			}

			if ( 'mustache' === template ) {
				// Modify connection data to match what mustache wants
				if ( selectedConnections[connection.type + connection.id] ) {
					connection.added = true;
				} else {
					connection.added = false;
				}

				if ( 'internal' === connection.type ) {
					connection.internal = true;
				}

				showConnection = Mustache.render( document.getElementById( 'dt-add-connection' ).innerHTML, {
					connection: connection,
				} );
			} else {
				showConnection = template( {
					connection: connection,
					selectedConnections: selectedConnections
				} );
			}

			connectionsNewList.innerHTML += showConnection;
		} );

		if ( '' === connectionsNewList.innerHTML ) {
			connectionsNewList.innerHTML = '<p class="no-results">No results</p>';
		}

		if ( 'mustache' === template ) {
			setDisabledConnections();
		}
	}

	/**
	 * Add or remove CSS classes to indicate button functionality
	*/
	function classList( expr ) {
		switch ( expr ) {
				case 'addEmpty':
					connectionsSelected.classList.add( 'empty' );
					break;
				case 'removeEmpty':
					connectionsSelected.classList.remove( 'empty' );
					break;
				case 'allUnavailable':
					selectAllConnections.classList.add ( 'unavailable' );
					break;
				case 'all':
					selectAllConnections.classList.remove ( 'unavailable' );
					break;
				case 'noneUnavailable':
					selectNoConnections.classList.add ( 'unavailable' );
					break;
				case 'none':
					selectNoConnections.classList.remove ( 'unavailable' );
					break;
		}
	}

	/**
	 * If the menu isn't showing yet, wait to see if it does.
	 *
	 * This is an attempt to deal with the delay on the
	 * hoverintent function core uses on the adminbar.
	 */
	function waitForDistributorMenuToShow() {
		if ( distributorTopMenu.classList.contains( 'hover' ) ) {
			distributorMenuEntered();
		} else {
			setTimeout( () => {
				if ( distributorTopMenu.classList.contains( 'hover' ) ) {
					distributorMenuEntered();
				}
			}, 210 );
		}
	}

	/**
	 * Handle distributor push dropdown menu.
	 */
	function distributorMenuEntered() {
		distributorMenuItem.focus();

		// Show or hide the overlay
		if (
			overlayDiv.classList.contains( 'show' ) &&
			! overlayDiv.classList.contains( 'syncing' ) &&
			! distributorTopMenu.classList.contains( 'hover' )
		) {
			overlayDiv.classList.remove( 'show' );
			document.body.classList.remove( 'is-showing-distributor' );
		} else {
			overlayDiv.classList.add( 'show' );
		}

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

		const template = processTemplate( 'dt-show-connections' );
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

			if ( 'mustache' === template ) {
				// Manipulate the data to match what mustache needs
				const mustacheData = { 'connections' : [] };
				_.each( dtConnections, ( connection ) => {
					if ( 'internal' === connection.type ) {
						connection.internal = true;
					}

					mustacheData.connections.push( connection );
				} );

				distributorPushWrapper.innerHTML = Mustache.render( document.getElementById( 'dt-show-connections' ).innerHTML, {
					connections: mustacheData.connections,
					foundConnections: mustacheData.connections.length,
					showSearch: 5 < mustacheData.connections.length,
				} );

				setDisabledConnections();
			} else {
				distributorPushWrapper.innerHTML = template( {
					connections: dtConnections,
				} );
			}

			setVariables();
		} ).error( () => {
			distributorPushWrapper.classList.remove( 'loaded' );
			distributorPushWrapper.classList.add( 'message-error' );
		} );
	}

	/**
	 * Close distributor menu when a click occurs outside of it
	 */
	function maybeCloseDistributorMenu() {
		// If a distribution is in progress, don't close things
		if ( overlayDiv.classList.contains( 'syncing' ) ) {
			return;
		}

		// If the Distributor menu is showing, hide everything
		if ( distributorTopMenu.classList.contains( 'hover' ) ) {
			overlayDiv.classList.remove( 'show' );
			distributorTopMenu.classList.remove( 'hover' );
			document.body.classList.remove( 'is-showing-distributor' );
		}

		// If the Distributor menu isn't showing but the overlay is, remove the overlay
		if (
			! distributorTopMenu.classList.contains( 'hover' ) &&
			overlayDiv.classList.contains( 'show' )
		) {
			overlayDiv.classList.remove( 'show' );
			document.body.classList.remove( 'is-showing-distributor' );
		}
	}

	// Event listeners when to fetch distributor data.
	distributorAdminItem.addEventListener( 'keydown', function( e ) {
		// Pressing Enter.
		if ( ( 13 === e.keyCode ) ) {
			distributorMenuEntered();
		}

		// Pressing Escape.
		if ( 27 === e.keyCode ) {
			overlayDiv.classList.remove( 'show' );
		}
	}, false );

	// Listen for hover events to remove overlay div
	window.hoverintent(
		distributorTopMenu,
		hoverIn,
		hoverOut
	).options( {
		timeout: 190
	} );

	/**
	 * Distributor menu hovered on
	 *
	 * Not currently using as this is handled in the
	 * distributorMenuEntered function.
	 */
	function hoverIn() {
		return null;
	}

	/**
	 * Distributor menu hovered out
	 *
	 * Used to remove the overlay.
	 */
	function hoverOut() {
		if (
			! distributorTopMenu.classList.contains( 'hover' ) &&
			! overlayDiv.classList.contains( 'syncing' )
		) {
			overlayDiv.classList.remove( 'show' );
			document.body.classList.remove( 'is-showing-distributor' );
		}
	}

	distributorAdminItem.addEventListener( 'touchstart', waitForDistributorMenuToShow, false );
	distributorAdminItem.addEventListener( 'mouseenter', waitForDistributorMenuToShow, false );
	overlayDiv.addEventListener( 'click', maybeCloseDistributorMenu, true );

	/**
	 * Do syndication ajax
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.syndicate-button', () => {
		if ( distributorTopMenu.classList.contains( 'syncing' ) ) {
			return;
		}

		distributorTopMenu.classList.add( 'syncing' );
		overlayDiv.classList.add( 'syncing' );

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
				distributorTopMenu.classList.remove( 'syncing' );
				overlayDiv.classList.remove( 'syncing' );

				// Hide the overlay if a user moved out of the Distributor menu
				hoverOut();

				if ( ! response.success ) {
					doError( response.data );
					return;
				}

				if ( ! response.data || ! response.data.results ) {
					doError( dt.messages.empty_result );
					return;
				}

				doSuccess( response.data.results );
			}, 500 );
		} ).error( ( xhr, textStatus, errorThrown ) => {
			setTimeout( () => {
				distributorTopMenu.classList.remove( 'syncing' );
				overlayDiv.classList.remove( 'syncing' );

				doError( `${dt.messages.ajax_error} ${errorThrown}` );
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

			if ( selectAllConnections.classList.contains ( 'unavailable' ) ) {
				classList ( 'all' );
			}
			if ( ! Object.keys( selectedConnections ).length ) {
				classList ( 'addEmpty' );
				classList ( 'noneUnavailable' );
			}

			showConnections();
		} else {
			const type = event.currentTarget.getAttribute( 'data-connection-type' );
			const id   = event.currentTarget.getAttribute( 'data-connection-id' );

			selectedConnections[type + id] = dtConnections[type + id];

			const element = event.currentTarget.cloneNode( true );

			const removeLink = document.createElement( 'span' );
			removeLink.classList.add( 'remove-connection' );

			element.appendChild( removeLink );
			element.classList = 'added-connection';
			connectionsSelectedList.appendChild( element );

			if ( selectNoConnections.classList.contains ( 'unavailable' ) ) {
				classList ( 'removeEmpty' );
				classList ( 'none' );
			}

			if ( Object.keys( selectedConnections ).length == connectionsAvailableTotal ){
				classList ( 'allUnavailable' );
			}

			showConnections();
		}
	} );

	/**
	 * Select all connections for distribution.
	*/
	jQuery( distributorPushWrapper ).on( 'click', '.selectall-connections', () => {
		jQuery ( connectionsNewList ).children( '.add-connection' ).each( ( index, childTarget ) => {
			if ( childTarget.classList.contains( 'syndicated' ) || childTarget.classList.contains( 'added' ) ) {
				return;
			} else {
				const type = childTarget.getAttribute( 'data-connection-type' );
				const id   = childTarget.getAttribute( 'data-connection-id' );

				selectedConnections[type + id] = dtConnections[type + id];

				const element     = childTarget.cloneNode();
				element.innerText = childTarget.innerText;

				const removeLink = document.createElement( 'span' );
				removeLink.classList.add( 'remove-connection' );

				element.appendChild( removeLink );
				element.classList = 'added-connection';

				connectionsSelectedList.appendChild( element );

			}

			if ( '' !== connectionsAvailableTotal ) {
				classList ( 'removeEmpty' );
				classList ( 'allUnavailable' );
				classList ( 'none' );
			}

		} );

		showConnections();
	} );

	/**
	 * Select no connections for distribution.
	*/
	jQuery( distributorPushWrapper ).on( 'click', '.selectno-connections', () => {

		while ( connectionsSelectedList.firstChild ) {
			const type = connectionsSelectedList.firstChild.getAttribute( 'data-connection-type' );
			const id   = connectionsSelectedList.firstChild.getAttribute( 'data-connection-id' );

			delete selectedConnections[type + id];

			connectionsSelectedList.removeChild( connectionsSelectedList.firstChild );

		}

		if ( '' !== connectionsAvailableTotal ) {
			classList ( 'addEmpty' );
			classList ( 'noneUnavailable' );
			classList ( 'all' );
		}

		showConnections();
	} );

	/**
	 * Remove a connection from selected connections and the UI list
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.added-connection', ( event ) => {
		event.currentTarget.parentNode.removeChild( event.currentTarget );
		const type = event.currentTarget.getAttribute( 'data-connection-type' );
		const id   = event.currentTarget.getAttribute( 'data-connection-id' );

		delete selectedConnections[type + id];

		if ( selectAllConnections.classList.contains ( 'unavailable' ) ) {
			classList ( 'all' );
		}
		if ( ! Object.keys( selectedConnections ).length ) {
			classList ( 'addEmpty' );
			classList ( 'noneUnavailable' );
		}

		showConnections();
	} );
} );
