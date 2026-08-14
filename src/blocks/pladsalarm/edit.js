import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { titel, tekst, baggrund } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Pladsalarm', 'lene' ) }>
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<TextareaControl label={ __( 'Tekst', 'lene' ) } value={ tekst } onChange={ ( v ) => setAttributes( { tekst: v } ) } />
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
				<ServerSideRender block="lene/pladsalarm" attributes={ attributes } />
			</div>
		</>
	);
}
