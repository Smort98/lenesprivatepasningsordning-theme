<?php
/**
 * Lokal SEO — strukturerede data (schema.org) som JSON-LD i <head>.
 * Bruger de samme datakilder som resten af sitet (kontaktoplysninger,
 * åbningstider), så der ikke er noget at holde i sync to steder.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Splitter "Linde Allé 69, 5750 Ringe" op i gade, postnummer og by.
 * Falder tilbage til at lægge hele strengen i streetAddress hvis
 * formatet ikke matcher, så vi aldrig printer forkerte data.
 */
function lene_schema_split_adresse( string $adresse ): array {
	$dele = array_map( 'trim', explode( ',', $adresse, 2 ) );
	$gade = $dele[0] ?? '';
	$rest = $dele[1] ?? '';

	if ( $rest && preg_match( '/^(\d{4})\s+(.+)$/u', $rest, $m ) ) {
		return array(
			'gade'       => $gade,
			'postnummer' => $m[1],
			'by'         => $m[2],
		);
	}

	return array(
		'gade'       => $adresse,
		'postnummer' => '',
		'by'         => '',
	);
}

function lene_schema_ugedag_navn( int $iso_dag ): string {
	$navne = array(
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		7 => 'Sunday',
	);
	return $navne[ $iso_dag ] ?? 'Monday';
}

function lene_schema_output(): void {
	$kontakt = lene_hent_kontaktoplysninger();
	$adresse = lene_schema_split_adresse( $kontakt['adresse'] );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'ChildCare',
		'name'        => get_bloginfo( 'name' ),
		'url'         => home_url( '/' ),
		'telephone'   => $kontakt['telefon'],
		'email'       => $kontakt['email'],
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $adresse['gade'],
			'postalCode'      => $adresse['postnummer'],
			'addressLocality' => $adresse['by'],
			'addressCountry'  => 'DK',
		),
	);

	$site_icon = get_site_icon_url();
	if ( $site_icon ) {
		$schema['image'] = $site_icon;
		$schema['logo']  = $site_icon;
	}

	$aabningstider = lene_hent_aabningstider_data();
	$specs         = array();
	foreach ( $aabningstider['dage'] as $dag ) {
		$ugedage = is_array( $dag['ugedage'] ?? null ) ? $dag['ugedage'] : array();
		if ( empty( $ugedage ) || empty( $dag['aabner'] ) || empty( $dag['lukker'] ) ) {
			continue;
		}
		$specs[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array_map( 'lene_schema_ugedag_navn', array_map( 'intval', $ugedage ) ),
			'opens'     => $dag['aabner'],
			'closes'    => $dag['lukker'],
		);
	}
	if ( ! empty( $specs ) ) {
		$schema['openingHoursSpecification'] = $specs;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'lene_schema_output' );
