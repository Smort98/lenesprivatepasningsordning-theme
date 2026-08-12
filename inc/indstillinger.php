<?php
/**
 * Én indstillingsside med kontaktoplysninger — deles af lene/kontaktkort
 * og footeren, så telefonnummer m.m. kun skal rettes ét sted.
 */

defined( 'ABSPATH' ) || exit;

const LENE_KONTAKT_OPTION = 'lene_kontaktoplysninger';

function lene_kontakt_standardvaerdier(): array {
	return array(
		'navn'    => 'Lene M. Aksgaard',
		'adresse' => 'Linde Allé 69, 5750 Ringe',
		'telefon' => '+45 28 74 84 56',
		'email'   => 'lene.koebke@hotmail.com',
	);
}

function lene_hent_kontaktoplysninger(): array {
	$gemt = get_option( LENE_KONTAKT_OPTION, array() );
	return wp_parse_args( is_array( $gemt ) ? $gemt : array(), lene_kontakt_standardvaerdier() );
}

function lene_registrer_indstillinger() {
	register_setting(
		'lene_indstillinger',
		LENE_KONTAKT_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lene_sanitize_kontaktoplysninger',
			'default'           => lene_kontakt_standardvaerdier(),
		)
	);

	add_settings_section(
		'lene_kontakt_sektion',
		'Kontaktoplysninger',
		function () {
			echo '<p>Bruges i kontaktkortet og i footeren på alle sider — ret dem her ét sted.</p>';
		},
		'lene-indstillinger'
	);

	$felter = array(
		'navn'    => 'Navn',
		'adresse' => 'Adresse',
		'telefon' => 'Telefon',
		'email'   => 'E-mail',
	);
	foreach ( $felter as $key => $label ) {
		add_settings_field(
			'lene_kontakt_' . $key,
			$label,
			function () use ( $key ) {
				$values = lene_hent_kontaktoplysninger();
				printf(
					'<input type="text" class="regular-text" name="%s[%s]" value="%s">',
					esc_attr( LENE_KONTAKT_OPTION ),
					esc_attr( $key ),
					esc_attr( $values[ $key ] )
				);
			},
			'lene-indstillinger',
			'lene_kontakt_sektion'
		);
	}
}
add_action( 'admin_init', 'lene_registrer_indstillinger' );

function lene_sanitize_kontaktoplysninger( $input ): array {
	$output = lene_kontakt_standardvaerdier();
	foreach ( $output as $key => $default ) {
		if ( isset( $input[ $key ] ) ) {
			$output[ $key ] = sanitize_text_field( $input[ $key ] );
		}
	}
	return $output;
}

function lene_indstillinger_menu() {
	add_options_page(
		'Kontaktoplysninger',
		'Kontaktoplysninger',
		'manage_options',
		'lene-indstillinger',
		'lene_render_indstillinger_side'
	);
}
add_action( 'admin_menu', 'lene_indstillinger_menu' );

function lene_render_indstillinger_side() {
	?>
	<div class="wrap">
		<h1>Kontaktoplysninger</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'lene_indstillinger' );
			do_settings_sections( 'lene-indstillinger' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
