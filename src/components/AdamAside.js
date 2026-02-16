import { Spinner } from '@wordpress/components';
import { useAdam } from '../hooks/useAdam';
import { AdamFragment } from './AdamFragment';
import { DEFAULT_CONTAINER } from '../data/constants';

/**
 * Adam aside container: fetches items and maps to AdamFragment.
 *
 * @return {JSX.Element} Rendered aside content.
 */
export const AdamAside = () => {
	const { items, loading, error } = useAdam( DEFAULT_CONTAINER );

	if ( loading ) {
		return (
			<div className="adam-aside-loading adam-p-6 adam-flex adam-justify-center">
				<Spinner />
			</div>
		);
	}

	if ( error || ! items.length ) {
		return null;
	}

	return (
		<div className="adam-aside-list">
			{ items.map( ( item, index ) => {
				const markup = item?.adDetails?.productMarkup ?? {};
				const key =
					item?.adIdentifier ??
					markup?.bodyContent?.slice?.( 0, 50 ) ??
					index;
				return (
					<AdamFragment
						key={ key }
						bodyContent={ markup?.bodyContent ?? '' }
						linkTags={ markup?.linkTags ?? [] }
						scriptTags={ markup?.scriptTags ?? [] }
					/>
				);
			} ) }
		</div>
	);
};
