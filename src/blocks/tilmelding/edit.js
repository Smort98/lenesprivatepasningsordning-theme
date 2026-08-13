import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, tekst, punkter } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tilmelding', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<TextareaControl label={ __( 'Tekst', 'lene' ) } value={ tekst } onChange={ ( v ) => setAttributes( { tekst: v } ) } />
					<TextareaControl
						label={ __( 'Punkter (én pr. linje)', 'lene' ) }
						value={ ( punkter || [] ).join( '\n' ) }
						onChange={ ( v ) => setAttributes( { punkter: v.split( '\n' ).filter( ( line ) => line.trim() !== '' ) } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/tilmelding" attributes={ attributes } />
			</div>
		</>
	);
}
