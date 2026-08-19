<?php
/**
 * Dedikeret rolle og capability til PWA-adgang (/app/), adskilt fra
 * kernens generelle "edit_posts" — en bruger, der kun skal bruge appen,
 * skal ikke automatisk kunne redigere indlæg/sider i wp-admin, og en
 * fremtidig forfatter/redaktør-konto skal ikke automatisk få adgang
 * til pladser/priser/åbningstider.
 */

defined( 'ABSPATH' ) || exit;

const LENE_APP_CAP = 'lene_app_adgang';

function lene_app_registrer_rolle(): void {
	add_role(
		'lene_app_bruger',
		'PWA-bruger',
		array(
			'read'       => true,
			LENE_APP_CAP => true,
		)
	);

	$administrator = get_role( 'administrator' );
	if ( $administrator && ! $administrator->has_cap( LENE_APP_CAP ) ) {
		$administrator->add_cap( LENE_APP_CAP );
	}
}
add_action( 'after_switch_theme', 'lene_app_registrer_rolle' );

/**
 * after_switch_theme fyrer kun ved et faktisk temaskift — dette sikrer
 * at rollen/capability'en også oprettes, når koden opdateres på et
 * allerede aktivt tema, uden at kræve en manuel gen-aktivering.
 */
function lene_app_sikr_rolle_findes(): void {
	if ( '1' === get_option( 'lene_app_rolle_version' ) ) {
		return;
	}
	lene_app_registrer_rolle();
	update_option( 'lene_app_rolle_version', '1' );
}
add_action( 'init', 'lene_app_sikr_rolle_findes' );

/**
 * PWA-brugere har intet ærinde i det almindelige wp-admin-dashboard —
 * send dem direkte til appen i stedet. Mindre overflade at angribe,
 * og mindre forvirring for en ikke-teknisk bruger.
 */
function lene_app_omdiriger_pwa_bruger_fra_wp_admin(): void {
	if ( wp_doing_ajax() ) {
		return;
	}
	$bruger = wp_get_current_user();
	if ( ! $bruger->exists() || ! in_array( 'lene_app_bruger', (array) $bruger->roles, true ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/app/' ) );
	exit;
}
add_action( 'admin_init', 'lene_app_omdiriger_pwa_bruger_fra_wp_admin' );
