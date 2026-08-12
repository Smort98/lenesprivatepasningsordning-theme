import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { citat, kilde } = attributes;
	const blockProps = useBlockProps.save( { className: 'section' } );

	return (
		<section { ...blockProps }>
			<div className="wrap quote">
				<RichText.Content tagName="blockquote" value={ citat } />
				<RichText.Content tagName="cite" value={ kilde } />
			</div>
		</section>
	);
}
