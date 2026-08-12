import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { IKON_OPTIONS, dashiconFor } from './ikoner';

export default function Edit( { attributes, setAttributes } ) {
	const { ikon, titel, tekst } = attributes;
	const blockProps = useBlockProps( { className: 'card' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Regel', 'lene' ) }>
					<SelectControl
						label={ __( 'Ikon', 'lene' ) }
						value={ ikon }
						options={ IKON_OPTIONS.map( ( o ) => ( { label: o.label, value: o.value } ) ) }
						onChange={ ( v ) => setAttributes( { ikon: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<article { ...blockProps }>
				<div className="card__body">
					<span className={ `card__icon dashicons dashicons-${ dashiconFor( ikon ) }` } aria-hidden="true" />
					<RichText
						tagName="h3"
						value={ titel }
						onChange={ ( v ) => setAttributes( { titel: v } ) }
						placeholder={ __( 'Fx "Feber"', 'lene' ) }
						allowedFormats={ [] }
					/>
					<RichText
						tagName="p"
						value={ tekst }
						onChange={ ( v ) => setAttributes( { tekst: v } ) }
						placeholder={ __( 'Én kort sætning…', 'lene' ) }
					/>
				</div>
			</article>
		</>
	);
}
