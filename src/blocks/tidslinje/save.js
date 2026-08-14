import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, baggrund } = attributes;
	const blockProps = useBlockProps.save( { className: `section section--${ baggrund === 'sky' ? 'sky' : 'paper' }` } );
	const innerBlocksProps = useInnerBlocksProps.save( { className: 'timeline' } );

	return (
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
	);
}
