import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const {
		overrubrik,
		titel,
		tekst,
		knapPrimaerTekst,
		knapPrimaerLink,
		knapSekundaerTekst,
		knapSekundaerLink,
		fakta,
		billede,
		visNaestePlads,
	} = attributes;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tekst', 'lene' ) }>
					<TextControl
						label={ __( 'Overrubrik', 'lene' ) }
						value={ overrubrik }
						onChange={ ( value ) => setAttributes( { overrubrik: value } ) }
					/>
					<TextControl
						label={ __( 'Titel', 'lene' ) }
						value={ titel }
						onChange={ ( value ) => setAttributes( { titel: value } ) }
					/>
					<TextareaControl
						label={ __( 'Tekst', 'lene' ) }
						value={ tekst }
						onChange={ ( value ) => setAttributes( { tekst: value } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Knapper', 'lene' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Primær knap – tekst', 'lene' ) }
						value={ knapPrimaerTekst }
						onChange={ ( value ) => setAttributes( { knapPrimaerTekst: value } ) }
					/>
					<TextControl
						label={ __( 'Primær knap – link', 'lene' ) }
						value={ knapPrimaerLink }
						onChange={ ( value ) => setAttributes( { knapPrimaerLink: value } ) }
					/>
					<TextControl
						label={ __( 'Sekundær knap – tekst', 'lene' ) }
						value={ knapSekundaerTekst }
						onChange={ ( value ) => setAttributes( { knapSekundaerTekst: value } ) }
					/>
					<TextControl
						label={ __( 'Sekundær knap – link', 'lene' ) }
						value={ knapSekundaerLink }
						onChange={ ( value ) => setAttributes( { knapSekundaerLink: value } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Punkter under stregen', 'lene' ) } initialOpen={ false }>
					<TextareaControl
						label={ __( 'Ét punkt pr. linje', 'lene' ) }
						value={ ( fakta || [] ).join( '\n' ) }
						onChange={ ( value ) =>
							setAttributes( { fakta: value.split( '\n' ) } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Billede', 'lene' ) } initialOpen={ false }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									billede: { id: media.id, url: media.url, alt: media.alt || '' },
								} )
							}
							allowedTypes={ [ 'image' ] }
							value={ billede?.id }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ billede?.url
										? __( 'Skift billede', 'lene' )
										: __( 'Vælg billede', 'lene' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
				<PanelBody title={ __( 'Næste ledige plads', 'lene' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Vis kortet automatisk', 'lene' ) }
						checked={ visNaestePlads }
						onChange={ ( value ) => setAttributes( { visNaestePlads: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/hero" attributes={ attributes } />
			</div>
		</>
	);
}
