import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[ 'lene/regelkort-regel', { ikon: 'feber', titel: 'Feber', tekst: 'Et døgn hjemme uden feber, før barnet kan komme igen.' } ],
	[ 'lene/regelkort-regel', { ikon: 'opkast', titel: 'Opkast', tekst: 'Et døgn uden opkast, før barnet kan komme igen.' } ],
	[ 'lene/regelkort-regel', { ikon: 'telefon', titel: 'Sygemelding', tekst: 'Giv besked senest kl. 8.30 samme morgen.' } ],
	[ 'lene/regelkort-regel', { ikon: 'tjek', titel: 'Raskmelding', tekst: 'Giv besked senest kl. 18 aftenen inden.' } ],
	[ 'lene/regelkort-regel', { ikon: 'info', titel: 'Hvornår er man "syg"?', tekst: 'Når barnet ikke kan følge en almindelig dag — være ude, tage med på udflugt.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, kolonner } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'cards', style: { '--kolonner': kolonner } },
		{ allowedBlocks: [ 'lene/regelkort-regel' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Regelkort', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<RangeControl label={ __( 'Kolonner', 'lene' ) } value={ kolonner } onChange={ ( v ) => setAttributes( { kolonner: v } ) } min={ 2 } max={ 4 } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="wrap">
					<div className="section__head">
						<div>
							<p className="eyebrow">{ eyebrow }</p>
							<h2>{ titel }</h2>
						</div>
					</div>
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
