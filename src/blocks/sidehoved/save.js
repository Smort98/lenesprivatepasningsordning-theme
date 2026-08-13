import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, tekst } = attributes;
	const blockProps = useBlockProps.save( { className: 'pagehead' } );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<RichText.Content tagName="p" className="eyebrow" value={ eyebrow } />
				<RichText.Content tagName="h1" value={ titel } />
				<RichText.Content tagName="p" className="lede" value={ tekst } />
			</div>
		</section>
	);
}
