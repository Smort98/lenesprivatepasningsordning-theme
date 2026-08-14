import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	TextareaControl,
	Button,
	ButtonGroup,
	SelectControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

const UGEDAGE = [
	{ label: 'Man', value: 1 },
	{ label: 'Tir', value: 2 },
	{ label: 'Ons', value: 3 },
	{ label: 'Tor', value: 4 },
	{ label: 'Fre', value: 5 },
	{ label: 'Lør', value: 6 },
	{ label: 'Søn', value: 7 },
];

export default function Edit( { attributes, setAttributes } ) {
	const { titel, eyebrow, dage, visStatus, note, baggrund } = attributes;
	const blockProps = useBlockProps();

	const opdaterRaekke = ( index, felter ) => {
		const nyeDage = dage.map( ( d, i ) => ( i === index ? { ...d, ...felter } : d ) );
		setAttributes( { dage: nyeDage } );
	};

	const toggleUgedag = ( index, dagNummer ) => {
		const raekke = dage[ index ];
		const ugedage = raekke.ugedage || [];
		const nye = ugedage.includes( dagNummer )
			? ugedage.filter( ( d ) => d !== dagNummer )
			: [ ...ugedage, dagNummer ].sort();
		opdaterRaekke( index, { ugedage: nye } );
	};

	const tilfoejRaekke = () => {
		setAttributes( {
			dage: [ ...dage, { etiket: '', ugedage: [], aabner: '06:45', lukker: '16:00' } ],
		} );
	};

	const fjernRaekke = ( index ) => {
		setAttributes( { dage: dage.filter( ( _, i ) => i !== index ) } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tekst', 'lene' ) }>
					<TextControl label={ __( 'Overrubrik', 'lene' ) } value={ eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } />
					<TextControl label={ __( 'Titel', 'lene' ) } value={ titel } onChange={ ( v ) => setAttributes( { titel: v } ) } />
					<ToggleControl
						label={ __( 'Vis "Åbent nu"-mærkat', 'lene' ) }
						checked={ visStatus }
						onChange={ ( v ) => setAttributes( { visStatus: v } ) }
					/>
					<TextareaControl label={ __( 'Note', 'lene' ) } value={ note } onChange={ ( v ) => setAttributes( { note: v } ) } />
					<SelectControl
						label={ __( 'Baggrund', 'lene' ) }
						value={ baggrund }
						options={ [
							{ label: __( 'Dis (grågrøn)', 'lene' ), value: 'sky' },
							{ label: __( 'Hvid', 'lene' ), value: 'paper' },
						] }
						onChange={ ( v ) => setAttributes( { baggrund: v } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Åbningstider', 'lene' ) } initialOpen={ true }>
					{ dage.map( ( dag, index ) => (
						// eslint-disable-next-line react/no-array-index-key
						<div key={ index } style={ { marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #ddd' } }>
							<TextControl
								label={ __( 'Etiket', 'lene' ) }
								value={ dag.etiket }
								onChange={ ( v ) => opdaterRaekke( index, { etiket: v } ) }
								placeholder={ __( 'Fx "Mandag – torsdag"', 'lene' ) }
							/>
							<p style={ { marginBottom: '6px', fontWeight: 600, fontSize: '11px', textTransform: 'uppercase' } }>
								{ __( 'Gælder', 'lene' ) }
							</p>
							<ButtonGroup>
								{ UGEDAGE.map( ( u ) => (
									<Button
										key={ u.value }
										variant={ ( dag.ugedage || [] ).includes( u.value ) ? 'primary' : 'secondary' }
										size="small"
										onClick={ () => toggleUgedag( index, u.value ) }
									>
										{ u.label }
									</Button>
								) ) }
							</ButtonGroup>
							<TextControl
								label={ __( 'Åbner (tt:mm)', 'lene' ) }
								type="time"
								value={ dag.aabner }
								onChange={ ( v ) => opdaterRaekke( index, { aabner: v } ) }
							/>
							<TextControl
								label={ __( 'Lukker (tt:mm)', 'lene' ) }
								type="time"
								value={ dag.lukker }
								onChange={ ( v ) => opdaterRaekke( index, { lukker: v } ) }
							/>
							<Button variant="link" isDestructive onClick={ () => fjernRaekke( index ) }>
								{ __( 'Fjern denne række', 'lene' ) }
							</Button>
						</div>
					) ) }
					<Button variant="secondary" onClick={ tilfoejRaekke }>
						{ __( '+ Tilføj række', 'lene' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block="lene/aabningstider" attributes={ attributes } />
			</div>
		</>
	);
}
