import jQuery from 'jquery'
import _ from 'underscores'
import { dt, dtConnections } from 'window'

let selectedConnections = {},
	searchString        = ''

const processTemplate = _.memoize( ( id ) => {
	const element = document.getElementById( id )
	if ( ! element ) {
		return false
	}

	// Use WordPress style Backbone template syntax
	const options = {
		evaluate:    /<#([\s\S]+?)#>/g,
		interpolate: /{{{([\s\S]+?)}}}/g,
		escape:      /{{([^}]+?)}}(?!})/g
	}

	return _.template( element.innerHTML, null, options )
} )

jQuery( window ).load( () => {
	const distributorMenuItem     = document.querySelector( '#wp-admin-bar-distributor' )
	const distributorPushWrapper  = document.querySelector( '#distributor-push-wrapper' )

	if ( ! distributorMenuItem || ! distributorPushWrapper ) {
		return
	}

	const connectionsSelected     = distributorPushWrapper.querySelector( '.connections-selected' )
	const connectionsSelectedList = distributorPushWrapper.querySelector( '.selected-connections-list' )
	const connectionsNewList      = distributorPushWrapper.querySelector( '.new-connections-list' )
	const connectionsSearchInput  = document.getElementById( 'dt-connection-search' )
	const syndicateButton         = distributorPushWrapper.querySelector( '.syndicate-button' )
	const actionWrapper           = distributorPushWrapper.querySelector( '.action-wrapper' )
	const asDraftInput            = document.getElementById( 'dt-as-draft' )

	distributorMenuItem.appendChild( distributorPushWrapper )

	/**
		 * Handle UI error changes
		 */
	function doError() {
		distributorPushWrapper.classList.add( 'message-error' )

		setTimeout( () => {
			distributorPushWrapper.classList.remove( 'message-error' )
		}, 6000 )
	}

	/**
	 * Handle UI success changes
	 */
	function doSuccess( results ) {
		let error = false

		_.each( results.internal, ( result, connectionId ) => {
			if ( result.status === 'fail' ) {
				error = true
			} else {
				dtConnections['internal' + connectionId].syndicated = result.url
			}
		} )

		_.each( results.external, ( result, connectionId ) => {
			if ( result.status === 'fail' ) {
				error = true
			} else {
				dtConnections['external' + connectionId].syndicated = true
			}
		} )

		if ( error ) {
			doError()
		} else {
			distributorPushWrapper.classList.add( 'message-success' )

			connectionsSelected.classList.add( 'empty' )
			connectionsSelectedList.innerText = ''

			setTimeout( () => {
				distributorPushWrapper.classList.remove( 'message-success' )
			}, 6000 )
		}

		selectedConnections = {}

		showConnections()
	}

	/**
		 * Show connections. If there is a search string, then filter by it
		 */
	function showConnections() {
		connectionsNewList.innerText = ''

		_.each( dtConnections, ( connection ) => {
			if ( searchString !== '' ) {
				let nameMatch = connection.name.replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() )
				let urlMatch  = connection.url.replace( /https?:\/\//i, '' ).replace( /www/i, '' ).replace( /[^0-9a-zA-Z ]+/, '' ).toLowerCase().match( searchString.toLowerCase() )

				if ( ! nameMatch && ! urlMatch ) {
					return
				}
			}

			const showConnection = processTemplate( 'dt-add-connection' )( {
				connection: connection,
				selectedConnections: selectedConnections
			} )

			connectionsNewList.innerHTML += showConnection
		} )
	}

	/**
	 * Handle distributor push dropdown menu hover using hoverIntent.
	 */
	function distributorMenuEntered() {
		distributorMenuItem.focus()
		document.body.classList.toggle( 'distributor-show' )
	}

	function distributorMenuExited() {
		distributorMenuItem.blur()
		document.body.classList.toggle( 'distributor-show' )
	}

	jQuery( distributorMenuItem ).hoverIntent( distributorMenuEntered, 300, distributorMenuExited )

	/**
	 * Do syndication ajax
	 */
	jQuery( syndicateButton ).on( 'click', () => {
		if ( actionWrapper.classList.contains( 'loading' ) ) {
			return
		}

		actionWrapper.classList.add( 'loading' )

		const data = {
			action: 'dt_push',
			nonce: dt.nonce,
			connections: selectedConnections,
			post_id: dt.post_id
		}

		if ( asDraftInput.checked ) {
			data.draft = true
		}

		const xhr = dt.usexhr ? { withCredentials: true } : false

		jQuery.ajax( {
			url: dt.ajaxurl,
			xhrFields: xhr,
			method: 'post',
			data: data
		} ).done( ( response ) => {
			setTimeout( () => {
				actionWrapper.classList.remove( 'loading' )

				if ( ! response.data || ! response.data.results ) {
					doError()
					return
				}

				doSuccess( response.data.results )
			}, 500 )
		} ).error( () => {
			setTimeout( () => {
				actionWrapper.classList.remove( 'loading' )

				doError()
			}, 500 )
		} )
	} )

	/**
	 * Add a connection to selected connections for ajax and to the UI list.
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.add-connection', ( event ) => {
		if ( event.target.nodeName === 'A' ) {
			return
		}

		event.preventDefault()

		if ( event.currentTarget.classList.contains( 'syndicated' ) ) {
			return
		}

		if ( event.currentTarget.classList.contains( 'added' ) ) {

			const type = event.currentTarget.getAttribute( 'data-connection-type' )
			const id   = event.currentTarget.getAttribute( 'data-connection-id' )

			const deleteNode = connectionsSelectedList.querySelector( '[data-connection-id="' + id + '"][data-connection-type="' + type + '"]' )

			deleteNode.parentNode.removeChild( deleteNode )

			delete selectedConnections[type + id]

			if ( ! Object.keys( selectedConnections ).length ) {
				connectionsSelected.classList.add( 'empty' )
			}

			showConnections()
		} else {

			const type = event.currentTarget.getAttribute( 'data-connection-type' )
			const id   = event.currentTarget.getAttribute( 'data-connection-id' )

			selectedConnections[type + id] = dtConnections[type + id]

			connectionsSelected.classList.remove( 'empty' )

			const element       = event.currentTarget.cloneNode()
			element.innerText = event.currentTarget.innerText

			const removeLink = document.createElement( 'span' )
			removeLink.classList.add( 'remove-connection' )

			element.appendChild( removeLink )
			element.classList = 'added-connection'

			connectionsSelectedList.appendChild( element )

			showConnections()
		}
	} )

	/**
	 * Remove a connection from selected connections and the UI list
	 */
	jQuery( distributorPushWrapper ).on( 'click', '.remove-connection', ( event ) => {
		event.currentTarget.parentNode.parentNode.removeChild( event.currentTarget.parentNode )
		const type = event.currentTarget.parentNode.getAttribute( 'data-connection-type' )
		const id   = event.currentTarget.parentNode.getAttribute( 'data-connection-id' )

		delete selectedConnections[type + id]

		if ( ! Object.keys( selectedConnections ).length ) {
			connectionsSelected.classList.add( 'empty' )
		}

		showConnections()
	} )

	/**
	 * List for connection filtering
	 */
	jQuery( connectionsSearchInput ).on( 'keyup change', _.debounce( ( event ) => {
		if ( event.currentTarget.value === '' ) {
			showConnections( dtConnections )
		}

		searchString = event.currentTarget.value.replace( /https?:\/\//i, '' ).replace( /www/i, '' ).replace( /[^0-9a-zA-Z ]+/, '' )

		showConnections()
	}, 300 ) )
} )
