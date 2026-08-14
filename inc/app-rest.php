<?php
/**
 * Lille REST-API til Lene-appen (PWA'en under /app/). Bevidst adskilt fra
 * kernens egne wp/v2/plads og wp/v2/lukkedag-endpoints: de ville tillade
 * at gemme meta uden om lene_gem_plads_meta()/lene_gem_lukkedag_meta(),
 * hvilket ville springe over sideeffekter som pladsalarm-udsendelse og
 * automatisk titel-opdatering (de handlers lytter på et klassisk
 * editor-nonce-felt, som REST-kald aldrig sender).
 *
 * Priser og åbningstider er slet ikke meta/CPT — de er attributter på
 * lene/pris og lene/aabningstider-blokkene i "Praktisk info"-siden, så
 * de læses/skrives via parse_blocks()/serialize_blocks().
 */

defined( 'ABSPATH' ) || exit;

function lene_app_rest_tjek_adgang(): bool {
	return current_user_can( 'edit_posts' );
}

/* ---------------------------------------------------------------------
 * Pladser
 * ------------------------------------------------------------------- */

function lene_app_plads_til_array( int $post_id ): array {
	$data                     = lene_plads_beregn( $post_id );
	$data['status_naar_fuld'] = get_post_meta( $post_id, 'status_naar_fuld', true ) ?: 'reserveret';
	$data['dato']             = $data['dato_raw'];
	return $data;
}

function lene_app_hent_alle_pladser(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'plads',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'dato',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	return array_map( 'lene_app_plads_til_array', $query->posts );
}

function lene_app_gem_plads( int $post_id, array $data ): array {
	$antal_ledige_foer = max( 0, (int) get_post_meta( $post_id, 'antal_ledige', true ) );

	$dato         = array_key_exists( 'dato', $data ) ? sanitize_text_field( (string) $data['dato'] ) : get_post_meta( $post_id, 'dato', true );
	$antal        = array_key_exists( 'antal', $data ) ? max( 0, (int) $data['antal'] ) : max( 0, (int) get_post_meta( $post_id, 'antal', true ) );
	$antal_ledige = array_key_exists( 'antal_ledige', $data ) ? max( 0, (int) $data['antal_ledige'] ) : $antal_ledige_foer;
	$antal_ledige = min( $antal, $antal_ledige );
	$status       = array_key_exists( 'status_naar_fuld', $data )
		? ( 'optaget' === $data['status_naar_fuld'] ? 'optaget' : 'reserveret' )
		: ( get_post_meta( $post_id, 'status_naar_fuld', true ) ?: 'reserveret' );
	$note         = array_key_exists( 'note', $data ) ? sanitize_text_field( (string) $data['note'] ) : get_post_meta( $post_id, 'note', true );

	update_post_meta( $post_id, 'dato', $dato );
	update_post_meta( $post_id, 'antal', $antal );
	update_post_meta( $post_id, 'antal_ledige', $antal_ledige );
	update_post_meta( $post_id, 'status_naar_fuld', $status );
	update_post_meta( $post_id, 'note', $note );

	// Samme "0 -> ledig" alarm-trigger som den klassiske gem-handler i cpt-plads.php.
	if ( 0 === $antal_ledige_foer && $antal_ledige > 0 && function_exists( 'lene_pladsalarm_send_besked' ) ) {
		lene_pladsalarm_send_besked();
	}

	if ( $dato ) {
		$timestamp = strtotime( $dato );
		if ( $timestamp && function_exists( 'lene_dansk_dato' ) ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => lene_dansk_dato( $timestamp ),
				)
			);
		}
	}

	return lene_app_plads_til_array( $post_id );
}

function lene_app_opret_plads( array $data ): array {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'plads',
			'post_status' => 'publish',
			'post_title'  => 'Ny plads',
		)
	);
	return lene_app_gem_plads( $post_id, $data );
}

/* ---------------------------------------------------------------------
 * Lukkedage
 * ------------------------------------------------------------------- */

function lene_app_lukkedag_til_array( int $post_id ): array {
	return array(
		'id'          => $post_id,
		'periode'     => get_post_meta( $post_id, 'periode', true ),
		'datoer'      => get_post_meta( $post_id, 'datoer', true ),
		'aarsag'      => get_post_meta( $post_id, 'aarsag', true ),
		'skjul_efter' => get_post_meta( $post_id, 'skjul_efter', true ),
	);
}

