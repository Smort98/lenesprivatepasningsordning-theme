import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { aar, titel, tekst } = attributes;
	const blockProps = useBlockProps.save( { className: 'timeline__item' } );

	return (
		<li { ...blockProps }>
			<RichText.Content tagName="span" className="timeline__aar" value={ aar } />
			<div>
				<RichText.Content tagName="h3" value={ titel } />
				<RichText.Content tagName="p" value={ tekst } />
			</div>
		</li>
	);
}
