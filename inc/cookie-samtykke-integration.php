<?php
/**
 * Kobler dette sites personoplysninger til Cookie-samtykke-pluginets
 * selvbetjente data-anmodninger (indsigt og sletning). Pluginet ved ikke
 * selv, hvor et sites data ligger — det er derfor overladt til temaet at
 * fortælle det, hvilke CPT'er der skal læses fra/ryddes op i, når en
 * bruger bekræfter en anmodning for sin e-mail.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Indsigt: returnerer alt, vi har registreret på $email, til den
 * automatisk genererede tekstfil brugeren downloader.
 */
function lene_hent_bruger_data( array $poster, string $email ): array {
	if ( ! is_email( $email ) ) {
		return $poster;
	}

	$henvendelser = get_posts(
		array(
			'post_type'      => 'henvendelse',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => 'email', 'value' => $email ),
			),
		)
	);
	foreach ( $henvendelser as $h ) {
		$poster[] = array(
			'titel'  => 'Henvendelse (' . lene_henvendelse_kilde_label( get_post_meta( $h->ID, 'kilde', true ) ) . ') — ' . get_the_date( 'j. F Y', $h ),
			'felter' => array(
				'Navn'                 => get_post_meta( $h->ID, 'navn', true ),
				'E-mail'               => get_post_meta( $h->ID, 'email', true ),
				'Telefon'              => get_post_meta( $h->ID, 'telefon', true ),
				'Barnets fødselsdato'  => get_post_meta( $h->ID, 'barnets_foedselsdato', true ),
				'Ønsket startdato'     => get_post_meta( $h->ID, 'oensket_start', true ),
				'Besked'               => get_post_meta( $h->ID, 'besked', true ),
			),
		);
	}

	$alarmer = get_posts(
		array(
			'post_type'      => 'pladsalarm',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => 'email', 'value' => $email ),
			),
		)
	);
	foreach ( $alarmer as $a ) {
		$poster[] = array(
			'titel'  => 'Pladsalarm (besked ved ledig plads) — ' . get_the_date( 'j. F Y', $a ),
			'felter' => array(
				'E-mail'  => get_post_meta( $a->ID, 'email', true ),
				'Status'  => 'sendt' === get_post_meta( $a->ID, 'status', true ) ? 'Besked sendt' : 'Afventer',
			),
		);
	}

	return $poster;
}
add_filter( 'cookie_samtykke_hent_bruger_data', 'lene_hent_bruger_data', 10, 2 );

/**
 * Sletning: rydder alt, vi har registreret på $email.
 */
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
