import './styles/styles.css';

import domReady from '@wordpress/dom-ready';
import { createRoot, createPortal, useEffect, useState } from '@wordpress/element';

import { AdamAside } from './components/AdamAside';

const AdamPortalApp = () => {
	const [ container, setContainer ] = useState( null );

	useEffect( () => {
		const registry = window.NFDPortalRegistry;
		if ( ! registry ) {
			return;
		}

		const updateContainer = ( el ) => setContainer( el );
		const clearContainer = () => setContainer( null );

		registry.onReady( 'adam', updateContainer );
		registry.onRemoved( 'adam', clearContainer );

		const current = registry.getElement( 'adam' );
		if ( current ) {
			updateContainer( current );
		}
	}, [] );

	if ( ! container ) {
		return null;
	}

	return createPortal( <AdamAside />, container );
};

// Mount to a wrapper so we don't depend on the portal div existing yet (plugin creates it in React).
const mountAdamPortal = () => {
	const wrapper = document.createElement( 'div' );
	wrapper.id = 'nfd-adam-root';
	document.body.appendChild( wrapper );
	const root = createRoot( wrapper );
	root.render( <AdamPortalApp /> );
};

domReady( mountAdamPortal );
