import { useEffect, useState } from '@wordpress/element';
import { fetchAdam } from '../api';

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

		fetchAdam()
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
