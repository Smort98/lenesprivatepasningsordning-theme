import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { sti, tekst, klasse } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Link', 'lene' ) }>
					<TextControl
						label={ __( 'Sti (fra forsiden, fx /kontakt/)', 'lene' ) }
						value={ sti }
						onChange={ ( value ) => setAttributes( { sti: value } ) }
					/>
					<TextControl
						label={ __( 'Linktekst', 'lene' ) }
						value={ tekst }
						onChange={ ( value ) => setAttributes( { tekst: value } ) }
					/>
					<TextControl
						label={ __( 'Ekstra CSS-klasse (valgfri)', 'lene' ) }
						value={ klasse }
						onChange={ ( value ) => setAttributes( { klasse: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<span { ...blockProps }>
				<ServerSideRender block="lene/site-link" attributes={ attributes } />
			</span>
		</>
	);
}
