import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Wrapperen ses kun i editoren (til at vise/vælge blokken) — selve
 * save() er "transparent" (ingen ekstra element), så .brand's flex-CSS
 * fortsat rammer logo+navn direkte, uden et ekstra lag imellem.
 */
export default function Edit() {
	const blockProps = useBlockProps( { className: 'brand' } );
	return (
		<div { ...blockProps }>
			<InnerBlocks />
		</div>
	);
}
