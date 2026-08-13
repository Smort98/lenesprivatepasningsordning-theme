import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, tekst } = attributes;
	const blockProps = useBlockProps( { className: 'pagehead' } );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<RichText
					tagName="p"
					className="eyebrow"
					value={ eyebrow }
					onChange={ ( v ) => setAttributes( { eyebrow: v } ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="h1"
					value={ titel }
					onChange={ ( v ) => setAttributes( { titel: v } ) }
					placeholder={ __( 'Titel', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					className="lede"
					value={ tekst }
					onChange={ ( v ) => setAttributes( { tekst: v } ) }
					placeholder={ __( 'Kort intro…', 'lene' ) }
				/>
			</div>
		</section>
	);
}
