import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, SelectControl } from '@wordpress/components';

const TEMPLATE = [
	[ 'lene/pris-linje', { tekst: 'Fuldtidsplads pr. måned', beloeb: '9.100 kr.', erMinus: false } ],
	[ 'lene/pris-linje', { tekst: 'Tilskud via fritvalgsordningen', beloeb: '6.478 kr.', erMinus: true } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, totalTekst, totalBeloeb, medSmaat, baggrund } = attributes;
	const blockProps = useBlockProps( { className: `section section--${ baggrund === 'paper' ? 'paper' : 'sky' }` } );
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{ className: 'receipt' },
		{ allowedBlocks: [ 'lene/pris-linje' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tekst', 'lene' ) }>
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
				<PanelBody title={ __( 'Total', 'lene' ) }>
					<TextControl label={ __( 'Tekst', 'lene' ) } value={ totalTekst } onChange={ ( v ) => setAttributes( { totalTekst: v } ) } />
					<TextControl label={ __( 'Beløb', 'lene' ) } value={ totalBeloeb } onChange={ ( v ) => setAttributes( { totalBeloeb: v } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Med småt', 'lene' ) } initialOpen={ false }>
					<TextareaControl
						label={ __( 'Ét punkt pr. linje', 'lene' ) }
						value={ ( medSmaat || [] ).join( '\n' ) }
						onChange={ ( v ) => setAttributes( { medSmaat: v.split( '\n' ) } ) }
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
					<div { ...innerBlocksProps }>
						{ children }
						<div className="receipt__total">
							<span>{ totalTekst }</span>
							<b>{ totalBeloeb }</b>
						</div>
						<ul className="receipt__fine">
							{ ( medSmaat || [] ).map( ( punkt, i ) =>
								punkt ? (
									// eslint-disable-next-line react/no-array-index-key
									<li key={ i }>{ punkt }</li>
								) : null
							) }
						</ul>
					</div>
				</div>
			</section>
		</>
	);
}
