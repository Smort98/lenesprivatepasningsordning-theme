import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, ToggleControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { eyebrow, titel, kolonner, lightbox } = attributes;
	const blockProps = useBlockProps( { className: 'section' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'galleri', style: { '--kolonner': String( kolonner ) } },
		{
			allowedBlocks: [ 'core/image' ],
			template: [],
			templateInsertUpdatesSelection: false,
		}
	);

	const billedIder = useSelect(
		( select ) => {
			const blocks = select( 'core/block-editor' ).getBlocks( clientId );
			return blocks.map( ( b ) => b.attributes.id ).filter( Boolean );
		},
		[ clientId ]
	);

	const umarkerede = useSelect(
		( select ) => {
			return billedIder.filter( ( id ) => {
				const media = select( 'core' ).getMedia( id );
				return media && ! media.meta?.samtykke_noteret;
			} );
		},
		[ billedIder ]
	);

	const harTjekketAlle = useMemo(
		() => billedIder.length > 0 && umarkerede.length === 0,
		[ billedIder, umarkerede ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Fotoalbum', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<RangeControl label={ __( 'Kolonner', 'lene' ) } value={ kolonner } onChange={ ( v ) => setAttributes( { kolonner: v } ) } min={ 2 } max={ 4 } />
					<ToggleControl label={ __( 'Lightbox ved klik', 'lene' ) } checked={ lightbox } onChange={ ( v ) => setAttributes( { lightbox: v } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="wrap">
					{ ( eyebrow || titel ) && (
						<div className="section__head">
							<div>
								{ eyebrow && <p className="eyebrow">{ eyebrow }</p> }
								{ titel && <h2>{ titel }</h2> }
							</div>
						</div>
					) }
					{ umarkerede.length > 0 && (
						<Notice status="warning" isDismissible={ false } style={ { marginBottom: '16px' } }>
							{ sprintf1(
								umarkerede.length,
								'1 billede mangler forældresamtykke-markering. Ret det under Medier → vælg billedet → "Forældresamtykke".',
								'%d billeder mangler forældresamtykke-markering. Ret det under Medier → vælg billedet → "Forældresamtykke".'
							) }
						</Notice>
					) }
					{ harTjekketAlle && (
						<Notice status="success" isDismissible={ false } style={ { marginBottom: '16px' } }>
							{ __( 'Alle billeder i dette galleri er markeret med samtykke.', 'lene' ) }
						</Notice>
					) }
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}

function sprintf1( count, singular, plural ) {
	const template = 1 === count ? singular : plural;
	return template.replace( '%d', String( count ) );
}
