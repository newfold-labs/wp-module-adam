import { useEffect, useRef } from '@wordpress/element';
import { getCleanBodyInner, isCFScript } from '../utils/cfScripts';

/**
 * Renders a single Adam fragment: injects links into head, renders body HTML, executes script tags.
 *
 * @param {Object} props
 * @param {string} props.bodyContent - Fragment body HTML.
 * @param {Array}  props.linkTags   - Array of { rel, href, as?, type?, media? }.
 * @param {Array}  props.scriptTags - Array of { src?, type? }.
 * @return {JSX.Element} Rendered fragment.
 */
export const AdamFragment = ( {
	bodyContent,
	linkTags = [],
	scriptTags = [],
} ) => {
	const containerRef = useRef( null );
	const bodyInner = getCleanBodyInner( bodyContent );

	useEffect( () => {
		const injectedLinks = [];
		const injectedScripts = [];

		if ( Array.isArray( linkTags ) ) {
			linkTags.forEach( ( tag ) => {
				if ( ! tag?.href ) return;
				if (
					document.querySelector(
						`link[href="${ CSS.escape( tag.href ) }"]`
					)
				) {
					return;
				}
				const linkEl = document.createElement( 'link' );
				linkEl.rel = tag.rel || 'stylesheet';
				linkEl.href = tag.href;
				if ( tag.as ) linkEl.as = tag.as;
				if ( tag.type ) linkEl.type = tag.type;
				if ( tag.media ) linkEl.media = tag.media;
				document.head.appendChild( linkEl );
				injectedLinks.push( linkEl );
			} );
		}

		if ( Array.isArray( scriptTags ) ) {
			scriptTags.forEach( ( tag ) => {
				if ( ! tag?.src ) return;
				if ( isCFScript( tag.src ) ) return;
				if (
					document.querySelector(
						`script[src="${ CSS.escape( tag.src ) }"]`
					)
				) {
					return;
				}
				const scriptEl = document.createElement( 'script' );
				scriptEl.src = tag.src;
				scriptEl.async = false;
				if ( tag.type ) scriptEl.type = tag.type;
				document.body.appendChild( scriptEl );
				injectedScripts.push( scriptEl );
			} );
		}

		if ( containerRef.current ) {
			const walker = document.createTreeWalker(
				containerRef.current,
				NodeFilter.SHOW_TEXT,
				null
			);
			const nodesToRemove = [];
			while ( walker.nextNode() ) {
				if ( isCFScript( walker.currentNode.textContent ) ) {
					nodesToRemove.push( walker.currentNode );
				}
			}
			nodesToRemove.forEach( ( node ) => node.remove() );

			const inlineScripts =
				containerRef.current.querySelectorAll( 'script' );
			inlineScripts.forEach( ( orig ) => {
				const content = orig.textContent || '';
				const src = orig.src || '';
				if ( isCFScript( content ) || isCFScript( src ) ) {
					orig.remove();
					return;
				}
				const fresh = document.createElement( 'script' );
				Array.from( orig.attributes ).forEach( ( attr ) => {
					fresh.setAttribute( attr.name, attr.value );
				} );
				fresh.textContent = content;
				orig.parentNode.replaceChild( fresh, orig );
			} );
		}

		return () => {
			injectedLinks.forEach( ( el ) => el.remove() );
			injectedScripts.forEach( ( el ) => el.remove() );
		};
	}, [ bodyInner, linkTags, scriptTags ] );

	return (
		<div
			ref={ containerRef }
			className="adam-frag adam-mb-4"
			data-test-id="app-aside-adam-card"
			dangerouslySetInnerHTML={ { __html: bodyInner } }
		/>
	);
};
