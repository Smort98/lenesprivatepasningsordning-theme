<?php
/**
 * CPT `pladsalarm` — e-mails der ønsker besked, når en plads bliver
 * ledig. Sendes automatisk én gang (se lene_pladsalarm_send_hvis_relevant
 * i cpt-plads.php), og markeres derefter som "sendt" så man ikke
 * spammes — vil man have besked igen, tilmelder man sig på ny.
 */

defined( 'ABSPATH' ) || exit;

function lene_register_cpt_pladsalarm() {
	register_post_type(
		'pladsalarm',
		array(
			'labels'             => array(
				'name'          => 'Pladsalarmer',
				'singular_name' => 'Pladsalarm',
				'all_items'     => 'Pladsalarmer (besked ved ledig plads)',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=plads',
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
		)
	);
}
add_action( 'init', 'lene_register_cpt_pladsalarm' );

function lene_pladsalarm_admin_columns( array $columns ): array {
	unset( $columns['title'] );
	$columns['email']   = 'E-mail';
	$columns['status']  = 'Status';
	$columns['dato']    = 'Tilmeldt';
	return $columns;
}
add_filter( 'manage_pladsalarm_posts_columns', 'lene_pladsalarm_admin_columns' );

function lene_pladsalarm_admin_column_content( string $column, int $post_id ): void {
	if ( 'email' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'email', true ) );
	} elseif ( 'status' === $column ) {
		$status = get_post_meta( $post_id, 'status', true ) ?: 'aktiv';
		echo esc_html( 'sendt' === $status ? 'Besked sendt' : 'Afventer' );
	} elseif ( 'dato' === $column ) {
		echo esc_html( get_the_date( 'j. F Y', $post_id ) );
	}
}
add_action( 'manage_pladsalarm_posts_custom_column', 'lene_pladsalarm_admin_column_content', 10, 2 );

/**
 * Alle aktive (endnu ikke underrettede) tilmeldinger.
 */
function lene_hent_aktive_pladsalarmer(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'pladsalarm',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'status',
					'value'   => 'sendt',
					'compare' => '!=',
				),
			),
		)
	);
	return $query->posts;
}

/**
 * Sender besked til alle aktive tilmeldinger om at der nu er en ledig
 * plads, og markerer dem som sendt bagefter (ét skud pr. tilmelding).
 */
function lene_pladsalarm_send_besked(): void {
	$alarmer = lene_hent_aktive_pladsalarmer();
	if ( empty( $alarmer ) ) {
		return;
	}

	$kontakt = lene_hent_kontaktoplysninger();
	$emne    = 'Der er blevet en plads ledig hos ' . get_bloginfo( 'name' );
	$link    = home_url( '/ledige-pladser/' );

	foreach ( $alarmer as $alarm ) {
		$email = get_post_meta( $alarm->ID, 'email', true );
		if ( ! $email ) {
			continue;
		}
		$krop = "Hej\n\nDer er lige blevet en plads ledig hos {$kontakt['navn']}.\n\nSe de ledige pladser og skriv dig på ventelisten her:\n{$link}\n\n—\n{$kontakt['navn']}\n{$kontakt['telefon']}\n{$kontakt['email']}";
		wp_mail( $email, $emne, $krop );
		update_post_meta( $alarm->ID, 'status', 'sendt' );
	}
}
