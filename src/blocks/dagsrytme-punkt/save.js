import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { tidspunkt, titel, tekst } = attributes;
	const blockProps = useBlockProps.save();

	return (
		<li { ...blockProps }>
			<RichText.Content tagName="span" className="time" value={ tidspunkt } />
			<div>
				<RichText.Content tagName="h3" value={ titel } />
				<RichText.Content tagName="p" value={ tekst } />
			</div>
		</li>
	);
}
