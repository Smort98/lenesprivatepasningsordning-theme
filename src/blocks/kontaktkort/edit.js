import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, ToggleControl, ExternalLink } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, tekst, visFormular } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Kontaktkort', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<TextareaControl label={ __( 'Tekst', 'lene' ) } value={ tekst } onChange={ ( v ) => setAttributes( { tekst: v } ) } />
					<ToggleControl label={ __( 'Vis formular', 'lene' ) } checked={ visFormular } onChange={ ( v ) => setAttributes( { visFormular: v } ) } />
					<p>
						<ExternalLink href={ '/wp-admin/options-general.php?page=lene-indstillinger' }>
							{ __( 'Ret navn, adresse, telefon og e-mail', 'lene' ) }
						</ExternalLink>
					</p>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/kontaktkort" attributes={ attributes } />
			</div>
		</>
	);
}
