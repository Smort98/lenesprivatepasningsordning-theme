import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { spoergsmaal, svar, aabenSomStandard } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Spørgsmål', 'lene' ) }>
					<ToggleControl
						label={ __( 'Åben som standard', 'lene' ) }
						checked={ aabenSomStandard }
						onChange={ ( v ) => setAttributes( { aabenSomStandard: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<details { ...blockProps } open={ aabenSomStandard }>
				<RichText
					tagName="summary"
					value={ spoergsmaal }
					onChange={ ( v ) => setAttributes( { spoergsmaal: v } ) }
					placeholder={ __( 'Skriv spørgsmålet…', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					value={ svar }
					onChange={ ( v ) => setAttributes( { svar: v } ) }
					placeholder={ __( 'Skriv svaret…', 'lene' ) }
				/>
			</details>
		</>
	);
}
