import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

const TEMPLATE = [
	[ 'lene/tjekliste-punkt', { tekst: 'Barnevogn med godkendt sele', note: '' } ],
	[ 'lene/tjekliste-punkt', { tekst: 'Dyne/pude', note: 'kan evt. lånes' } ],
	[ 'lene/tjekliste-punkt', { tekst: 'Bleer og engangsvaskeklude', note: '' } ],
	[ 'lene/tjekliste-punkt', { tekst: 'Skiftetøj', note: '' } ],
	[ 'lene/tjekliste-punkt', { tekst: 'Udetøj og sko efter årstiden', note: '' } ],
	[ 'lene/tjekliste-punkt', { tekst: 'Specialkost', note: 'hvis relevant' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, visPrintknap } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'tjekliste' },
		{ allowedBlocks: [ 'lene/tjekliste-punkt' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tjekliste', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<ToggleControl
						label={ __( 'Vis printknap', 'lene' ) }
						checked={ visPrintknap }
						onChange={ ( v ) => setAttributes( { visPrintknap: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="wrap">
					<div className="section__head">
						<div>
							<p className="eyebrow">{ eyebrow }</p>
							<h2>{ titel }</h2>
						</div>
						{ visPrintknap && (
							<button type="button" className="btn btn--ghost tjekliste__print no-print" disabled>
								{ __( 'Print listen', 'lene' ) }
							</button>
						) }
					</div>
					<ul { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
