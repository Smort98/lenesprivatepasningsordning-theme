import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[ 'lene/temagitter-tema', { ikon: 'udvikling', titel: 'Barnets alsidige personlige udvikling', tekst: 'Vi støtter barnets selvværd, mod og lyst til at udforske verden.' } ],
	[ 'lene/temagitter-tema', { ikon: 'sociale', titel: 'Sociale kompetencer', tekst: 'Vi øver venskaber, empati og at indgå i fællesskaber.' } ],
	[ 'lene/temagitter-tema', { ikon: 'sprog', titel: 'Sprog', tekst: 'Vi synger, snakker og læser højt hver dag.' } ],
	[ 'lene/temagitter-tema', { ikon: 'krop', titel: 'Krop og bevægelse', tekst: 'Vi er ude hver dag og bruger kroppen aktivt.' } ],
	[ 'lene/temagitter-tema', { ikon: 'natur', titel: 'Natur og naturfænomener', tekst: 'Vi udforsker haven, dyrene og årstiderne sammen.' } ],
	[ 'lene/temagitter-tema', { ikon: 'kultur', titel: 'Kulturelle udtryksformer', tekst: 'Vi tegner, synger og fejrer traditioner sammen.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, kolonner, baggrund } = attributes;
	const blockProps = useBlockProps( { className: `section section--${ baggrund === 'paper' ? 'paper' : 'sky' }` } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'cards', style: { '--kolonner': String( kolonner ) } },
		{ allowedBlocks: [ 'lene/temagitter-tema' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Temagitter', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<RangeControl label={ __( 'Kolonner', 'lene' ) } value={ kolonner } onChange={ ( v ) => setAttributes( { kolonner: v } ) } min={ 2 } max={ 3 } />
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
