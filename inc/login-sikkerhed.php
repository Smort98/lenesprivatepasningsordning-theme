<?php
/**
 * Simpelt IP-baseret login-loft. Sitet har ingen sikkerheds-plugin
 * (Wordfence, Limit Login Attempts o.l.) til at bremse gæt af
 * adgangskoder, og hverken wp_signon() eller wp-login.php har nogen
 * indbygget throttling. Koblet på selve 'authenticate'-filterkæden,
 * så det dækker BÅDE det almindelige wp-login.php og /app/'s eget
 * loginformular ens — begge ender i sidste ende i wp_authenticate().
 */

defined( 'ABSPATH' ) || exit;

const LENE_LOGIN_MAKS_FORSOEG  = 5;
const LENE_LOGIN_LAAS_MINUTTER = 10;

function lene_login_transient_noegle(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	return 'lene_login_' . md5( $ip );
}

function lene_login_laast_ude(): bool {
	return (int) get_transient( lene_login_transient_noegle() ) >= LENE_LOGIN_MAKS_FORSOEG;
}

function lene_login_registrer_fejlforsoeg(): void {
	$noegle  = lene_login_transient_noegle();
	$forsoeg = (int) get_transient( $noegle );
	set_transient( $noegle, $forsoeg + 1, LENE_LOGIN_LAAS_MINUTTER * MINUTE_IN_SECONDS );
}
add_action( 'wp_login_failed', 'lene_login_registrer_fejlforsoeg' );

function lene_login_nulstil_fejlforsoeg(): void {
	delete_transient( lene_login_transient_noegle() );
}
add_action( 'wp_login', 'lene_login_nulstil_fejlforsoeg' );

/**
 * Kører EFTER kernens egen wp_authenticate_username_password (prioritet
 * 20), så den altid kan overskrive resultatet med en fejl, uanset om
 * adgangskoden faktisk var korrekt — det er selve pointen med et loft.
 */
function lene_login_tjek_laas( $bruger, $brugernavn, $adgangskode ) {
	if ( '' === (string) $brugernavn && '' === (string) $adgangskode ) {
		return $bruger; // Bare et sidebesøg, ikke et reelt loginforsøg.
	}
	if ( lene_login_laast_ude() ) {
		return new WP_Error( 'lene_for_mange_forsoeg', 'For mange forkerte login-forsøg. Prøv igen om lidt.' );
	}
	return $bruger;
}
add_filter( 'authenticate', 'lene_login_tjek_laas', 30, 3 );