function lene_app_hent_alle_lukkedage(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'lukkedag',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'skjul_efter',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	return array_map( 'lene_app_lukkedag_til_array', $query->posts );
}

function lene_app_gem_lukkedag( int $post_id, array $data ): array {
	foreach ( array( 'periode', 'datoer', 'aarsag', 'skjul_efter' ) as $felt ) {
		if ( array_key_exists( $felt, $data ) ) {
			update_post_meta( $post_id, $felt, sanitize_text_field( (string) $data[ $felt ] ) );
		}
	}
	if ( empty( get_the_title( $post_id ) ) ) {
		$periode = get_post_meta( $post_id, 'periode', true );
		if ( $periode ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $periode ) );
		}
	}
	return lene_app_lukkedag_til_array( $post_id );
}

function lene_app_opret_lukkedag( array $data ): array {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'lukkedag',
			'post_status' => 'publish',
			'post_title'  => 'Ny lukkeperiode',
		)
	);
	return lene_app_gem_lukkedag( $post_id, $data );
}

/* ---------------------------------------------------------------------
 * Fælles blok-hjælpere (praktisk-info-siden er datakilden for både
 * åbningstider og priser).
 * ------------------------------------------------------------------- */

function lene_app_praktisk_info_side(): ?WP_Post {
	$side = get_page_by_path( 'praktisk-info' );
	return $side ?: null;
}

function lene_app_blok_standardattrs( string $block_name ): array {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
	if ( ! $block_type ) {
		return array();
	}
	$standard = array();
	foreach ( $block_type->attributes as $key => $def ) {
		if ( array_key_exists( 'default', $def ) ) {
			$standard[ $key ] = $def['default'];
		}
	}
	return $standard;
}

/* ---------------------------------------------------------------------
 * Åbningstider (lene/aabningstider — attributter, ingen underblokke)
 * ------------------------------------------------------------------- */

function lene_app_hent_aabningstider(): ?array {
	$side = lene_app_praktisk_info_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/aabningstider' );
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/aabningstider' === ( $blok['blockName'] ?? '' ) ) {
			$attrs = array_merge( $standard, $blok['attrs'] ?? array() );
			return array(
				'dage' => $attrs['dage'],
				'note' => $attrs['note'],
			);
		}
	}
	return null;
}

function lene_app_gem_aabningstider( array $data ): ?array {
	$side = lene_app_praktisk_info_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/aabningstider' );
	$blokke   = parse_blocks( $side->post_content );
	$fundet   = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/aabningstider' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$nuvaerende = array_merge( $standard, $blok['attrs'] ?? array() );
		$nye_attrs  = array_merge(
			$nuvaerende,
			array(
				'dage' => array_key_exists( 'dage', $data ) ? lene_app_saniter_dage( $data['dage'] ) : $nuvaerende['dage'],
				'note' => array_key_exists( 'note', $data ) ? sanitize_textarea_field( (string) $data['note'] ) : $nuvaerende['note'],
			)
		);
		$blokke[ $i ]['attrs'] = $nye_attrs;
		$fundet                = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	wp_update_post(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_aabningstider();
}

function lene_app_saniter_dage( $dage ): array {
	$ud = array();
	foreach ( (array) $dage as $dag ) {
		$ugedage = array();
		foreach ( (array) ( $dag['ugedage'] ?? array() ) as $u ) {
			$u = (int) $u;
			if ( $u >= 1 && $u <= 7 ) {
				$ugedage[] = $u;
			}
		}
		$ud[] = array(
			'etiket'  => sanitize_text_field( (string) ( $dag['etiket'] ?? '' ) ),
			'ugedage' => $ugedage,
			'aabner'  => sanitize_text_field( (string) ( $dag['aabner'] ?? '' ) ),
			'lukker'  => sanitize_text_field( (string) ( $dag['lukker'] ?? '' ) ),
		);
	}
	return $ud;
}

/* ---------------------------------------------------------------------
 * Priser (lene/pris — har underblokke lene/pris-linje, hvor tekst/beløb
 * er "rich-text"-kildet fra selve den gemte HTML, ikke fra attrs-JSON).
 * Vi genopbygger derfor blokkens markup som tekst og lader parse_blocks()
 * — den samme parser WordPress selv bruger — stå for den rigtige
 * opdeling i innerBlocks/innerContent, i stedet for at forsøge at bygge
 * det array-format i hånden.
 * ------------------------------------------------------------------- */

function lene_app_les_pris_linje( array $blok ): array {
	$html   = $blok['innerHTML'] ?? '';
	$tekst  = '';
	$beloeb = '';
	if ( preg_match( '/<span class="receipt__label">(.*?)<\/span>/s', $html, $m ) ) {
		$tekst = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<span class="receipt__amount[^"]*">(.*?)<\/span>/s', $html, $m ) ) {
		$beloeb = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	return array(
		'tekst'   => $tekst,
		'beloeb'  => $beloeb,
		'erMinus' => ! empty( $blok['attrs']['erMinus'] ),
	);
}

function lene_app_hent_priser(): ?array {
	$side = lene_app_praktisk_info_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/pris' );
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/pris' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$attrs  = array_merge( $standard, $blok['attrs'] ?? array() );
		$linjer = array();
		foreach ( $blok['innerBlocks'] ?? array() as $child ) {
			if ( 'lene/pris-linje' === ( $child['blockName'] ?? '' ) ) {
				$linjer[] = lene_app_les_pris_linje( $child );
			}
		}
		return array(
			'totalBeloeb' => $attrs['totalBeloeb'],
			'medSmaat'    => array_values( array_filter( (array) $attrs['medSmaat'] ) ),
			'linjer'      => $linjer,
		);
	}
	return null;
}

