<?php
/**
 * Indsendelses-handler for lene/pladsalarm — "få besked når der bliver
 * en ledig plads". Samme honeypot+nonce-mønster som de andre formularer.
 */

defined( 'ABSPATH' ) || exit;

function lene_pladsalarm_handle_indsendelse() {
	$tilbage_url = wp_get_referer() ?: home_url( '/' );

	if ( ! isset( $_POST['lene_pladsalarm_nonce'] ) || ! wp_verify_nonce( $_POST['lene_pladsalarm_nonce'], 'lene_pladsalarm_indsend' ) ) {
		wp_safe_redirect( add_query_arg( 'pladsalarm', 'fejl', $tilbage_url ) );
		exit;
	}

	// Honeypot: skal være tomt. Er det udfyldt, er det en bot — lad som ingenting.
	if ( ! empty( $_POST['lene_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'pladsalarm', 'tak', $tilbage_url ) );
		exit;
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( '' === $email || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'pladsalarm', 'fejl', $tilbage_url ) );
		exit;
	}

	// Findes allerede en aktiv tilmelding med samme e-mail? Opret ikke en ekstra.
	$eksisterende = get_posts(
		array(
			'post_type'      => 'pladsalarm',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array( 'key' => 'email', 'value' => $email ),
				array( 'key' => 'status', 'value' => 'sendt', 'compare' => '!=' ),
			),
		)
	);

	if ( empty( $eksisterende ) ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'pladsalarm',
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s — %s', $email, current_time( 'd-m-Y H:i' ) ),
			)
		);
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'email', $email );
			update_post_meta( $post_id, 'status', 'aktiv' );
		}
	}

	wp_safe_redirect( add_query_arg( 'pladsalarm', 'tak', $tilbage_url ) );
	exit;
}
add_action( 'admin_post_lene_pladsalarm', 'lene_pladsalarm_handle_indsendelse' );
add_action( 'admin_post_nopriv_lene_pladsalarm', 'lene_pladsalarm_handle_indsendelse' );
