import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { billede, titel, tekst } = attributes;
	const blockProps = useBlockProps.save( { className: 'card' } );

	return (
		<article { ...blockProps }>
			<div className="card__img">
				{ billede?.url && (
					<img src={ billede.url } alt={ billede.alt || '' } loading="lazy" />
				) }
			</div>
			<div className="card__body">
				<RichText.Content tagName="h3" value={ titel } />
				<RichText.Content tagName="p" value={ tekst } />
			</div>
		</article>
	);
}
