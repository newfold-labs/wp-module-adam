/**
 * Check whether a script string is a Cloudflare challenge script.
 *
 * @param {string} text - Script content or src to check.
 * @return {boolean} True if the text matches CF challenge patterns.
 */
export function isCFScript( text ) {
	return (
		/window\.__CF\$cv\$params/.test( text ) ||
		/challenge-platform/.test( text ) ||
		/cdn-cgi\/challenge-platform/.test( text )
	);
}

/**
 * Strip Cloudflare challenge scripts from an HTML string.
 *
 * @param {string} html - Raw HTML string.
 * @return {string} HTML with CF challenge scripts removed.
 */
export function stripCFScripts( html ) {
	if ( typeof html !== 'string' ) {
		return '';
	}
	html = html.replace(
		/<script\b[^>]*>[\s\S]*?<\/script>/gi,
		( match ) => ( isCFScript( match ) ? '' : match )
	);
	html = html.replace(
		/\(function\(\)\{function c\(\)\{var b=a\.contentDocument[\s\S]*?\}\)\(\);?/g,
		''
	);
	return html;
}

/**
 * Extract body inner HTML from a full-document string, stripping CF scripts.
 *
 * @param {string} bodyContent - Fragment body HTML.
 * @return {string} Cleaned inner HTML.
 */
export function getCleanBodyInner( bodyContent ) {
	if ( typeof bodyContent !== 'string' ) {
		return '';
	}
	const inner = bodyContent
		.replace( /^[\s\S]*?<body[^>]*>/i, '' )
		.replace( /<\/body>[\s\S]*$/i, '' );
	return stripCFScripts( inner );
}
