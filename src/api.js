import { NewfoldRuntime } from '@newfold/wp-module-runtime';
import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch Adam (cross-sell) items for a container from the module REST API.
 *
 * @param {string} containerName - Container name (e.g. 'AMHPCardsV2').
 * @return {Promise<{ response: Array }>} Promise resolving to { response: items }.
 */
export const fetchAdam = async ( containerName ) => {
	const baseUrl = NewfoldRuntime.createApiUrl(
		'/newfold-adam/v1/containers'
	);
	const url =
		baseUrl +
		( baseUrl.indexOf( '?' ) !== -1 ? '&' : '?' ) +
		'container=' +
		encodeURIComponent( containerName );
	return await apiFetch( { url } );
};
