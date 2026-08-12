import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	MediaPlaceholder,
	MediaUploadCheck,
	MediaUpload,
} from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { billede, titel, tekst } = attributes;
	const blockProps = useBlockProps( { className: 'card' } );

	return (
		<article { ...blockProps }>
			<div className="card__img">
				{ billede?.url ? (
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( { billede: { id: media.id, url: media.url, alt: media.alt || '' } } )
							}
							allowedTypes={ [ 'image' ] }
							value={ billede.id }
							render={ ( { open } ) => (
								<img
									src={ billede.url }
									alt={ billede.alt || '' }
									onClick={ open }
									style={ { cursor: 'pointer' } }
								/>
							) }
						/>
					</MediaUploadCheck>
				) : (
					<MediaPlaceholder
						icon="format-image"
						labels={ { title: __( 'Billede', 'lene' ) } }
						onSelect={ ( media ) =>
							setAttributes( { billede: { id: media.id, url: media.url, alt: media.alt || '' } } )
						}
						accept="image/*"
						allowedTypes={ [ 'image' ] }
					/>
				) }
			</div>
			<div className="card__body">
				<RichText
					tagName="h3"
					value={ titel }
					onChange={ ( value ) => setAttributes( { titel: value } ) }
					placeholder={ __( 'Titel', 'lene' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					value={ tekst }
					onChange={ ( value ) => setAttributes( { tekst: value } ) }
					placeholder={ __( 'Kort tekst…', 'lene' ) }
				/>
			</div>
		</article>
	);
}
