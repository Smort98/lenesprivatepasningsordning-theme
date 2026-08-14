import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	RangeControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const {
		retning,
		overskrift,
		visKunLedige,
		visPrikker,
		maksAntal,
		visOpdateret,
		baggrund,
	} = attributes;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Pladstavle', 'lene' ) }>
					<SelectControl
						label={ __( 'Retning', 'lene' ) }
						value={ retning }
						options={ [
							{ label: __( 'Vandret (forside)', 'lene' ), value: 'vandret' },
							{ label: __( 'Lodret (underside)', 'lene' ), value: 'lodret' },
						] }
						onChange={ ( value ) => setAttributes( { retning: value } ) }
						help={ __(
							'Samme skilte — vipper automatisk til lodret under 900px uanset valg.',
							'lene'
						) }
					/>
					<TextControl
						label={ __( 'Overskrift', 'lene' ) }
						value={ overskrift }
						onChange={ ( value ) => setAttributes( { overskrift: value } ) }
					/>
					<ToggleControl
						label={ __( 'Vis kun ledige og delvist ledige', 'lene' ) }
						checked={ visKunLedige }
						onChange={ ( value ) => setAttributes( { visKunLedige: value } ) }
					/>
					<ToggleControl
						label={ __( 'Vis prikker (én pr. plads)', 'lene' ) }
						checked={ visPrikker }
						onChange={ ( value ) => setAttributes( { visPrikker: value } ) }
					/>
					<ToggleControl
						label={ __( 'Vis "Opdateret <dato>"', 'lene' ) }
						checked={ visOpdateret }
						onChange={ ( value ) => setAttributes( { visOpdateret: value } ) }
					/>
					<RangeControl
						label={ __( 'Maks. antal (0 = alle)', 'lene' ) }
						value={ maksAntal }
						onChange={ ( value ) => setAttributes( { maksAntal: value } ) }
						min={ 0 }
						max={ 12 }
					/>
					<SelectControl
						label={ __( 'Baggrund', 'lene' ) }
						value={ baggrund }
						options={ [
							{ label: __( 'Dis (grågrøn)', 'lene' ), value: 'sky' },
							{ label: __( 'Hvid', 'lene' ), value: 'paper' },
						] }
						onChange={ ( value ) => setAttributes( { baggrund: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="lene/pladstavle"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
