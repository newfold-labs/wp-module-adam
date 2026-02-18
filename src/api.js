import { NewfoldRuntime } from '@newfold/wp-module-runtime';
import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch Adam (cross-sell) items from the module REST API.
 *
 * @return {Promise<{ response: Array }>} Promise resolving to { response: items }.
 */
export const fetchAdam = async () => {
	const url = NewfoldRuntime.createApiUrl( '/newfold-adam/v1/items' );
	return await apiFetch( { url } );
};
