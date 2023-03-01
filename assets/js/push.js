import '../css/push.scss';

import jQuery from 'jquery';
import _ from 'underscore';
import Mustache from 'mustache';
import { sprintf, _n } from '@wordpress/i18n';

const { document, dt } = window;
let selectedText = '';
let selectedConnections = {},
	searchString = '';
const processTemplate = _.memoize( ( id ) => {
	const element = document.getElementById( id );
	if ( ! element ) {
		return false;
	}

	if ( element.attributes.template ) {
		Mustache.parse( element.innerHTML );
		return 'mustache';
	}
	// Use WordPress style Backbone template syntax
	const options = {
		evaluate: /<#([\s\S]+?)#>/g,
		interpolate: /{{{([\s\S]+?)}}}/g,
		escape: /{{([^}]+?)}}(?!})/g,
	};

	return _.template( element.innerHTML, null, options );
} );

jQuery( window ).on( 'load', () => {
	const distributorMenuItem = document.querySelector(
		'#wp-admin-bar-distributor #wp-admin-bar-distributor-placeholder'
	);
	const distributorPushWrapper = document.querySelector(
		'#distributor-push-wrapper'
	);

	if ( ! distributorMenuItem || ! distributorPushWrapper ) {
		return;
	}

	let dtConnections = '';
	let connectionsSelected = '';
	let connectionsSelectedList = '';
	let connectionsNewList = '';
	let connectionsNewListChildren = '';
	let connectionsAvailableTotal = '';
	let selectAllConnections = '';
	let selectNoConnections = '';
	let connectionsSearchInput = '';
	let postStatusInput = '';
	let asDraftInput = '';
	let errorDetails = '';

	distributorMenuItem.appendChild( distributorPushWrapper );

	// Add our overlay div
	const overlayDiv = document.createElement( 'div' );
	overlayDiv.id = 'distributor-overlay';

	const distributorTopMenu = document.querySelector(
		'#wp-admin-bar-distributor'
	);

	distributorTopMenu.parentNode.insertBefore(
		overlayDiv,
		distributorTopMenu.nextSibling
	);

	/**
	 * Set variables after connections have been rendered
	 */
	function setVariables() {
		connectionsSelected = distributorPushWrapper.querySelector(
			'.connections-selected'
		);
		connectionsSelectedList = distributorPushWrapper.querySelector(
			'.selected-connections-list'
		);
		connectionsNewList = distributorPushWrapper.querySelector(
			'.new-connections-list'
		);
		selectAllConnections = distributorPushWrapper.querySelector(
			'.selectall-connections'
		);
		selectNoConnections = distributorPushWrapper.querySelector(
			'.selectno-connections'
		);
		connectionsSearchInput = document.getElementById(
			'dt-connection-search'
		);
		postStatusInput = document.getElementById( 'dt-post-status' );
		asDraftInput = document.getElementById( 'dt-as-draft' );
		errorDetails = document.querySelector( '.dt-error ul.details' );

		if ( null !== connectionsNewList ) {
			connectionsNewListChildren =
				connectionsNewList.querySelectorAll( '.add-connection' );
		}

		/**
		 * Listen for connection filtering
		 */
		jQuery( connectionsSearchInput ).on(
			'keyup change',
			_.debounce( ( event ) => {
				if ( '' === event.currentTarget.value ) {
					showConnections( dtConnections );
				}
				searchString = event.currentTarget.value
					.replace( /https?:\/\//i, '' )
					.replace( /www/i, '' )
					.replace( /[^0-9a-zA-Z ]+/, '' );
				showConnections();
			}, 300 )
		);

		/**
		 * Disable select all button if all connections are syndicated and set variable for total connections available
		 */
		_.each( connectionsNewListChildren, ( element ) => {
			if ( ! element.classList.contains( 'syndicated' ) ) {
				selectAllConnections.classList.remove( 'unavailable' );
				connectionsAvailableTotal++;
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
		connectionsNewList = distributorPushWrapper.querySelector(
			'.new-connections-list'
		);

		if ( null !== connectionsNewList ) {
			connectionsNewListChildren =
				connectionsNewList.querySelectorAll( '.add-connection' );

			_.each( connectionsNewListChildren, ( element ) => {
				if ( element.classList.contains( 'syndicated' ) ) {
					element.disabled = true;
				}
			} );
		}
	}

	/**
	 * Handle UI error changes
	 *
	 * @param {string[]} messages Array of error messages.
	 */
	function doError( messages ) {
		distributorPushWrapper.classList.add( 'message-error' );
		errorDetails.innerText = '';

		_.each( prepareMessages( messages ), function ( message ) {
			const errorItem = document.createElement( 'li' );
			errorItem.innerText = message;
			errorDetails.appendChild( errorItem );
		} );
	}

	/**
	 * Prepare error messages for printing.
	 *
	 * @param {string | Array} messages Error messages.
	 */
	function prepareMessages( messages ) {
		if ( ! _.isArray( messages ) ) {
			return [ messages ];
		}

		return _.map( messages, function ( message ) {
			if ( _.isString( message ) ) {
				return message;
			}
			if ( _.has( message, 'message' ) ) {
				return message.message;
			}
		} );
	}

	/**
	 * Handle UI success changes.
	 *
	 * @param {Object[]} results Array of results from distribution attempts.
	 */
	function doSuccess( results ) {
		let success = false;
		const errors = {};

		[ 'internal', 'external' ].forEach( ( type ) => {
			_.each( results[ type ], ( result, connectionId ) => {
				if ( 'success' === result.status ) {
					dtConnections[ `${ type }${ connectionId }` ].syndicated =
						result.url;
					success = true;
				}

				if ( ! _.isEmpty( result.errors ) ) {
					errors[ `${ type }${ connectionId }` ] = result.errors;
				}
			} );
		} );

		if ( ! _.isEmpty( errors ) ) {
			const formattedErrors = _.map(
				errors,
				function ( messages, connectionId ) {
					return `${ dtConnections[ connectionId ].name }:\n${ _.map(
						messages,
						function ( message ) {
							return `- ${ message }\n`;
						}
					) }`;
				}
			);

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
				const nameMatch = connection.name
					.replace( /[^0-9a-zA-Z ]+/, '' )
					.toLowerCase()
					.match( searchString.toLowerCase() );
				const urlMatch = connection.url
					.replace( /https?:\/\//i, '' )
					.replace( /www/i, '' )
					.replace( /[^0-9a-zA-Z ]+/, '' )
					.toLowerCase()
					.match( searchString.toLowerCase() );

				if ( ! nameMatch && ! urlMatch ) {
					return;
				}
			}

			if ( 'mustache' === template ) {
				// Modify connection data to match what mustache wants
				if ( selectedConnections[ connection.type + connection.id ] ) {
					connection.added = true;
				} else {
					connection.added = false;
				}

				if ( 'internal' === connection.type ) {
					connection.internal = true;
				}

				showConnection = Mustache.render(
					document.getElementById( 'dt-add-connection' ).innerHTML,
					{
						connection,
					}
				);
			} else {
				showConnection = template( {
					connection,
					selectedConnections,
				} );
			}

			connectionsNewList.innerHTML += showConnection;
		} );

		if ( '' === connectionsNewList.innerHTML ) {
			connectionsNewList.innerHTML =
				'<p class="no-results">No results</p>';
		}

		if ( 'mustache' === template ) {
			setDisabledConnections();
		}
	}

	/**
	 * Add or remove CSS classes to indicate button functionality.
	 *
	 * @param {string} expr Functionality to indicate.
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
				selectAllConnections.classList.add( 'unavailable' );
				break;
			case 'all':
				selectAllConnections.classList.remove( 'unavailable' );
				break;
			case 'noneUnavailable':
				selectNoConnections.classList.add( 'unavailable' );
				break;
			case 'none':
				selectNoConnections.classList.remove( 'unavailable' );
				break;
		}
	}

	/**
	 * Handle distributor push dropdown menu.
	 */
	function distributorMenuEntered() {
		distributorMenuItem.focus();

		// Determine if we need to hide the admin bar
		maybeHideAdminBar();

		if ( distributorPushWrapper.classList.contains( 'loaded' ) ) {
			return;
		}

		distributorPushWrapper.classList.remove( 'message-error' );
		distributorPushWrapper.classList.add( 'loaded' );

		const data = {
			action: 'dt_load_connections',
			loadConnectionsNonce: dt.loadConnectionsNonce,
			postId: dt.postId,
		};

		const template = processTemplate( 'dt-show-connections' );
		const xhr = dt.usexhr ? { withCredentials: true } : false;

		jQuery
			.ajax( {
				url: dt.ajaxurl,
				xhrFields: xhr,
				method: 'post',
				data,
			} )
			.done( ( response ) => {
				if ( ! response.success || ! response.data ) {
					distributorPushWrapper.classList.remove( 'loaded' );
					distributorPushWrapper.classList.add( 'message-error' );
					return;
				}

				dtConnections = response.data;

				if ( 'mustache' === template ) {
					// Manipulate the data to match what mustache needs
					const mustacheData = { connections: [] };
					_.each( dtConnections, ( connection ) => {
						if ( 'internal' === connection.type ) {
							connection.internal = true;
						}

						mustacheData.connections.push( connection );
					} );

					distributorPushWrapper.innerHTML = Mustache.render(
						document.getElementById( 'dt-show-connections' )
							.innerHTML,
						{
							connections: mustacheData.connections,
							foundConnections: mustacheData.connections.length,
							showSearch: 5 < mustacheData.connections.length,
						}
					);

					setDisabledConnections();
				} else {
					distributorPushWrapper.innerHTML = template( {
						connections: dtConnections,
					} );
				}

				setVariables();
			} )
			.error( () => {
				distributorPushWrapper.classList.remove( 'loaded' );
				distributorPushWrapper.classList.add( 'message-error' );
			} );
	}

	/**
	 * Close distributor menu when a click occurs outside of it
	 */
	function maybeCloseDistributorMenu() {
		// If a distribution is in progress, don't close things
		if ( distributorTopMenu.classList.contains( 'syncing' ) ) {
			return;
		}

		// If the Distributor menu is showing, hide everything
		if ( distributorTopMenu.classList.contains( 'hover' ) ) {
			distributorTopMenu.classList.remove( 'hover' );
			document.body.classList.remove( 'is-showing-distributor' );
		}

		// Determine if we need to hide the admin bar
		maybeHideAdminBar();
	}

	const distributorAdminItem = document.querySelector(
		'#wp-admin-bar-distributor > a'
	);

	// Event listeners when to fetch distributor data.
	distributorAdminItem.addEventListener(
		'keydown',
		function ( e ) {
			// Pressing Enter.
			if ( 13 === e.keyCode ) {
				distributorMenuEntered();
			}
		},
		false
	);

	// In full screen mode, add hoverintent to remove admin bar on hover out
	if ( document.body.classList.contains( 'is-fullscreen-mode' ) ) {
		window
			.hoverintent(
				distributorTopMenu,
				function () {
					return null;
				},
				maybeHideAdminBar
			)
			.options( {
				timeout: 180,
			} );
	}

	/**
	 * Distributor menu hovered out
	 *
	 * Used to remove the admin bar from showing.
	 */
	function maybeHideAdminBar() {
		if (
			! distributorTopMenu.classList.contains( 'hover' ) &&
			! distributorTopMenu.classList.contains( 'syncing' )
		) {
			document.body.classList.remove( 'is-showing-distributor' );
		}
	}

	distributorAdminItem.addEventListener(
		'touchstart',
		distributorMenuEntered,
		false
	);
	distributorAdminItem.addEventListener(
		'mouseenter',
		distributorMenuEntered,
		false
	);
	overlayDiv.addEventListener( 'click', maybeCloseDistributorMenu, true );

	/**
	 * Do syndication ajax
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.syndicate-button', () => {
		if ( distributorTopMenu.classList.contains( 'syncing' ) ) {
			return;
		}

		distributorTopMenu.classList.add( 'syncing' );

		const data = {
			action: 'dt_push',
			nonce: dt.nonce,
			connections: selectedConnections,
			postId: dt.postId,
		};

		data.postStatus =
			null !== asDraftInput && asDraftInput.checked
				? 'draft'
				: postStatusInput.value;

		const xhr = dt.usexhr ? { withCredentials: true } : false;

		jQuery
			.ajax( {
				url: dt.ajaxurl,
				xhrFields: xhr,
				method: 'post',
				data,
			} )
			.done( ( response ) => {
				setTimeout( () => {
					distributorTopMenu.classList.remove( 'syncing' );

					// Maybe hide the admin bar
					maybeHideAdminBar();

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
			} )
			// eslint-disable-next-line no-shadow
			.error( ( xhr, textStatus, errorThrown ) => {
				setTimeout( () => {
					distributorTopMenu.classList.remove( 'syncing' );

					doError( `${ dt.messages.ajax_error } ${ errorThrown }` );
				}, 500 );
			} );
	} );

	/**
	 * Add a connection to selected connections for ajax and to the UI list.
	 */
	jQuery( distributorPushWrapper ).on(
		'click',
		'.add-connection',
		( event ) => {
			if ( 'A' === event.target.nodeName ) {
				return;
			}

			event.preventDefault();

			if ( event.currentTarget.classList.contains( 'syndicated' ) ) {
				return;
			}

			if ( event.currentTarget.classList.contains( 'added' ) ) {
				const type = event.currentTarget.getAttribute(
					'data-connection-type'
				);
				const id =
					event.currentTarget.getAttribute( 'data-connection-id' );

				const deleteNode = connectionsSelectedList.querySelector(
					`[data-connection-id="${ id }"][data-connection-type="${ type }"]`
				);

				deleteNode.parentNode.removeChild( deleteNode );

				delete selectedConnections[ type + id ];
				selectedText = sprintf(
					/* translators: 1) Selected connection content singular name. 2) Selected connection content plural name. */
					_n(
						'Selected connection (%d)',
						'Selected connections (%d)',
						Object.keys( selectedConnections ).length,
						'distributor'
					),
					Object.keys( selectedConnections ).length
				);
				document.querySelector(
					'.selected-connections-text'
				).textContent = selectedText;
				if (
					selectAllConnections.classList.contains( 'unavailable' )
				) {
					classList( 'all' );
				}
				if ( ! Object.keys( selectedConnections ).length ) {
					classList( 'addEmpty' );
					classList( 'noneUnavailable' );
				}

				showConnections();
			} else {
				const type = event.currentTarget.getAttribute(
					'data-connection-type'
				);
				const id =
					event.currentTarget.getAttribute( 'data-connection-id' );

				selectedConnections[ type + id ] = dtConnections[ type + id ];

				const element = event.currentTarget.cloneNode( true );
				selectedText = sprintf(
					/* translators: 1) Selected connection content singular name. 2) Selected connection content plural name. */
					_n(
						'Selected connection (%d)',
						'Selected connections (%d)',
						Object.keys( selectedConnections ).length,
						'distributor'
					),
					Object.keys( selectedConnections ).length
				);
				document.querySelector(
					'.selected-connections-text'
				).textContent = selectedText;

				const removeLink = document.createElement( 'span' );
				removeLink.classList.add( 'remove-connection' );

				element.appendChild( removeLink );
				element.classList = 'added-connection';
				connectionsSelectedList.appendChild( element );

				if ( selectNoConnections.classList.contains( 'unavailable' ) ) {
					classList( 'removeEmpty' );
					classList( 'none' );
				}

				if (
					// eslint-disable-next-line eqeqeq
					Object.keys( selectedConnections ).length ==
					connectionsAvailableTotal
				) {
					classList( 'allUnavailable' );
				}

				showConnections();
			}
		}
	);

	/**
	 * Select all connections for distribution.
	 */
	jQuery( distributorPushWrapper ).on(
		'click',
		'.selectall-connections',
		() => {
			jQuery( connectionsNewList )
				.children( '.add-connection' )
				.each( ( index, childTarget ) => {
					if (
						childTarget.classList.contains( 'syndicated' ) ||
						childTarget.classList.contains( 'added' )
					) {
						return;
					}
					const type = childTarget.getAttribute(
						'data-connection-type'
					);
					const id = childTarget.getAttribute( 'data-connection-id' );

					selectedConnections[ type + id ] =
						dtConnections[ type + id ];

					const element = childTarget.cloneNode();
					element.innerText = childTarget.innerText;

					const removeLink = document.createElement( 'span' );
					removeLink.classList.add( 'remove-connection' );

					element.appendChild( removeLink );
					element.classList = 'added-connection';
					selectedText = sprintf(
						/* translators: 1) Selected connection content singular name. 2) Selected connection content plural name. */
						_n(
							'Selected connection (%d)',
							'Selected connections (%d)',
							Object.keys( selectedConnections ).length,
							'distributor'
						),
						Object.keys( selectedConnections ).length
					);
					document.querySelector(
						'.selected-connections-text'
					).textContent = selectedText;

					connectionsSelectedList.appendChild( element );

					if ( '' !== connectionsAvailableTotal ) {
						classList( 'removeEmpty' );
						classList( 'allUnavailable' );
						classList( 'none' );
					}
				} );

			showConnections();
		}
	);

	/**
	 * Select no connections for distribution.
	 */
	jQuery( distributorPushWrapper ).on(
		'click',
		'.selectno-connections',
		() => {
			while ( connectionsSelectedList.firstChild ) {
				const type = connectionsSelectedList.firstChild.getAttribute(
					'data-connection-type'
				);
				const id =
					connectionsSelectedList.firstChild.getAttribute(
						'data-connection-id'
					);

				delete selectedConnections[ type + id ];
				selectedText = sprintf(
					/* translators: 1) Selected connection content singular name. 2) Selected connection content plural name. */
					_n(
						'Selected connection (%d)',
						'Selected connections (%d)',
						Object.keys( selectedConnections ).length,
						'distributor'
					),
					Object.keys( selectedConnections ).length
				);
				document.querySelector(
					'.selected-connections-text'
				).textContent = selectedText;

				connectionsSelectedList.removeChild(
					connectionsSelectedList.firstChild
				);
			}

			if ( '' !== connectionsAvailableTotal ) {
				classList( 'addEmpty' );
				classList( 'noneUnavailable' );
				classList( 'all' );
			}

			showConnections();
		}
	);

	/**
	 * Remove a connection from selected connections and the UI list
	 */
	jQuery( distributorPushWrapper ).on(
		'click',
		'.added-connection',
		( event ) => {
			event.currentTarget.parentNode.removeChild( event.currentTarget );
			const type = event.currentTarget.getAttribute(
				'data-connection-type'
			);
			const id = event.currentTarget.getAttribute( 'data-connection-id' );

			delete selectedConnections[ type + id ];
			selectedText = sprintf(
				/* translators: 1) Selected connection content singular name. 2) Selected connection content plural name. */
				_n(
					'Selected connection (%d)',
					'Selected connections (%d)',
					Object.keys( selectedConnections ).length,
					'distributor'
				),
				Object.keys( selectedConnections ).length
			);
			document.querySelector(
				'.selected-connections-text'
			).textContent = selectedText;

			if ( selectAllConnections.classList.contains( 'unavailable' ) ) {
				classList( 'all' );
			}
			if ( ! Object.keys( selectedConnections ).length ) {
				classList( 'addEmpty' );
				classList( 'noneUnavailable' );
			}

			showConnections();
		}
	);
} );
