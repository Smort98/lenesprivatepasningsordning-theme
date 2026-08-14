import { useBlockProps, useInnerBlocksProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { eyebrow, titel, lede, baggrund } = attributes;
	const blockProps = useBlockProps.save( { className: `section section--${ baggrund === 'sky' ? 'sky' : 'paper' }` } );
	const innerBlocksProps = useInnerBlocksProps.save( { className: 'rhythm' } );

	return (
		<section { ...blockProps }>
			<div className="wrap">
				<div className="section__head">
					<div>
						<RichText.Content tagName="p" className="eyebrow" value={ eyebrow } />
						<RichText.Content tagName="h2" value={ titel } />
					</div>
					<RichText.Content tagName="p" className="lede" style={ { maxWidth: '38ch' } } value={ lede } />
				</div>
				<ul { ...innerBlocksProps } />
			</div>
		</section>
	);
}
