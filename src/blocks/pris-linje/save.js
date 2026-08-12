import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { tekst, beloeb, erMinus } = attributes;
	const blockProps = useBlockProps.save( { className: 'receipt__row' } );

	return (
		<div { ...blockProps }>
			<RichText.Content tagName="span" className="receipt__label" value={ tekst } />
			<span className="receipt__fill" />
			<RichText.Content
				tagName="span"
				className={ `receipt__amount${ erMinus ? ' is-minus' : '' }` }
				value={ beloeb }
			/>
		</div>
	);
}
