import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, kolonner, lightbox } = attributes;
	const blockProps = useBlockProps.save( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'galleri',
		style: { '--kolonner': String( kolonner ) },
		'data-lightbox': lightbox ? 'true' : 'false',
	} );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				{ ( eyebrow || titel ) && (
					<div className="section__head">
						<div>
							{ eyebrow && <p className="eyebrow">{ eyebrow }</p> }
							{ titel && <h2>{ titel }</h2> }
						</div>
					</div>
				) }
				<div { ...innerBlocksProps } />
			</div>
		</section>
	);
}
