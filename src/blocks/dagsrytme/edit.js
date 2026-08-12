import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, RichText } from '@wordpress/block-editor';

const TEMPLATE = [
	[ 'lene/dagsrytme-punkt', { tidspunkt: '6.45', titel: 'Godmorgen', tekst: 'Vi spiser morgenmad frem til 7.30 og starter dagen stille og roligt.' } ],
	[ 'lene/dagsrytme-punkt', { tidspunkt: '7.30', titel: 'Leg indenfor', tekst: 'Bøger, sanglege og gymnastik. Tid til nærvær og til at øve motorikken.' } ],
	[ 'lene/dagsrytme-punkt', { tidspunkt: '9.00', titel: 'Frugt og sang', tekst: 'Vi spiser frugt sammen og synger, inden vi kommer i tøjet.' } ],
	[ 'lene/dagsrytme-punkt', { tidspunkt: '9.30', titel: 'Ud af døren', tekst: 'Haven, legepladsen, ænderne ved søen — eller besøg hos de andre private passere.' } ],
	[ 'lene/dagsrytme-punkt', { tidspunkt: '11.00', titel: 'Frokost', tekst: 'Rugbrød, pålæg, fisk, ost, grønt eller en lun ret. Sundt og varieret.' } ],
	[ 'lene/dagsrytme-punkt', { tidspunkt: '12.00', titel: 'Middagslur', tekst: 'Når der er sovet, er der eftermiddagsmad, og så leges der videre inde eller ude.' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { eyebrow, titel, lede } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'rhythm' },
		{ allowedBlocks: [ 'lene/dagsrytme-punkt' ], template: TEMPLATE, templateInsertUpdatesSelection: false }
	);

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<div className="section__head">
					<div>
						<RichText
							tagName="p"
							className="eyebrow"
							value={ eyebrow }
							onChange={ ( value ) => setAttributes( { eyebrow: value } ) }
							allowedFormats={ [] }
						/>
						<RichText
							tagName="h2"
							value={ titel }
							onChange={ ( value ) => setAttributes( { titel: value } ) }
							allowedFormats={ [] }
						/>
					</div>
					<RichText
						tagName="p"
						className="lede"
						style={ { maxWidth: '38ch' } }
						value={ lede }
						onChange={ ( value ) => setAttributes( { lede: value } ) }
						placeholder={ __( 'Kort intro…', 'lene' ) }
					/>
				</div>
				<ul { ...innerBlocksProps } />
			</div>
		</section>
	);
}
