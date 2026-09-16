import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Bevidst uden useBlockProps.save() / noget wrapper-element — kun de
 * indre blokke gemmes. render.php lægger selv <a class="..."> udenom
 * ved render, så $content her bliver præcis det, der skal stå inde i
 * <a>-tagget, uden et overflødigt ekstra lag der ville ødelægge
 * .brand's flex-layout (logo + navn skal være direkte flex-børn).
 */
export default function save() {
	return <InnerBlocks.Content />;
}
