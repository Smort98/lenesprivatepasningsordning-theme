import { useBlockProps, useInnerBlocksProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[ 'lene/kortgitter-kort', { titel: 'Ude hver dag', tekst: 'Vi går på tur, samler pinde og bruger haven — også når det regner. Der er regntøj og gummistøvler til alle.' } ],
	[ 'lene/kortgitter-kort', { titel: 'En lille flok', tekst: 'Få børn betyder, at jeg kan nå omkring alle. Der er tid til at trøste, til at lytte og til at lege med.' } ],
	[ 'lene/kortgitter-kort', { titel: 'Godkendt og med tilskud', tekst: 'Ordningen er godkendt af kommunen, og du kan søge tilskud til pasningen. Jeg hjælper gerne med papirerne.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { titel, kolonner, baggrund } = attributes;
	const blockProps = useBlockProps( { className: `section section--${ baggrund === 'sky' ? 'sky' : 'paper' }` } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'cards', style: { '--kolonner': String( kolonner ) } },
		{ allowedBlocks: [ 'lene/kortgitter-kort' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Kortgitter', 'lene' ) }>
					<RangeControl
						label={ __( 'Kolonner', 'lene' ) }
						value={ kolonner }
						onChange={ ( value ) => setAttributes( { kolonner: value } ) }
						min={ 2 }
						max={ 4 }
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
			<section { ...blockProps }>
				<div className="wrap">
					<div className="section__head">
						<RichText
							tagName="h2"
							value={ titel }
							onChange={ ( value ) => setAttributes( { titel: value } ) }
							allowedFormats={ [] }
						/>
					</div>
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
