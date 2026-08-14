import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, kolonner, baggrund } = attributes;
	const blockProps = useBlockProps.save( { className: `section section--${ baggrund === 'paper' ? 'paper' : 'sky' }` } );
	const innerBlocksProps = useInnerBlocksProps.save( { className: 'cards', style: { '--kolonner': kolonner } } );

	return (
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
	);
}
