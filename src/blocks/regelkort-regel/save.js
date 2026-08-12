import { useBlockProps, RichText } from '@wordpress/block-editor';
import { dashiconFor } from './ikoner';

export default function save( { attributes } ) {
	const { ikon, titel, tekst } = attributes;
	const blockProps = useBlockProps.save( { className: 'card' } );

	return (
		<article { ...blockProps }>
			<div className="card__body">
				<span className={ `card__icon dashicons dashicons-${ dashiconFor( ikon ) }` } aria-hidden="true" />
				<RichText.Content tagName="h3" value={ titel } />
				<RichText.Content tagName="p" value={ tekst } />
			</div>
		</article>
	);
}
