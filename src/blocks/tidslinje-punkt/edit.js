import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { aar, titel, tekst } = attributes;
	const blockProps = useBlockProps( { className: 'timeline__item' } );

	return (
		<li { ...blockProps }>
			<RichText
				tagName="span"
				className="timeline__aar"
				value={ aar }
				onChange={ ( v ) => setAttributes( { aar: v } ) }
				placeholder={ __( '2019', 'lene' ) }
				allowedFormats={ [] }
			/>
			<div>
				<RichText
					tagName="h3"
					value={ titel }
					onChange={ ( v ) => setAttributes( { titel: v } ) }
					placeholder={ __( 'Titel', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					value={ tekst }
					onChange={ ( v ) => setAttributes( { tekst: v } ) }
					placeholder={ __( 'Kort tekst…', 'lene' ) }
				/>
			</div>
		</li>
	);
}
