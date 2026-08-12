<?php
/**
 * Delt indsendelses-handler for lene/kontaktkort og lene/tilmelding.
 *
 * Honeypot + nonce, ingen reCAPTCHA (tredjepartskald og cookiesamtykke
 * er ikke besværet værd for et par henvendelser om måneden). Gemmer
 * altid som CPT `henvendelse` OG sender mail, så intet går tabt hvis
 * mailen fejler.
 */

defined( 'ABSPATH' ) || exit;

function lene_formular_handle_indsendelse() {
	$tilbage_url = wp_get_referer() ?: home_url( '/' );

	if ( ! isset( $_POST['lene_formular_nonce'] ) || ! wp_verify_nonce( $_POST['lene_formular_nonce'], 'lene_formular_indsend' ) ) {
		wp_safe_redirect( add_query_arg( 'henvendelse', 'fejl', $tilbage_url ) );
		exit;
	}

	// Honeypot: skal være tomt. Er det udfyldt, er det en bot — lad som ingenting.
	if ( ! empty( $_POST['lene_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'henvendelse', 'tak', $tilbage_url ) );
		exit;
	}

	$felter = array(
		'kilde'                => isset( $_POST['kilde'] ) ? sanitize_key( $_POST['kilde'] ) : 'kontakt',
		'navn'                 => isset( $_POST['navn'] ) ? sanitize_text_field( wp_unslash( $_POST['navn'] ) ) : '',
		'email'                => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'telefon'              => isset( $_POST['telefon'] ) ? sanitize_text_field( wp_unslash( $_POST['telefon'] ) ) : '',
		'barnets_foedselsdato' => isset( $_POST['barnets_foedselsdato'] ) ? sanitize_text_field( wp_unslash( $_POST['barnets_foedselsdato'] ) ) : '',
		'oensket_start'        => isset( $_POST['oensket_start'] ) ? sanitize_text_field( wp_unslash( $_POST['oensket_start'] ) ) : '',
		'plads_id'             => isset( $_POST['plads_id'] ) ? (int) $_POST['plads_id'] : 0,
		'besked'               => isset( $_POST['besked'] ) ? sanitize_textarea_field( wp_unslash( $_POST['besked'] ) ) : '',
	);

	if ( '' === $felter['navn'] || '' === $felter['email'] ) {
		wp_safe_redirect( add_query_arg( 'henvendelse', 'fejl', $tilbage_url ) );
		exit;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'henvendelse',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%s — %s', $felter['navn'], current_time( 'd-m-Y H:i' ) ),
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( $felter as $key => $value ) {
			if ( '' !== $value && 0 !== $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	$kontakt   = lene_hent_kontaktoplysninger();
	$modtager  = get_option( 'admin_email' );
	$emne      = 'kontakt' === $felter['kilde'] ? 'Ny besked fra kontaktformularen' : 'Ny tilmelding til venteliste';
	$krop      = "Navn: {$felter['navn']}\nE-mail: {$felter['email']}\n";
	if ( $felter['telefon'] ) {
		$krop .= "Telefon: {$felter['telefon']}\n";
	}
	if ( $felter['barnets_foedselsdato'] ) {
		$krop .= "Barnets fødselsdato: {$felter['barnets_foedselsdato']}\n";
	}
	if ( $felter['oensket_start'] ) {
		$krop .= "Ønsket startdato: {$felter['oensket_start']}\n";
	}
	if ( $felter['besked'] ) {
		$krop .= "\nBesked:\n{$felter['besked']}\n";
	}
	$krop .= "\n—\nSendt fra " . home_url( '/' );

	wp_mail( $modtager, $emne, $krop, array( 'Reply-To: ' . $felter['navn'] . ' <' . $felter['email'] . '>' ) );

	wp_safe_redirect( add_query_arg( 'henvendelse', 'tak', $tilbage_url ) );
	exit;
}
add_action( 'admin_post_lene_henvendelse', 'lene_formular_handle_indsendelse' );
add_action( 'admin_post_nopriv_lene_henvendelse', 'lene_formular_handle_indsendelse' );
