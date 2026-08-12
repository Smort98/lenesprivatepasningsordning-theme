import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<ServerSideRender block="lene/pladschip" attributes={ attributes } />
		</div>
	);
}
