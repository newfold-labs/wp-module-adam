import { NewfoldRuntime } from '@newfold/wp-module-runtime';
import apiFetch from '@wordpress/api-fetch';

const DEFAULT_REST_NAMESPACE = '/newfold-adam/v1';

/**
 * Fetch Adam (cross-sell) items from the module REST API.
 * Uses REST namespace from NewfoldRuntime when set by the backend (wp-module-adam).
 *
 * @return {Promise<{ response: Array }>} Promise resolving to { response: items }.
 */
export const fetchAdam = async () => {
	const restNamespace =
		NewfoldRuntime.adam?.restNamespace || DEFAULT_REST_NAMESPACE;
	const path =
		restNamespace.startsWith( '/' ) ? `${ restNamespace }/items` : `/${ restNamespace }/items`;
	const url = NewfoldRuntime.createApiUrl( path );
	return await apiFetch( { url } );
};
