import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[ 'lene/tidslinje-punkt', { aar: 'Efter 2006', titel: 'Grunduddannelse for dagplejere', tekst: 'Gennemført grunduddannelsen for dagplejere.' } ],
	[ 'lene/tidslinje-punkt', { aar: '2013', titel: 'Privat passer', tekst: 'Startede som privat passer i Ringe.' } ],
	[ 'lene/tidslinje-punkt', { aar: '2017', titel: 'Forældresamarbejde', tekst: 'Kursus i forældresamarbejde.' } ],
	[ 'lene/tidslinje-punkt', { aar: '2018', titel: 'Børns leg med tvist af natur', tekst: 'Kursus om børns leg og natur.' } ],
	[ 'lene/tidslinje-punkt', { aar: '2019', titel: 'Den styrkede pædagogiske læreplan', tekst: 'Kursus i den styrkede pædagogiske læreplan.' } ],
	[ 'lene/tidslinje-punkt', { aar: '2019', titel: 'Brede læringsmål og evaluering', tekst: 'Kursus i brede læringsmål og evaluering af læringsmiljøet.' } ],
	[ 'lene/tidslinje-punkt', { aar: 'Hvert andet år', titel: 'Førstehjælpskursus for børn', tekst: 'Løbende genopfrisker jeg førstehjælp til børn.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'timeline' },
		{ allowedBlocks: [ 'lene/tidslinje-punkt' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tidslinje', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
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
					<ul { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
