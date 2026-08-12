import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { tekst, note } = attributes;
	const blockProps = useBlockProps( { className: 'tjekliste__item' } );

	return (
		<li { ...blockProps }>
			<label>
				<input type="checkbox" className="tjekliste__box" disabled />
				<RichText
					tagName="span"
					className="tjekliste__tekst"
					value={ tekst }
					onChange={ ( v ) => setAttributes( { tekst: v } ) }
					placeholder={ __( 'Fx "Skiftetøj"', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="span"
					className="tjekliste__note"
					value={ note }
					onChange={ ( v ) => setAttributes( { note: v } ) }
					placeholder={ __( '(valgfri note)', 'lene' ) }
					allowedFormats={ [] }
				/>
			</label>
		</li>
	);
}
