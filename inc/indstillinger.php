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

/**
 * Site Admin UI-kittets farve-tokens, sat ud fra temaets aktive
 * farvepalet i stedet for faste hex-koder — skifter man farvetema i
 * Stilarter, følger indstillingssiden automatisk med.
 */
function lene_admin_sadmin_farver(): array {
	$farver = lene_app_hent_farver();
	return array(
		'--sadmin-header-bg'     => $farver['pine'],
		'--sadmin-header-text'   => $farver['sun'],
		'--sadmin-header-muted'  => 'rgba(255,255,255,.62)',
		'--sadmin-accent'        => $farver['pine'],
		'--sadmin-accent-hover'  => 'color-mix(in srgb, ' . $farver['pine'] . ' 85%, black)',
		'--sadmin-accent-text'   => $farver['sun'],
		'--sadmin-cta-bg'        => $farver['sun'],
		'--sadmin-cta-bg-hover'  => $farver['sun-deep'],
		'--sadmin-cta-text'      => $farver['pine'],
		'--sadmin-bg'            => $farver['sky'],
		'--sadmin-card-bg'       => $farver['paper'],
		'--sadmin-border'        => 'color-mix(in srgb, ' . $farver['pine'] . ' 16%, transparent)',
		'--sadmin-text'          => $farver['pine'],
		'--sadmin-muted'         => $farver['stone'],
		'--sadmin-ok-text'       => '#16a34a',
		'--sadmin-ok-bg'         => '#dcfce7',
		'--sadmin-neutral-text'  => $farver['stone'],
		'--sadmin-neutral-bg'    => 'color-mix(in srgb, ' . $farver['stone'] . ' 12%, transparent)',
	);
}

function lene_admin_assets( string $hook ) {
	if ( 'settings_page_lene-indstillinger' !== $hook ) {
		return;
	}
	$version = wp_get_theme()->get( 'Version' ) ?: '1.0';
	wp_enqueue_style( 'lene-sadmin', get_theme_file_uri( 'assets/css/sadmin.css' ), array(), $version );

	$css = ':root{';
	foreach ( lene_admin_sadmin_farver() as $noegle => $vaerdi ) {
		$css .= esc_html( $noegle ) . ':' . esc_html( $vaerdi ) . ';';
	}
	$css .= '}';
	wp_add_inline_style( 'lene-sadmin', $css );
}
add_action( 'admin_enqueue_scripts', 'lene_admin_assets' );

function lene_render_indstillinger_side() {
	?>
	<div class="wrap sadmin">
		<div class="sadmin-header">
			<div>
				<p class="sadmin-header__eyebrow">Tema-indstilling</p>
				<h1>Kontaktoplysninger &amp; logo</h1>
				<p>Kontaktoplysninger bruges i kontaktkortet og footeren på alle sider. Logoet skifter automatisk farve efter det farvetema, der er valgt under Redigering → Design → Stilarter.</p>
			</div>
		</div>

		<div class="sadmin-card">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'lene_indstillinger' );
				do_settings_sections( 'lene-indstillinger' );
				submit_button( 'Gem indstillinger' );
				?>
			</form>
		</div>
	</div>
	<?php
}
