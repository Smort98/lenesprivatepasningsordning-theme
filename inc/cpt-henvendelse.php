<?php
/**
 * CPT `henvendelse` — gemmer indsendelser fra lene/kontaktkort og
 * lene/tilmelding, så intet går tabt hvis mailen fejler.
 */

defined( 'ABSPATH' ) || exit;

function lene_register_cpt_henvendelse() {
	register_post_type(
		'henvendelse',
		array(
			'labels'             => array(
				'name'          => 'Henvendelser',
				'singular_name' => 'Henvendelse',
				'all_items'     => 'Henvendelser',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // Samlet i "Drift" (inc/admin-drift.php) i stedet for eget topmenupunkt.
			'menu_icon'           => 'dashicons-email-alt',
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'lene_register_cpt_henvendelse' );

function lene_henvendelse_meta_box() {
	add_meta_box( 'lene_henvendelse_detaljer', 'Henvendelse', 'lene_render_henvendelse_meta_box', 'henvendelse', 'normal', 'high' );
}
add_action( 'add_meta_boxes_henvendelse', 'lene_henvendelse_meta_box' );

function lene_render_henvendelse_meta_box( WP_Post $post ) {
	$felter = array(
		'kilde'                  => 'Kilde',
		'navn'                   => 'Navn',
		'email'                  => 'E-mail',
		'telefon'                => 'Telefon',
		'barnets_foedselsdato'   => 'Barnets fødselsdato',
		'oensket_start'          => 'Ønsket startdato',
		'plads_id'               => 'Plads (ID)',
		'besked'                 => 'Besked',
	);
	echo '<table class="form-table">';
	foreach ( $felter as $key => $label ) {
		$value = get_post_meta( $post->ID, $key, true );
		if ( '' === $value ) {
			continue;
		}
		printf(
			'<tr><th style="width:200px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( $value ) )
		);
	}
	echo '</table>';
}

function lene_henvendelse_admin_columns( array $columns ): array {
	$columns['kilde'] = 'Kilde';
	$columns['navn']  = 'Navn';
	$columns['email'] = 'E-mail';
	return $columns;
}
add_filter( 'manage_henvendelse_posts_columns', 'lene_henvendelse_admin_columns' );

function lene_henvendelse_kilde_label( string $kilde ): string {
	$labels = array(
		'kontakt'    => 'Kontakt',
		'tilmelding' => 'Tilmelding',
	);
	return $labels[ $kilde ] ?? ( $kilde ?: '—' );
}

function lene_henvendelse_admin_column_content( string $column, int $post_id ): void {
	if ( 'kilde' === $column ) {
		echo esc_html( lene_henvendelse_kilde_label( get_post_meta( $post_id, 'kilde', true ) ) );
		return;
	}
	if ( in_array( $column, array( 'navn', 'email' ), true ) ) {
		echo esc_html( get_post_meta( $post_id, $column, true ) ?: '—' );
	}
}
add_action( 'manage_henvendelse_posts_custom_column', 'lene_henvendelse_admin_column_content', 10, 2 );

/**
 * CSV-eksport af henvendelser — knap øverst i listen ("alle") og som
 * bulk-handling ("kun de markerede"). Samme mønster begge steder: byg
 * en admin-post.php-URL med nonce, og lad lene_handle_export_henvendelser_csv
 * stå for selve CSV-strømmen.
 */
function lene_henvendelse_bulk_actions( array $actions ): array {
	$actions['lene_export_csv'] = 'Eksportér til CSV';
	return $actions;
}
add_filter( 'bulk_actions-edit-henvendelse', 'lene_henvendelse_bulk_actions' );

function lene_henvendelse_handle_bulk_actions( string $redirect_to, string $doaction, array $post_ids ): string {
	if ( 'lene_export_csv' !== $doaction ) {
		return $redirect_to;
	}
	$url = add_query_arg(
		array(
			'action' => 'lene_export_henvendelser_csv',
			'ids'    => implode( ',', array_map( 'intval', $post_ids ) ),
		),
		admin_url( 'admin-post.php' )
	);
	return wp_nonce_url( $url, 'lene_export_henvendelser_csv' );
}
add_filter( 'handle_bulk_actions-edit-henvendelse', 'lene_henvendelse_handle_bulk_actions', 10, 3 );

function lene_henvendelse_export_all_button( string $which ): void {
	if ( 'top' !== $which ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'henvendelse' !== $screen->post_type ) {
		return;
	}
	$url = wp_nonce_url(
		add_query_arg( array( 'action' => 'lene_export_henvendelser_csv' ), admin_url( 'admin-post.php' ) ),
		'lene_export_henvendelser_csv'
	);
	echo '<a href="' . esc_url( $url ) . '" class="button" style="margin-left:8px;">Eksportér alle til CSV</a>';
}
add_action( 'manage_posts_extra_tablenav', 'lene_henvendelse_export_all_button' );

function lene_handle_export_henvendelser_csv(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Du har ikke adgang til denne handling.' );
	}
	check_admin_referer( 'lene_export_henvendelser_csv' );

	$ids = isset( $_GET['ids'] ) ? array_filter( array_map( 'intval', explode( ',', wp_unslash( $_GET['ids'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	$query_args = array(
		'post_type'      => 'henvendelse',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( $ids ) {
		$query_args['post__in'] = $ids;
		$query_args['orderby']  = 'post__in';
	}
	$henvendelser = get_posts( $query_args );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="henvendelser-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	// UTF-8 BOM, så Excel på Windows viser æøå korrekt.
	fwrite( $out, "\xEF\xBB\xBF" );

	fputcsv(
		$out,
		array( 'Dato', 'Kilde', 'Navn', 'E-mail', 'Telefon', 'Barnets fødselsdato', 'Ønsket startdato', 'Plads', 'Besked' )
	);

	foreach ( $henvendelser as $henvendelse ) {
		$plads_id   = (int) get_post_meta( $henvendelse->ID, 'plads_id', true );
		$plads_tekst = '';
		if ( $plads_id && 'plads' === get_post_type( $plads_id ) ) {
			$beregnet    = lene_plads_beregn( $plads_id );
			$plads_tekst = $beregnet['timestamp'] ? lene_dansk_dato( $beregnet['timestamp'] ) : '';
		}

		fputcsv(
			$out,
			array(
				get_the_date( 'Y-m-d H:i', $henvendelse ),
				lene_henvendelse_kilde_label( get_post_meta( $henvendelse->ID, 'kilde', true ) ),
				get_post_meta( $henvendelse->ID, 'navn', true ),
				get_post_meta( $henvendelse->ID, 'email', true ),
				get_post_meta( $henvendelse->ID, 'telefon', true ),
				get_post_meta( $henvendelse->ID, 'barnets_foedselsdato', true ),
				get_post_meta( $henvendelse->ID, 'oensket_start', true ),
				$plads_tekst,
				get_post_meta( $henvendelse->ID, 'besked', true ),
			)
		);
	}

	fclose( $out );
	exit;
}
add_action( 'admin_post_lene_export_henvendelser_csv', 'lene_handle_export_henvendelser_csv' );
