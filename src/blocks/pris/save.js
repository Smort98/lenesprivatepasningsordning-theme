import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, totalTekst, totalBeloeb, medSmaat, baggrund } = attributes;
	const blockProps = useBlockProps.save( { className: `section section--${ baggrund === 'paper' ? 'paper' : 'sky' }` } );
	const { children, ...innerBlocksProps } = useInnerBlocksProps.save( { className: 'receipt' } );

	return (
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
	);
}
