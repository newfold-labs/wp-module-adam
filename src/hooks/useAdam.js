import { useEffect, useState } from '@wordpress/element';
import { fetchAdam } from '../api';

const RETRY_DELAY_MS = 1500;
const CLIENT_ERROR_MIN = 400;
const CLIENT_ERROR_MAX = 499;

/**
 * Returns true if the error is retryable (e.g. network or 5xx). Skips retry for 4xx client errors.
 * apiFetch rejects with the REST response body when present, which may include data.status.
 *
 * @param {Error & { code?: string, data?: { status?: number } }} err - Error from apiFetch.
 * @return {boolean}
 */
function isRetryableError( err ) {
	const status = err?.data?.status;
	if ( typeof status === 'number' && status >= CLIENT_ERROR_MIN && status <= CLIENT_ERROR_MAX ) {
		return false;
	}
	return true;
}

/**
 * @param {number} ms
 * @return {Promise<void>}
 */
const delay = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

/**
 * Fetches Adam items and returns items, loading, and error state.
 *
 * @return {{ items: Array, loading: boolean, error: Error|null }} Object with items array, loading flag, and error if any.
 */
export const useAdam = () => {
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		setLoading( true );
		setError( null );

		const fetchWithRetry = () =>
			fetchAdam().catch( ( err ) => {
				if ( ! isRetryableError( err ) ) {
					throw err;
				}
				return delay( RETRY_DELAY_MS ).then( () =>
					fetchAdam().catch( () => {
						throw err;
					} )
				);
			} );

		fetchWithRetry()
			.then( ( data ) => {
				const list = data?.response ?? [];
				setItems( Array.isArray( list ) ? list : [] );
			} )
			.catch( ( err ) => {
				setError( err );
				setItems( [] );
			} )
			.finally( () => {
				setLoading( false );
			} );
	}, [] );

	return { items, loading, error };
};