function lene_app_byg_pris_markup( array $attrs, array $linjer ): string {
	$linjer_markup = '';
	foreach ( $linjer as $linje ) {
		$tekst    = esc_html( (string) ( $linje['tekst'] ?? '' ) );
		$beloeb   = esc_html( (string) ( $linje['beloeb'] ?? '' ) );
		$er_minus = ! empty( $linje['erMinus'] );
		$klasse   = 'receipt__amount' . ( $er_minus ? ' is-minus' : '' );
		$html     = '<div class="wp-block-lene-pris-linje receipt__row"><span class="receipt__label">' . $tekst . '</span><span class="receipt__fill"></span><span class="' . $klasse . '">' . $beloeb . '</span></div>';
		$linjer_markup .= '<!-- wp:lene/pris-linje ' . wp_json_encode( array( 'erMinus' => $er_minus ) ) . ' -->' . "\n" . $html . "\n" . '<!-- /wp:lene/pris-linje -->' . "\n\n";
	}

	$fine_html = '';
	foreach ( (array) ( $attrs['medSmaat'] ?? array() ) as $punkt ) {
		if ( '' === trim( (string) $punkt ) ) {
			continue;
		}
		$fine_html .= '<li>' . esc_html( (string) $punkt ) . '</li>';
	}

	$sektion_klasse = 'wp-block-lene-pris section section--' . ( 'paper' === ( $attrs['baggrund'] ?? 'sky' ) ? 'paper' : 'sky' );

	$aabning = '<section class="' . $sektion_klasse . '"><div class="wrap"><div class="section__head"><div><p class="eyebrow">' . esc_html( (string) $attrs['eyebrow'] ) . '</p><h2>' . esc_html( (string) $attrs['titel'] ) . '</h2></div></div><div class="receipt">';
	$lukning = '<div class="receipt__total"><span>' . esc_html( (string) $attrs['totalTekst'] ) . '</span><b>' . esc_html( (string) $attrs['totalBeloeb'] ) . '</b></div><ul class="receipt__fine">' . $fine_html . '</ul></div></div></section>';

	return '<!-- wp:lene/pris ' . wp_json_encode( $attrs ) . ' -->' . "\n" . $aabning . "\n\n" . $linjer_markup . $lukning . "\n" . '<!-- /wp:lene/pris -->';
}

