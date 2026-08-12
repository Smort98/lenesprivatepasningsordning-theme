import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, visPrintknap } = attributes;
	const blockProps = useBlockProps.save( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps.save( { className: 'tjekliste' } );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<div className="section__head">
					<div>
						<p className="eyebrow">{ eyebrow }</p>
						<h2>{ titel }</h2>
					</div>
					{ visPrintknap && (
						<button type="button" className="btn btn--ghost tjekliste__print no-print">
							Print listen
						</button>
					) }
				</div>
				<ul { ...innerBlocksProps } />
			</div>
		</section>
	);
}
