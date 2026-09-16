<?php
/**
 * Udvidelse af PWA-REST-API'et (inc/app-rest.php) med redigering af
 * sideindhold, der ikke er strukturerede CPT'er: hero-teksten og
 * dagsrytmen på forsiden, tidslinjen ("Hvem er jeg") og fotoalbummet.
 * Samme princip som lene_app_gem_priser(): byg blokkens markup som
 * tekststreng og lad parse_blocks() stå for opdelingen i innerBlocks.
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------
 * Hero (forsidens overskrift/tekst)
 * ------------------------------------------------------------------- */

function lene_app_hero_side(): ?WP_Post {
	$forside_id = (int) get_option( 'page_on_front' );
	if ( ! $forside_id ) {
		return null;
	}
	$side = get_post( $forside_id );
	return $side ?: null;
}

function lene_app_hent_hero(): ?array {
	$side = lene_app_hero_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/hero' );
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/hero' === ( $blok['blockName'] ?? '' ) ) {
			$attrs = array_merge( $standard, $blok['attrs'] ?? array() );
			return array(
				'overrubrik' => $attrs['overrubrik'],
				'titel'      => $attrs['titel'],
				'tekst'      => $attrs['tekst'],
			);
		}
	}
	return null;
}

function lene_app_gem_hero( array $data ): ?array {
	$side = lene_app_hero_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/hero' );
	$blokke   = parse_blocks( $side->post_content );
	$fundet   = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/hero' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$nuvaerende = array_merge( $standard, $blok['attrs'] ?? array() );
		foreach ( array( 'overrubrik', 'titel', 'tekst' ) as $felt ) {
			if ( array_key_exists( $felt, $data ) ) {
				$nuvaerende[ $felt ] = sanitize_text_field( (string) $data[ $felt ] );
			}
		}
		$blokke[ $i ]['attrs'] = $nuvaerende;
		$fundet                = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	lene_app_opdater_side_uden_kses(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_hero();
}

/* ---------------------------------------------------------------------
 * Tidslinje ("Hvem er jeg" — efteruddannelse/kurser)
 * ------------------------------------------------------------------- */

function lene_app_hvem_er_jeg_side(): ?WP_Post {
	$side = get_page_by_path( 'hvem-er-jeg' );
	return $side ?: null;
}

function lene_app_les_tidslinje_punkt( array $blok ): array {
	$html  = $blok['innerHTML'] ?? '';
	$aar   = '';
	$titel = '';
	$tekst = '';
	if ( preg_match( '/<span class="timeline__aar">(.*?)<\/span>/s', $html, $m ) ) {
		$aar = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<h3[^>]*>(.*?)<\/h3>/s', $html, $m ) ) {
		$titel = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<p[^>]*>(.*?)<\/p>/s', $html, $m ) ) {
		$tekst = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	return array(
		'aar'   => $aar,
		'titel' => $titel,
		'tekst' => $tekst,
	);
}

function lene_app_hent_tidslinje(): ?array {
	$side = lene_app_hvem_er_jeg_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/tidslinje' );
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/tidslinje' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$attrs   = array_merge( $standard, $blok['attrs'] ?? array() );
		$punkter = array();
		foreach ( $blok['innerBlocks'] ?? array() as $child ) {
			if ( 'lene/tidslinje-punkt' === ( $child['blockName'] ?? '' ) ) {
				$punkter[] = lene_app_les_tidslinje_punkt( $child );
			}
		}
		return array(
			'eyebrow' => $attrs['eyebrow'],
			'titel'   => $attrs['titel'],
			'punkter' => $punkter,
		);
	}
	return null;
}

function lene_app_byg_tidslinje_markup( array $attrs, array $punkter ): string {
	$punkter_markup = '';
	foreach ( $punkter as $p ) {
		$aar    = esc_html( (string) ( $p['aar'] ?? '' ) );
		$titel  = esc_html( (string) ( $p['titel'] ?? '' ) );
		$tekst  = esc_html( (string) ( $p['tekst'] ?? '' ) );
		$html   = '<li class="wp-block-lene-tidslinje-punkt timeline__item"><span class="timeline__aar">' . $aar . '</span><div><h3>' . $titel . '</h3><p>' . $tekst . '</p></div></li>';
		$punkter_markup .= '<!-- wp:lene/tidslinje-punkt -->' . "\n" . $html . "\n" . '<!-- /wp:lene/tidslinje-punkt -->' . "\n\n";
	}

	$aabning = '<section class="wp-block-lene-tidslinje section section--' . esc_attr( (string) $attrs['baggrund'] ) . '"><div class="wrap"><div class="section__head"><div><p class="eyebrow">' . esc_html( (string) $attrs['eyebrow'] ) . '</p><h2>' . esc_html( (string) $attrs['titel'] ) . '</h2></div></div><ul class="timeline">';
	$lukning = '</ul></div></section>';

	return '<!-- wp:lene/tidslinje ' . wp_json_encode( $attrs ) . ' -->' . "\n" . $aabning . "\n\n" . $punkter_markup . $lukning . "\n" . '<!-- /wp:lene/tidslinje -->';
}

function lene_app_gem_tidslinje( array $data ): ?array {
	$side = lene_app_hvem_er_jeg_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/tidslinje' );
	$blokke   = parse_blocks( $side->post_content );
	$fundet   = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/tidslinje' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$nuvaerende = array_merge( $standard, $blok['attrs'] ?? array() );
		if ( array_key_exists( 'eyebrow', $data ) ) {
			$nuvaerende['eyebrow'] = sanitize_text_field( (string) $data['eyebrow'] );
		}
		if ( array_key_exists( 'titel', $data ) ) {
			$nuvaerende['titel'] = sanitize_text_field( (string) $data['titel'] );
		}

		$punkter = array();
		foreach ( (array) ( $data['punkter'] ?? array() ) as $p ) {
			$punkter[] = array(
				'aar'   => sanitize_text_field( (string) ( $p['aar'] ?? '' ) ),
				'titel' => sanitize_text_field( (string) ( $p['titel'] ?? '' ) ),
				'tekst' => sanitize_text_field( (string) ( $p['tekst'] ?? '' ) ),
			);
		}
		if ( ! array_key_exists( 'punkter', $data ) ) {
			foreach ( $blok['innerBlocks'] ?? array() as $child ) {
				if ( 'lene/tidslinje-punkt' === ( $child['blockName'] ?? '' ) ) {
					$punkter[] = lene_app_les_tidslinje_punkt( $child );
				}
			}
		}

		$markup       = lene_app_byg_tidslinje_markup( $nuvaerende, $punkter );
		$blokke[ $i ] = parse_blocks( $markup )[0];
		$fundet       = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	lene_app_opdater_side_uden_kses(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_tidslinje();
}

/* ---------------------------------------------------------------------
 * Dagsrytme (forsidens døgnrytme)
 * ------------------------------------------------------------------- */

function lene_app_les_dagsrytme_punkt( array $blok ): array {
	$html      = $blok['innerHTML'] ?? '';
	$tidspunkt = '';
	$titel     = '';
	$tekst     = '';
	if ( preg_match( '/<span class="time">(.*?)<\/span>/s', $html, $m ) ) {
		$tidspunkt = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<h3[^>]*>(.*?)<\/h3>/s', $html, $m ) ) {
		$titel = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<p[^>]*>(.*?)<\/p>/s', $html, $m ) ) {
		$tekst = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	return array(
		'tidspunkt' => $tidspunkt,
		'titel'     => $titel,
		'tekst'     => $tekst,
	);
}

/**
 * dagsrytme-blokkens eyebrow/titel/lede er (i modsætning til fx
 * tidslinjens) "rich-text"-kildet fra selve den gemte HTML, ikke
 * plain-string-attrs i JSON-kommentaren — parse_blocks() henter dem
 * derfor IKKE med i $blok['attrs'], vi skal selv læse dem ud af
 * $blok['innerHTML'] (blokkens egen markup, før dens innerBlocks).
 */
function lene_app_les_dagsrytme_tekster( array $blok ): array {
	$html    = $blok['innerHTML'] ?? '';
	$eyebrow = '';
	$titel   = '';
	$lede    = '';
	if ( preg_match( '/<p class="eyebrow">(.*?)<\/p>/s', $html, $m ) ) {
		$eyebrow = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<h2[^>]*>(.*?)<\/h2>/s', $html, $m ) ) {
		$titel = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<p class="lede"[^>]*>(.*?)<\/p>/s', $html, $m ) ) {
		$lede = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	return array(
		'eyebrow' => $eyebrow,
		'titel'   => $titel,
		'lede'    => $lede,
	);
}

function lene_app_hent_dagsrytme(): ?array {
	$side = lene_app_hero_side();
	if ( ! $side ) {
		return null;
	}
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/dagsrytme' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$punkter = array();
		foreach ( $blok['innerBlocks'] ?? array() as $child ) {
			if ( 'lene/dagsrytme-punkt' === ( $child['blockName'] ?? '' ) ) {
				$punkter[] = lene_app_les_dagsrytme_punkt( $child );
			}
		}
		return array_merge( lene_app_les_dagsrytme_tekster( $blok ), array( 'punkter' => $punkter ) );
	}
	return null;
}

/**
 * $tekster indeholder eyebrow/titel/lede (rent tekstligt, ender som HTML
 * her) — $json_attrs er de "rigtige" plain-attrs (baggrund, anchor), som
 * rent faktisk hører hjemme i blok-kommentarens JSON.
 */
function lene_app_byg_dagsrytme_markup( array $tekster, array $json_attrs, array $punkter ): string {
	$punkter_markup = '';
	foreach ( $punkter as $p ) {
		$tid   = esc_html( (string) ( $p['tidspunkt'] ?? '' ) );
		$titel = esc_html( (string) ( $p['titel'] ?? '' ) );
		$tekst = esc_html( (string) ( $p['tekst'] ?? '' ) );
		$html  = '<li class="wp-block-lene-dagsrytme-punkt"><span class="time">' . $tid . '</span><div><h3>' . $titel . '</h3><p>' . $tekst . '</p></div></li>';
		$punkter_markup .= '<!-- wp:lene/dagsrytme-punkt -->' . "\n" . $html . "\n" . '<!-- /wp:lene/dagsrytme-punkt -->' . "\n\n";
	}

	$eyebrow = esc_html( (string) ( $tekster['eyebrow'] ?? '' ) );
	$titel   = esc_html( (string) ( $tekster['titel'] ?? '' ) );
	$lede    = esc_html( (string) ( $tekster['lede'] ?? '' ) );
	$anchor  = ! empty( $json_attrs['anchor'] ) ? ' id="' . esc_attr( (string) $json_attrs['anchor'] ) . '"' : '';
	$bg      = 'sky' === ( $json_attrs['baggrund'] ?? 'sky' ) ? 'sky' : 'paper';

	$aabning = '<section class="wp-block-lene-dagsrytme section section--' . $bg . '"' . $anchor . '><div class="wrap"><div class="section__head"><div><p class="eyebrow">' . $eyebrow . '</p><h2>' . $titel . '</h2></div><p class="lede" style="max-width:38ch">' . $lede . '</p></div><ul class="rhythm">';
	$lukning = '</ul></div></section>';

	return '<!-- wp:lene/dagsrytme ' . wp_json_encode( $json_attrs ) . ' -->' . "\n" . $aabning . "\n\n" . $punkter_markup . $lukning . "\n" . '<!-- /wp:lene/dagsrytme -->';
}

function lene_app_gem_dagsrytme( array $data ): ?array {
	$side = lene_app_hero_side();
	if ( ! $side ) {
		return null;
	}
	$blokke = parse_blocks( $side->post_content );
	$fundet = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/dagsrytme' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$tekster = lene_app_les_dagsrytme_tekster( $blok );
		foreach ( array( 'eyebrow', 'titel', 'lede' ) as $felt ) {
			if ( array_key_exists( $felt, $data ) ) {
				$tekster[ $felt ] = sanitize_text_field( (string) $data[ $felt ] );
			}
		}
		// "anchor" og "baggrund" er de eneste rigtige JSON-attrs på denne blok.
		$json_attrs = array(
			'anchor'   => $blok['attrs']['anchor'] ?? '',
			'baggrund' => $blok['attrs']['baggrund'] ?? 'paper',
		);

		$punkter = array();
		foreach ( (array) ( $data['punkter'] ?? array() ) as $p ) {
			$punkter[] = array(
				'tidspunkt' => sanitize_text_field( (string) ( $p['tidspunkt'] ?? '' ) ),
				'titel'     => sanitize_text_field( (string) ( $p['titel'] ?? '' ) ),
				'tekst'     => sanitize_text_field( (string) ( $p['tekst'] ?? '' ) ),
			);
		}
		if ( ! array_key_exists( 'punkter', $data ) ) {
			foreach ( $blok['innerBlocks'] ?? array() as $child ) {
				if ( 'lene/dagsrytme-punkt' === ( $child['blockName'] ?? '' ) ) {
					$punkter[] = lene_app_les_dagsrytme_punkt( $child );
				}
			}
		}

		$markup       = lene_app_byg_dagsrytme_markup( $tekster, $json_attrs, $punkter );
		$blokke[ $i ] = parse_blocks( $markup )[0];
		$fundet       = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	lene_app_opdater_side_uden_kses(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_dagsrytme();
}

/* ---------------------------------------------------------------------
 * Fotoalbum (lene/galleri — wrapper om core/image)
 * ------------------------------------------------------------------- */

function lene_app_fotoalbum_side(): ?WP_Post {
	$side = get_page_by_path( 'fotoalbum' );
	return $side ?: null;
}

function lene_app_hent_galleri(): ?array {
	$side = lene_app_fotoalbum_side();
	if ( ! $side ) {
		return null;
	}
	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/galleri' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$billeder = array();
		foreach ( $blok['innerBlocks'] ?? array() as $child ) {
			if ( 'core/image' !== ( $child['blockName'] ?? '' ) ) {
				continue;
			}
			$id = (int) ( $child['attrs']['id'] ?? 0 );
			if ( ! $id ) {
				continue;
			}
			$billeder[] = array(
				'id'  => $id,
				'url' => wp_get_attachment_image_url( $id, 'medium' ) ?: '',
				'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}
		return array( 'billeder' => $billeder );
	}
	return null;
}

function lene_app_byg_galleri_billede_markup( int $attachment_id ): string {
	$size  = 'large';
	$src   = wp_get_attachment_image_url( $attachment_id, $size ) ?: wp_get_attachment_url( $attachment_id );
	$alt   = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$attrs = array(
		'id'              => $attachment_id,
		'sizeSlug'        => $size,
		'linkDestination' => 'none',
	);
	$html = '<figure class="wp-block-image size-' . esc_attr( $size ) . '"><img src="' . esc_url( (string) $src ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . $attachment_id . '"/></figure>';
	return '<!-- wp:core/image ' . wp_json_encode( $attrs ) . ' -->' . "\n" . $html . "\n" . '<!-- /wp:core/image -->';
}

function lene_app_byg_galleri_markup( array $attrs, array $billede_ider ): string {
	$billeder_markup = '';
	foreach ( $billede_ider as $id ) {
		$id = (int) $id;
		if ( $id && 'attachment' === get_post_type( $id ) ) {
			$billeder_markup .= lene_app_byg_galleri_billede_markup( $id ) . "\n\n";
		}
	}

	$overskrift = '';
	if ( ! empty( $attrs['eyebrow'] ) || ! empty( $attrs['titel'] ) ) {
		$overskrift = '<div class="section__head"><div>';
		if ( ! empty( $attrs['eyebrow'] ) ) {
			$overskrift .= '<p class="eyebrow">' . esc_html( (string) $attrs['eyebrow'] ) . '</p>';
		}
		if ( ! empty( $attrs['titel'] ) ) {
			$overskrift .= '<h2>' . esc_html( (string) $attrs['titel'] ) . '</h2>';
		}
		$overskrift .= '</div></div>';
	}

	$kolonner = (int) ( $attrs['kolonner'] ?? 3 );
	$lightbox = ! empty( $attrs['lightbox'] ) ? 'true' : 'false';

	$aabning = '<section class="wp-block-lene-galleri section"><div class="wrap">' . $overskrift . '<div class="galleri" style="--kolonner:' . $kolonner . '" data-lightbox="' . $lightbox . '">';
	$lukning = '</div></div></section>';

	return '<!-- wp:lene/galleri ' . wp_json_encode( $attrs ) . ' -->' . "\n" . $aabning . "\n\n" . $billeder_markup . $lukning . "\n" . '<!-- /wp:lene/galleri -->';
}

/**
 * Gemmer fotoalbummet ud fra en komplet, ordnet liste af attachment-ID'er
 * — tilføjelse, sletning og ombytning af rækkefølge er alle bare "gem den
 * nye ønskede liste", i stedet for tre forskellige operationer.
 */
function lene_app_gem_galleri( array $billede_ider ): ?array {
	$side = lene_app_fotoalbum_side();
	if ( ! $side ) {
		return null;
	}
	$standard = lene_app_blok_standardattrs( 'lene/galleri' );
	$blokke   = parse_blocks( $side->post_content );
	$fundet   = false;

	foreach ( $blokke as $i => $blok ) {
		if ( 'lene/galleri' !== ( $blok['blockName'] ?? '' ) ) {
			continue;
		}
		$nuvaerende   = array_merge( $standard, $blok['attrs'] ?? array() );
		$markup       = lene_app_byg_galleri_markup( $nuvaerende, $billede_ider );
		$blokke[ $i ] = parse_blocks( $markup )[0];
		$fundet       = true;
		break;
	}

	if ( ! $fundet ) {
		return null;
	}

	lene_app_opdater_side_uden_kses(
		array(
			'ID'           => $side->ID,
			'post_content' => serialize_blocks( $blokke ),
		)
	);

	return lene_app_hent_galleri();
}

/**
 * Uploader et nyt billede til mediebiblioteket fra en multipart-formular
 * (samme fremgangsmåde som kernens egen wp/v2/media-endpoint). Tilføjer
 * IKKE billedet til galleriet i sig selv — det gør klienten ved bagefter
 * at kalde PUT /galleri med den nye, ønskede rækkefølge af ID'er.
 */
function lene_app_upload_galleri_billede() {
	if ( empty( $_FILES['fil'] ) ) {
		return new WP_Error( 'mangler_fil', 'Ingen fil modtaget.', array( 'status' => 400 ) );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$side_id       = lene_app_fotoalbum_side()?->ID ?? 0;
	$attachment_id = media_handle_upload( 'fil', $side_id );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	return array(
		'id'  => $attachment_id,
		'url' => wp_get_attachment_image_url( $attachment_id, 'medium' ),
	);
}

/* ---------------------------------------------------------------------
 * Ruter
 * ------------------------------------------------------------------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'lene-app/v1',
			'/hero',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_hero();
						return null === $data ? new WP_Error( 'ikke_fundet', 'Forsiden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$data = lene_app_gem_hero( (array) $req->get_json_params() );
						return null === $data ? new WP_Error( 'ikke_fundet', 'Forsiden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/tidslinje',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_tidslinje();
						return null === $data ? new WP_Error( 'ikke_fundet', '"Hvem er jeg"-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$data = lene_app_gem_tidslinje( (array) $req->get_json_params() );
						return null === $data ? new WP_Error( 'ikke_fundet', '"Hvem er jeg"-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/dagsrytme',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_dagsrytme();
						return null === $data ? new WP_Error( 'ikke_fundet', 'Forsiden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$data = lene_app_gem_dagsrytme( (array) $req->get_json_params() );
						return null === $data ? new WP_Error( 'ikke_fundet', 'Forsiden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/galleri',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						$data = lene_app_hent_galleri();
						return null === $data ? new WP_Error( 'ikke_fundet', 'Fotoalbum-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => function ( WP_REST_Request $req ) {
						$body = (array) $req->get_json_params();
						$data = lene_app_gem_galleri( is_array( $body['billede_ider'] ?? null ) ? $body['billede_ider'] : array() );
						return null === $data ? new WP_Error( 'ikke_fundet', 'Fotoalbum-siden blev ikke fundet.', array( 'status' => 404 ) ) : $data;
					},
					'permission_callback' => 'lene_app_rest_tjek_adgang',
				),
			)
		);

		register_rest_route(
			'lene-app/v1',
			'/galleri/upload',
			array(
				'methods'             => 'POST',
				'callback'            => 'lene_app_upload_galleri_billede',
				'permission_callback' => 'lene_app_rest_tjek_adgang',
			)
		);
	}
);
