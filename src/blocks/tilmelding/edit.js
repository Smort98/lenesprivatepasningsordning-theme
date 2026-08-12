import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, tekst } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tilmelding', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<TextareaControl label={ __( 'Tekst', 'lene' ) } value={ tekst } onChange={ ( v ) => setAttributes( { tekst: v } ) } />
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/tilmelding" attributes={ attributes } />
			</div>
		</>
	);
}
