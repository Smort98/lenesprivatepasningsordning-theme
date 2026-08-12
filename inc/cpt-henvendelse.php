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
			'show_in_menu'        => true,
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

function lene_henvendelse_admin_column_content( string $column, int $post_id ): void {
	if ( in_array( $column, array( 'kilde', 'navn', 'email' ), true ) ) {
		echo esc_html( get_post_meta( $post_id, $column, true ) ?: '—' );
	}
}
add_action( 'manage_henvendelse_posts_custom_column', 'lene_henvendelse_admin_column_content', 10, 2 );
