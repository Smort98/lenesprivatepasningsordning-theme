import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

const TEMPLATE = [
	[ 'lene/faq-item', { spoergsmaal: 'Hvordan kommer vi på venteliste?', svar: 'Skriv eller ring til Lene med barnets navn, fødselsdato og hvornår I gerne vil starte. Så noterer hun jer på listen og giver besked, hvis der bliver plads før tid.', aabenSomStandard: true } ],
	[ 'lene/faq-item', { spoergsmaal: 'Hvad koster en plads?', svar: 'Prisen aftales direkte, og du kan søge kommunalt tilskud til privat pasning. Lene hjælper gerne med at udfylde ansøgningen.' } ],
	[ 'lene/faq-item', { spoergsmaal: 'Kan vi komme på besøg først?', svar: 'Ja, og det anbefales. Kom gerne en formiddag, hvor børnene er der, så I kan se hverdagen, som den ser ud til daglig.' } ],
	[ 'lene/faq-item', { spoergsmaal: 'Hvad skal vi selv have med?', svar: 'Skiftetøj, ble og en sut eller bamse hvis barnet bruger det. Mad, regntøj og barnevognsplads sørger Lene for.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, baggrund } = attributes;
	const blockProps = useBlockProps( { className: `section section--${ baggrund === 'paper' ? 'paper' : 'sky' }` } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'faq' },
		{ allowedBlocks: [ 'lene/faq-item' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'FAQ', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
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
							{ eyebrow && <p className="eyebrow">{ eyebrow }</p> }
							<h2>{ titel }</h2>
						</div>
					</div>
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
