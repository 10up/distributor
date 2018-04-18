import { wp, dtGutenberg } from 'window'
import jQuery from 'jquery'

wp.i18n.setLocaleData( dtGutenberg.i18n, 'distributor' )

const messages = []

if ( !! parseInt( dtGutenberg.originalDeleted ) ) {
	messages.push( wp.i18n.sprintf( wp.i18n.__( 'This %s was distributed from ' ), dtGutenberg.postTypeSingular ) )
	messages.push( wp.element.createElement( 'a', {
		href: dtGutenberg.postUrl
	}, [
		dtGutenberg.originalLocationName
	] ) )
	messages.push( wp.i18n.__( '. However, the original has been deleted.' ) )
} else if ( ! parseInt( dtGutenberg.unlinked ) ) {
	messages.push( wp.i18n.__( 'Distributed from ', 'distributor' ) )

	messages.push( wp.element.createElement( 'a', {
		href: dtGutenberg.postUrl
	}, [
		dtGutenberg.originalLocationName
	] ) )

	messages.push( '.' )

	messages.push( wp.element.createElement( 'span', {}, [
		wp.i18n.sprintf( wp.i18n.__( " The original %1$s will update this version unless you ", 'distributor' ), dtGutenberg.postTypeSingular.toLowerCase() ),
		wp.element.createElement( 'a', {
			href: dtGutenberg.unlinkNonceUrl
		}, [
			wp.i18n.__( 'unlink from the original.', 'distributor' )
		] )
	] ) )
} else {
	messages.push( wp.i18n.__( 'Originally distributed from ', 'distributor' ) )

	messages.push( wp.element.createElement( 'a', {
		href: dtGutenberg.postUrl
	}, [
		dtGutenberg.originalLocationName
	] ) )

	messages.push( '.' )

	messages.push( wp.element.createElement( 'span', {}, [
		wp.i18n.sprintf( wp.i18n.__( " This %1$s has been unlinked from the original. However, you can always ", 'distributor' ), dtGutenberg.postTypeSingular.toLowerCase() ),
		wp.element.createElement( 'a', {
			href: dtGutenberg.linkNonceUrl
		}, [
			wp.i18n.__( 'restore it.', 'distributor' )
		] )
	] ) )
}

const messageElement = wp.element.createElement( 'p', {
	className: 'dt-message-wrapper'
}, messages )

wp.data.dispatch( 'core/editor' ).createWarningNotice( messageElement, {
	isDismissible: false
} )
