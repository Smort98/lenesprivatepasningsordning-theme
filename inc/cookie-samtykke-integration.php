<?php
/**
 * Kobler dette sites personoplysninger til Cookie-samtykke-pluginets
 * selvbetjente sletteanmodning. Pluginet ved ikke selv, hvor et sites
 * data ligger — det er derfor overladt til temaet at fortælle det,
 * hvilke CPT'er der skal ryddes op i, når en bruger bekræfter en
 * sletteanmodning for sin e-mail.
 */

defined( 'ABSPATH' ) || exit;

function lene_slet_bruger_data( string $email ): void {
	if ( ! is_email( $email ) ) {
		return;
	}

	foreach ( array( 'henvendelse', 'pladsalarm' ) as $post_type ) {
		$ider = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'email',
						'value' => $email,
					),
				),
			)
		);
		foreach ( $ider as $id ) {
			wp_delete_post( $id, true );
		}
	}
}
add_action( 'cookie_samtykke_slet_bruger_data', 'lene_slet_bruger_data' );
