import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { citat, kilde } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );

	return (
		<section { ...blockProps }>
			<div className="wrap quote">
				<RichText
					tagName="blockquote"
					value={ citat }
					onChange={ ( value ) => setAttributes( { citat: value } ) }
					placeholder={ __( 'Skriv citatet…', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="cite"
					value={ kilde }
					onChange={ ( value ) => setAttributes( { kilde: value } ) }
					placeholder={ __( 'Kilde, fx “Forælder i Ringe”', 'lene' ) }
					allowedFormats={ [] }
				/>
			</div>
		</section>
	);
}
