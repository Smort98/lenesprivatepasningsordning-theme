<?php
/**
 * CPT `lukkedag` — ferie- og lukkeperioder. Samme princip som `plads`:
 * én indholdstype for sig, så perioder kan genbruges flere steder
 * (fx "Næste lukkeuge" på forsiden).
 */

defined( 'ABSPATH' ) || exit;

function lene_register_cpt_lukkedag() {
	register_post_type(
		'lukkedag',
		array(
			'labels'             => array(
				'name'          => 'Lukkedage',
				'singular_name' => 'Lukkedag',
				'add_new_item'  => 'Tilføj ny lukkeperiode',
				'edit_item'     => 'Redigér lukkeperiode',
				'all_items'     => 'Lukkedage',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-palmtree',
			'show_in_rest'        => true,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
		)
	);

	foreach ( array( 'periode', 'datoer', 'aarsag', 'skjul_efter' ) as $felt ) {
		register_post_meta(
			'lukkedag',
			$felt,
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => fn() => current_user_can( 'edit_posts' ),
			)
		);
	}
}
add_action( 'init', 'lene_register_cpt_lukkedag' );

function lene_lukkedag_meta_box() {
	add_meta_box( 'lene_lukkedag_detaljer', 'Lukkeperiode-detaljer', 'lene_render_lukkedag_meta_box', 'lukkedag', 'normal', 'high' );
}
add_action( 'add_meta_boxes_lukkedag', 'lene_lukkedag_meta_box' );

function lene_render_lukkedag_meta_box( WP_Post $post ) {
	wp_nonce_field( 'lene_lukkedag_gem', 'lene_lukkedag_nonce' );

	$periode     = get_post_meta( $post->ID, 'periode', true );
	$datoer      = get_post_meta( $post->ID, 'datoer', true );
	$aarsag      = get_post_meta( $post->ID, 'aarsag', true );
	$skjul_efter = get_post_meta( $post->ID, 'skjul_efter', true );
	?>
	<style>
		.lene-lukkedag-felter { display: grid; gap: 16px; max-width: 480px; }
		.lene-lukkedag-felter label { display: block; font-weight: 600; margin-bottom: 4px; }
		.lene-lukkedag-felter input { width: 100%; max-width: 320px; }
		.lene-lukkedag-felter .beskrivelse { color: #666; font-size: 13px; margin-top: 4px; }
	</style>
	<div class="lene-lukkedag-felter">
		<div>
			<label for="lene_lukkedag_periode">Periode (fx "Uge 29–30" eller "24. dec – 1. jan")</label>
			<input type="text" id="lene_lukkedag_periode" name="lene_lukkedag_periode" value="<?php echo esc_attr( $periode ); ?>">
		</div>
		<div>
			<label for="lene_lukkedag_datoer">Datoer (fx "13.–24. juli")</label>
			<input type="text" id="lene_lukkedag_datoer" name="lene_lukkedag_datoer" value="<?php echo esc_attr( $datoer ); ?>">
		</div>
		<div>
			<label for="lene_lukkedag_aarsag">Årsag</label>
			<input type="text" id="lene_lukkedag_aarsag" name="lene_lukkedag_aarsag" value="<?php echo esc_attr( $aarsag ); ?>" placeholder="Fx: Sommerferie">
		</div>
		<div>
			<label for="lene_lukkedag_skjul_efter">Skjul efter denne dato</label>
			<input type="date" id="lene_lukkedag_skjul_efter" name="lene_lukkedag_skjul_efter" value="<?php echo esc_attr( $skjul_efter ); ?>">
			<p class="beskrivelse">Perioden falder automatisk af listen dagen efter denne dato — typisk periodens sidste dag.</p>
		</div>
	</div>
	<?php
}

function lene_gem_lukkedag_meta( int $post_id ) {
	if ( ! isset( $_POST['lene_lukkedag_nonce'] ) || ! wp_verify_nonce( $_POST['lene_lukkedag_nonce'], 'lene_lukkedag_gem' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'periode', 'datoer', 'aarsag', 'skjul_efter' ) as $felt ) {
		$key = 'lene_lukkedag_' . $felt;
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $felt, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	if ( empty( get_the_title( $post_id ) ) && ! empty( $_POST['lene_lukkedag_periode'] ) ) {
		remove_action( 'save_post_lukkedag', 'lene_gem_lukkedag_meta' );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => sanitize_text_field( wp_unslash( $_POST['lene_lukkedag_periode'] ) ),
			)
		);
		add_action( 'save_post_lukkedag', 'lene_gem_lukkedag_meta' );
	}
}
add_action( 'save_post_lukkedag', 'lene_gem_lukkedag_meta' );

function lene_lukkedag_admin_columns( array $columns ): array {
	unset( $columns['date'] );
	$columns['periode']     = 'Periode';
	$columns['datoer']      = 'Datoer';
	$columns['aarsag']      = 'Årsag';
	$columns['skjul_efter'] = 'Skjules efter';
	return $columns;
}
add_filter( 'manage_lukkedag_posts_columns', 'lene_lukkedag_admin_columns' );

function lene_lukkedag_admin_column_content( string $column, int $post_id ): void {
	if ( in_array( $column, array( 'periode', 'datoer', 'aarsag', 'skjul_efter' ), true ) ) {
		echo esc_html( get_post_meta( $post_id, $column, true ) ?: '—' );
	}
}
add_action( 'manage_lukkedag_posts_custom_column', 'lene_lukkedag_admin_column_content', 10, 2 );

/**
 * Kommende lukkeperioder, sorteret efter skjul_efter (den der udløber
 * snarest, først). Poster hvor skjul_efter er passeret, skjules.
 */
function lene_hent_lukkedage(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'lukkedag',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'skjul_efter',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	$i_dag      = current_time( 'Y-m-d' );
	$perioder   = array();
	foreach ( $query->posts as $post ) {
		$skjul_efter = get_post_meta( $post->ID, 'skjul_efter', true );
		if ( $skjul_efter && $skjul_efter < $i_dag ) {
			continue;
		}
		$perioder[] = array(
			'id'      => $post->ID,
			'periode' => get_post_meta( $post->ID, 'periode', true ),
			'datoer'  => get_post_meta( $post->ID, 'datoer', true ),
			'aarsag'  => get_post_meta( $post->ID, 'aarsag', true ),
		);
	}

	return $perioder;
}
