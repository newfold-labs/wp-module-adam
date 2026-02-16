import { useEffect, useState } from '@wordpress/element';
import { fetchAdam } from '../api';

/**
 * Fetches Adam items for a container and returns items, loading, and error state.
 *
 * @param {string} containerName - Container name (e.g. 'AMHPCardsV2').
 * @return {{ items: Array, loading: boolean, error: Error|null }} Object with items array, loading flag, and error if any.
 */
export const useAdam = ( containerName ) => {
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		if ( ! containerName ) {
			setLoading( false );
			setItems( [] );
			return;
		}

		setLoading( true );
		setError( null );

		fetchAdam( containerName )
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
	}, [ containerName ] );

	return { items, loading, error };
};
