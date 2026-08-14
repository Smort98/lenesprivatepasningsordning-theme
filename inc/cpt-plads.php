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
 * Meta box til plads-CPT'en — det eneste kunden skal udfylde: en dato og
 * to tal. Status, mærkat og prikker beregnes af temaet ud fra dem.
 */
function lene_plads_meta_box() {
	add_meta_box(
		'lene_plads_detaljer',
		'Plads-detaljer',
		'lene_render_plads_meta_box',
		'plads',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_plads', 'lene_plads_meta_box' );

function lene_render_plads_meta_box( WP_Post $post ) {
	wp_nonce_field( 'lene_plads_gem', 'lene_plads_nonce' );

	$dato             = get_post_meta( $post->ID, 'dato', true );
	$antal            = get_post_meta( $post->ID, 'antal', true );
	$antal            = '' === $antal ? 1 : (int) $antal;
	$antal_ledige     = get_post_meta( $post->ID, 'antal_ledige', true );
	$antal_ledige     = '' === $antal_ledige ? 0 : (int) $antal_ledige;
	$status_naar_fuld = get_post_meta( $post->ID, 'status_naar_fuld', true ) ?: 'reserveret';
	$note             = get_post_meta( $post->ID, 'note', true );
	?>
	<style>
		.lene-plads-felter { display: grid; gap: 16px; max-width: 480px; }
		.lene-plads-felter label { display: block; font-weight: 600; margin-bottom: 4px; }
		.lene-plads-felter input[type="date"],
		.lene-plads-felter input[type="number"],
		.lene-plads-felter input[type="text"],
		.lene-plads-felter select { width: 100%; max-width: 260px; }
		.lene-plads-felter .beskrivelse { color: #666; font-size: 13px; margin-top: 4px; }
	</style>
	<div class="lene-plads-felter">
		<div>
			<label for="lene_plads_dato">Startdato for pladsen</label>
			<input type="date" id="lene_plads_dato" name="lene_plads_dato" value="<?php echo esc_attr( $dato ); ?>">
		</div>
		<div>
			<label for="lene_plads_antal">Antal pladser på datoen i alt</label>
			<input type="number" min="0" id="lene_plads_antal" name="lene_plads_antal" value="<?php echo esc_attr( $antal ); ?>">
		</div>
		<div>
			<label for="lene_plads_antal_ledige">Heraf ledige</label>
			<input type="number" min="0" id="lene_plads_antal_ledige" name="lene_plads_antal_ledige" value="<?php echo esc_attr( $antal_ledige ); ?>">
			<p class="beskrivelse">Skriv fx 3 og 2, så vises "2 af 3 pladser ledige" automatisk. Status, farve og prikker beregnes af temaet — du skal ikke vælge dem selv.</p>
		</div>
		<div>
			<label for="lene_plads_status_naar_fuld">Når 0 er ledige, vis som</label>
			<select id="lene_plads_status_naar_fuld" name="lene_plads_status_naar_fuld">
				<option value="reserveret" <?php selected( $status_naar_fuld, 'reserveret' ); ?>>Reserveret</option>
				<option value="optaget" <?php selected( $status_naar_fuld, 'optaget' ); ?>>Optaget</option>
			</select>
		</div>
		<div>
			<label for="lene_plads_note">Note (valgfri)</label>
			<input type="text" id="lene_plads_note" name="lene_plads_note" value="<?php echo esc_attr( $note ); ?>" placeholder="Fx: kun formiddage">
		</div>
	</div>
	<?php
}

function lene_gem_plads_meta( int $post_id ) {
	if ( ! isset( $_POST['lene_plads_nonce'] ) || ! wp_verify_nonce( $_POST['lene_plads_nonce'], 'lene_plads_gem' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$antal_ledige_foer = max( 0, (int) get_post_meta( $post_id, 'antal_ledige', true ) );

	$dato         = isset( $_POST['lene_plads_dato'] ) ? sanitize_text_field( wp_unslash( $_POST['lene_plads_dato'] ) ) : '';
	$antal        = isset( $_POST['lene_plads_antal'] ) ? max( 0, (int) $_POST['lene_plads_antal'] ) : 0;
	$antal_ledige = isset( $_POST['lene_plads_antal_ledige'] ) ? max( 0, (int) $_POST['lene_plads_antal_ledige'] ) : 0;
	$antal_ledige = min( $antal, $antal_ledige );
	$status       = ( isset( $_POST['lene_plads_status_naar_fuld'] ) && 'optaget' === $_POST['lene_plads_status_naar_fuld'] ) ? 'optaget' : 'reserveret';
	$note         = isset( $_POST['lene_plads_note'] ) ? sanitize_text_field( wp_unslash( $_POST['lene_plads_note'] ) ) : '';

	update_post_meta( $post_id, 'dato', $dato );
	update_post_meta( $post_id, 'antal', $antal );
	update_post_meta( $post_id, 'antal_ledige', $antal_ledige );
	update_post_meta( $post_id, 'status_naar_fuld', $status );
	update_post_meta( $post_id, 'note', $note );

	// En plads er lige blevet ledig (var 0, er nu >0) — giv pladsalarm-listen besked.
	if ( 0 === $antal_ledige_foer && $antal_ledige > 0 && function_exists( 'lene_pladsalarm_send_besked' ) ) {
		lene_pladsalarm_send_besked();
	}

	// Titlen sættes automatisk ud fra datoen — kunden skal ikke selv holde den i sync.
	if ( $dato ) {
		$timestamp = strtotime( $dato );
		if ( $timestamp ) {
			remove_action( 'save_post_plads', 'lene_gem_plads_meta' );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => lene_dansk_dato( $timestamp ),
				)
			);
			add_action( 'save_post_plads', 'lene_gem_plads_meta' );
		}
	}
}
add_action( 'save_post_plads', 'lene_gem_plads_meta' );

/**
 * Kolonner i pladslisten i wp-admin, så man kan se status uden at åbne hver post.
 */
function lene_plads_admin_columns( array $columns ): array {
	unset( $columns['date'] );
	$columns['dato']   = 'Dato';
	$columns['antal']  = 'Pladser';
	$columns['status'] = 'Status';
	return $columns;
}
add_filter( 'manage_plads_posts_columns', 'lene_plads_admin_columns' );

function lene_plads_admin_column_content( string $column, int $post_id ): void {
	if ( 'dato' === $column ) {
		$dato = get_post_meta( $post_id, 'dato', true );
		echo esc_html( $dato ? lene_dansk_dato( strtotime( $dato ) ) : '—' );
		return;
	}
	if ( 'antal' === $column || 'status' === $column ) {
		$beregnet = lene_plads_beregn( $post_id );
		echo esc_html( 'antal' === $column ? $beregnet['tekst'] : $beregnet['status_label'] );
	}
}
add_action( 'manage_plads_posts_custom_column', 'lene_plads_admin_column_content', 10, 2 );

function lene_plads_sortable_columns( array $columns ): array {
	$columns['dato'] = 'dato';
	return $columns;
}
add_filter( 'manage_edit-plads_sortable_columns', 'lene_plads_sortable_columns' );

function lene_plads_admin_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'plads' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( 'dato' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'dato' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( '' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'dato' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'lene_plads_admin_orderby' );

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
