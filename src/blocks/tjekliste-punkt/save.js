import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { tekst, note } = attributes;
	const blockProps = useBlockProps.save( { className: 'tjekliste__item' } );

	return (
		<li { ...blockProps }>
			<label>
				<input type="checkbox" className="tjekliste__box" />
				<RichText.Content tagName="span" className="tjekliste__tekst" value={ tekst } />
				{ note && <RichText.Content tagName="span" className="tjekliste__note" value={ note } /> }
			</label>
		</li>
	);
}