function lene_app_gem_priser( array $data ): ?array {
	$side = lene_app_praktisk_info_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/pris' );
	$blokke   = parse_blocks( $side->post_content );
	$fundet   = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/pris' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$nuvaerende = array_merge( $standard, $blok['attrs'] ?? array() );
		$attrs      = array(
			'eyebrow'     => $nuvaerende['eyebrow'],
			'titel'       => $nuvaerende['titel'],
			'totalTekst'  => $nuvaerende['totalTekst'],
			'totalBeloeb' => array_key_exists( 'totalBeloeb', $data ) ? sanitize_text_field( (string) $data['totalBeloeb'] ) : $nuvaerende['totalBeloeb'],
			'medSmaat'    => array_key_exists( 'medSmaat', $data )
				? array_values( array_map( 'sanitize_text_field', (array) $data['medSmaat'] ) )
				: $nuvaerende['medSmaat'],
			'baggrund'    => $nuvaerende['baggrund'],
		);

		$linjer = array();
		foreach ( (array) ( $data['linjer'] ?? array() ) as $linje ) {
			$linjer[] = array(
				'tekst'   => sanitize_text_field( (string) ( $linje['tekst'] ?? '' ) ),
				'beloeb'  => sanitize_text_field( (string) ( $linje['beloeb'] ?? '' ) ),
				'erMinus' => ! empty( $linje['erMinus'] ),
			);
		}
		if ( ! array_key_exists( 'linjer', $data ) ) {
			// Ingen linjer sendt med — behold de eksisterende uændret.
			foreach ( $blok['innerBlocks'] ?? array() as $child ) {
				if ( 'lene/pris-linje' === ( $child['blockName'] ?? '' ) ) {
					$linjer[] = lene_app_les_pris_linje( $child );
				}
			}
		}

		$markup       = lene_app_byg_pris_markup( $attrs, $linjer );
		$blokke[ $i ] = parse_blocks( $markup )[0];
		$fundet       = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	wp_update_post(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_priser();
}

/* ---------------------------------------------------------------------
 * Ruter
 * ------------------------------------------------------------------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'lene-app/v1',
			'/pladser',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => fn() => lene_app_hent_alle_pladser(),
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'POST',
					'callback'            => fn( WP_REST_Request $req ) => lene_app_opret_plads( (array) $req->get_json_params() ),
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);
		register_rest_route(
			'lene-app/v1',
			'/pladser/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$id = (int) $req['id'];
						if ( 'plads' !== get_post_type( $id ) ) {
							return new WP_Error( 'ikke_fundet', 'Plads ikke fundet.', array( 'status' => 404 ) );
						}
						return lene_app_gem_plads( $id, (array) $req->get_json_params() );
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => function ( WP_REST_Request $req ) {
						$id = (int) $req['id'];
						if ( 'plads' !== get_post_type( $id ) ) {
							return new WP_Error( 'ikke_fundet', 'Plads ikke fundet.', array( 'status' => 404 ) );
						}
						wp_delete_post( $id, true );
						return array( 'slettet' => true );
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/lukkedage',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => fn() => lene_app_hent_alle_lukkedage(),
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'POST',
					'callback'            => fn( WP_REST_Request $req ) => lene_app_opret_lukkedag( (array) $req->get_json_params() ),
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);
		register_rest_route(
			'lene-app/v1',
			'/lukkedage/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$id = (int) $req['id'];
						if ( 'lukkedag' !== get_post_type( $id ) ) {
							return new WP_Error( 'ikke_fundet', 'Lukkeperiode ikke fundet.', array( 'status' => 404 ) );
						}
						return lene_app_gem_lukkedag( $id, (array) $req->get_json_params() );
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => function ( WP_REST_Request $req ) {
						$id = (int) $req['id'];
						if ( 'lukkedag' !== get_post_type( $id ) ) {
							return new WP_Error( 'ikke_fundet', 'Lukkeperiode ikke fundet.', array( 'status' => 404 ) );
						}
						wp_delete_post( $id, true );
						return array( 'slettet' => true );
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/aabningstider',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_aabningstider();
						return null === $data ? new WP_Error( 'ikke_fundet', 'Praktisk info-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$data = lene_app_gem_aabningstider( (array) $req->get_json_params() );
						return null === $data ? new WP_Error( 'ikke_fundet', 'Praktisk info-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/priser',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_priser();
						return null === $data ? new WP_Error( 'ikke_fundet', 'Praktisk info-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$data = lene_app_gem_priser( (array) $req->get_json_params() );
						return null === $data ? new WP_Error( 'ikke_fundet', 'Praktisk info-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);
	}
);
