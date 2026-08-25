import { useBlockProps, useInnerBlocksProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { titel, kolonner, baggrund } = attributes;
	const blockProps = useBlockProps.save( { className: `section section--${ baggrund === 'sky' ? 'sky' : 'paper' }` } );
	const innerBlocksProps = useInnerBlocksProps.save( { className: 'cards', style: { '--kolonner': String( kolonner ) } } );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<div className="section__head">
					<RichText.Content tagName="h2" value={ titel } />
				</div>
				<div { ...innerBlocksProps } />
			</div>
		</section>
	);
}
