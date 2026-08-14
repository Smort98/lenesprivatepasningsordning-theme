import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, footTekst, baggrund } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Lukkedage', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<TextareaControl
						label={ __( 'Tekst under listen', 'lene' ) }
						value={ footTekst }
						onChange={ ( v ) => setAttributes( { footTekst: v } ) }
					/>
					<SelectControl
						label={ __( 'Baggrund', 'lene' ) }
						value={ baggrund }
						options={ [
							{ label: __( 'Dis (grågrøn)', 'lene' ), value: 'sky' },
							{ label: __( 'Hvid', 'lene' ), value: 'paper' },
						] }
						onChange={ ( v ) => setAttributes( { baggrund: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/lukkedage" attributes={ attributes } />
			</div>
		</>
	);
}
