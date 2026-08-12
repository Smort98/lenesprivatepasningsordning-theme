import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { tidspunkt, titel, tekst } = attributes;
	const blockProps = useBlockProps();

	return (
		<li { ...blockProps }>
			<RichText
				tagName="span"
				className="time"
				value={ tidspunkt }
				onChange={ ( value ) => setAttributes( { tidspunkt: value } ) }
				placeholder={ __( '6.45', 'lene' ) }
				allowedFormats={ [] }
			/>
			<div>
				<RichText
					tagName="h3"
					value={ titel }
					onChange={ ( value ) => setAttributes( { titel: value } ) }
					placeholder={ __( 'Titel', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					value={ tekst }
					onChange={ ( value ) => setAttributes( { tekst: value } ) }
					placeholder={ __( 'Kort tekst…', 'lene' ) }
				/>
			</div>
		</li>
	);
}
