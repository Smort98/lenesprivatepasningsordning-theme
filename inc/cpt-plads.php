<?php
/**
 * CPT `plads` + statuslogik.
 *
 * Kunden skriver kun to tal (antal, antal_ledige) plus en dato. Status,
 * mærkat og tælletekst beregnes altid herfra — de gemmes aldrig som egen
 * værdi, så de ikke kan komme ud af trit med tallene.
 */

defined( 'ABSPATH' ) || exit;

function lene_register_cpt_plads() {
	register_post_type(
		'plads',
		array(
			'labels'             => array(
				'name'          => 'Pladser',
				'singular_name' => 'Plads',
				'add_new_item'  => 'Tilføj ny plads',
				'edit_item'     => 'Redigér plads',
				'all_items'     => 'Pladser',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_rest'        => true,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
		)
	);

	register_post_meta(
		'plads',
		'dato',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'plads',
		'antal',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'default'       => 1,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'plads',
		'antal_ledige',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'default'       => 0,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'plads',
		'status_naar_fuld',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'default'       => 'reserveret',
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'plads',
		'note',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		)
	);
}
add_action( 'init', 'lene_register_cpt_plads' );

/**
 * Beregner status + tekster for én plads-post ud fra dens meta.
 * status: 'ledig' | 'delvis' | 'reserveret' | 'optaget'
 */
function lene_plads_beregn( int $post_id ): array {
	$antal        = max( 0, (int) get_post_meta( $post_id, 'antal', true ) );
	$antal_ledige = max( 0, min( $antal, (int) get_post_meta( $post_id, 'antal_ledige', true ) ) );
	$naar_fuld    = get_post_meta( $post_id, 'status_naar_fuld', true ) ?: 'reserveret';
	$dato_raw     = get_post_meta( $post_id, 'dato', true );
	$note         = get_post_meta( $post_id, 'note', true );

	if ( $antal_ledige <= 0 ) {
		$status = ( 'optaget' === $naar_fuld ) ? 'optaget' : 'reserveret';
	} elseif ( $antal_ledige >= $antal ) {
		$status = 'ledig';
	} else {
		$status = 'delvis';
	}

	$labels = array(
		'ledig'      => 'Ledig',
		'delvis'     => 'Delvist ledig',
		'reserveret' => 'Reserveret',
		'optaget'    => 'Optaget',
	);

	if ( $antal_ledige >= $antal && $antal_ledige > 0 ) {
		$tekst_hale = 1 === $antal ? 'plads' : 'pladser';
	} else {
		$tekst_hale = sprintf(
			'af %d %s %s',
			$antal,
			1 === $antal ? 'plads' : 'pladser',
			1 === $antal ? 'ledig' : 'ledige'
		);
	}
	$tekst = trim( $antal_ledige . ' ' . $tekst_hale );

	$timestamp = $dato_raw ? strtotime( $dato_raw ) : false;

	return array(
		'id'           => $post_id,
		'dato_raw'     => $dato_raw,
		'timestamp'    => $timestamp,
		'antal'        => $antal,
		'antal_ledige' => $antal_ledige,
		'status'       => $status,
		'status_label' => $labels[ $status ],
		'tekst'        => $tekst,
		'tekst_hale'   => $tekst_hale,
		'note'         => $note,
	);
}

/**
 * Alle pladser, kommende først, sorteret efter dato.
 *
 * @param bool $kun_fremtidige Skjul datoer der er passeret.
 */
function lene_hent_pladser( bool $kun_fremtidige = false ): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'plads',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'dato',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	$pladser = array();
	foreach ( $query->posts as $post ) {
		$beregnet = lene_plads_beregn( $post->ID );
		if ( $kun_fremtidige && $beregnet['timestamp'] && $beregnet['timestamp'] < strtotime( 'today' ) ) {
			continue;
		}
		$pladser[] = $beregnet;
	}

	return $pladser;
}

/**
 * Summen af ledige pladser på fremtidige datoer — driver mærkatet i headeren.
 */
function lene_antal_ledige_pladser_total(): int {
	$total = 0;
	foreach ( lene_hent_pladser( true ) as $plads ) {
		if ( 'ledig' === $plads['status'] || 'delvis' === $plads['status'] ) {
			$total += $plads['antal_ledige'];
		}
	}
	return $total;
}

/**
 * Den næste plads der har mindst én ledig — bruges i lene/hero.
 */
function lene_naeste_ledige_plads(): ?array {
	foreach ( lene_hent_pladser( true ) as $plads ) {
		if ( $plads['antal_ledige'] > 0 ) {
			return $plads;
		}
	}
	return null;
}

/**
 * Seneste redigeringstidspunkt blandt alle plads-poster — driver
 * "Opdateret <dato>" i pladstavlen.
 */
function lene_seneste_plads_aendring() {
	$query = new WP_Query(
		array(
			'post_type'      => 'plads',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	if ( empty( $query->posts ) ) {
		return false;
	}
	return get_post_modified_time( 'U', false, $query->posts[0] );
}

/**
 * Dansk datoformat "1. januar 2028" ud fra en timestamp.
 */
function lene_dansk_dato( $timestamp, bool $kort_maaned = false ): string {
	if ( ! $timestamp ) {
		return '';
	}
	$maaneder = array(
		1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april', 5 => 'maj', 6 => 'juni',
		7 => 'juli', 8 => 'august', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
	);
	$korte = array(
		1 => 'jan', 2 => 'feb', 3 => 'marts', 4 => 'april', 5 => 'maj', 6 => 'juni',
		7 => 'juli', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec',
	);
	$maaned_num = (int) gmdate( 'n', $timestamp );
	$navn       = $kort_maaned ? $korte[ $maaned_num ] : $maaneder[ $maaned_num ];
	return sprintf( '%d. %s %s', (int) gmdate( 'j', $timestamp ), $navn, gmdate( 'Y', $timestamp ) );
}

/**
 * Samme som lene_dansk_dato(), men splitter årstal ud for sig — bruges af
 * pladstavlens skilte, hvor året sidder i et lille mærke for sig selv.
 */
function lene_dansk_dato_dele( $timestamp, bool $kort_maaned = false ): array {
	if ( ! $timestamp ) {
		return array(
			'dag_maaned' => '',
			'aar'        => '',
		);
	}
	$maaneder = array(
		1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april', 5 => 'maj', 6 => 'juni',
		7 => 'juli', 8 => 'august', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
	);
	$korte = array(
		1 => 'jan', 2 => 'feb', 3 => 'marts', 4 => 'april', 5 => 'maj', 6 => 'juni',
		7 => 'juli', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec',
	);
	$maaned_num = (int) gmdate( 'n', $timestamp );
	$navn       = $kort_maaned ? $korte[ $maaned_num ] : $maaneder[ $maaned_num ];
	return array(
		'dag_maaned' => sprintf( '%d. %s', (int) gmdate( 'j', $timestamp ), $navn ),
		'aar'        => gmdate( 'Y', $timestamp ),
	);
}
