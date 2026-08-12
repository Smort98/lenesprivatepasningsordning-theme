import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { spoergsmaal, svar, aabenSomStandard } = attributes;
	const blockProps = useBlockProps.save();

	return (
		<details { ...blockProps } open={ aabenSomStandard }>
			<RichText.Content tagName="summary" value={ spoergsmaal } />
			<RichText.Content tagName="p" value={ svar } />
		</details>
	);
}
