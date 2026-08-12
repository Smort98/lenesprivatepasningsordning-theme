import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { tekst, beloeb, erMinus } = attributes;
	const blockProps = useBlockProps( { className: 'receipt__row' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Prislinje', 'lene' ) }>
					<ToggleControl
						label={ __( 'Vis som fradrag (minus)', 'lene' ) }
						checked={ erMinus }
						onChange={ ( v ) => setAttributes( { erMinus: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText
					tagName="span"
					className="receipt__label"
					value={ tekst }
					onChange={ ( v ) => setAttributes( { tekst: v } ) }
					placeholder={ __( 'Fx "Fuldtidsplads pr. måned"', 'lene' ) }
					allowedFormats={ [] }
				/>
				<span className="receipt__fill" />
				<RichText
					tagName="span"
					className={ `receipt__amount${ erMinus ? ' is-minus' : '' }` }
					value={ beloeb }
					onChange={ ( v ) => setAttributes( { beloeb: v } ) }
					placeholder={ __( 'Fx "9.100 kr."', 'lene' ) }
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
