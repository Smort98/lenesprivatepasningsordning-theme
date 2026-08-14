<?php
/**
 * Logo-ikonet i header — flere tegnede varianter at vælge imellem.
 * Alle varianter bruger var(--pine) osv., så ikonet automatisk følger
 * det aktive farvetema (theme.json-stilart).
 */

defined( 'ABSPATH' ) || exit;

const LENE_LOGO_OPTION = 'lene_logo_variant';

function lene_logo_varianter(): array {
	return array(
		'mobil'     => 'Babygynge',
		'hus'       => 'Hus & hjerte',
		'sol'       => 'Sol',
		'monogram'  => 'Monogram',
	);
}

function lene_hent_logo_variant(): string {
	$gemt = get_option( LENE_LOGO_OPTION, 'mobil' );
	return array_key_exists( $gemt, lene_logo_varianter() ) ? $gemt : 'mobil';
}

/**
 * Returnerer selve <svg>-markeringen for en variant. $farver kan sættes
 * til konkrete hex-koder (bruges i wp-admin-forhåndsvisningen, som ikke
 * har temaets CSS-variabler til rådighed) — ellers bruges var(--pine) osv.,
 * så ikonet følger det aktive farvetema på selve sitet.
 */
function lene_logo_svg( string $variant, ?array $farver = null ): string {
	$c = wp_parse_args(
		$farver,
		array(
			'pine'     => 'var(--pine)',
			'sun'      => 'var(--sun)',
			'sun-deep' => 'var(--sun-deep)',
			'display'  => 'var(--display)',
		)
	);

	switch ( $variant ) {
		case 'hus':
			return sprintf(
				'<svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true">
					<path d="M10 30 L32 12 L54 30" fill="none" stroke="%1$s" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
					<rect x="16" y="30" width="32" height="22" rx="3" fill="none" stroke="%1$s" stroke-width="2.4"/>
					<path d="M32 45 C26 39 21 39 21 34 C21 30.5 24.5 28.5 27.5 30.5 C29 31.5 31 33.5 32 35.5 C33 33.5 35 31.5 36.5 30.5 C39.5 28.5 43 30.5 43 34 C43 39 38 39 32 45 Z" fill="%2$s" stroke="%3$s" stroke-width="1.4"/>
				</svg>',
				esc_attr( $c['pine'] ),
				esc_attr( $c['sun'] ),
				esc_attr( $c['sun-deep'] )
			);

		case 'sol':
			return sprintf(
				'<svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true">
					<g stroke="%1$s" stroke-width="3" stroke-linecap="round">
						<line x1="47" y1="32" x2="52" y2="32"/>
						<line x1="42.6" y1="42.6" x2="46.1" y2="46.1"/>
						<line x1="32" y1="47" x2="32" y2="52"/>
						<line x1="21.4" y1="42.6" x2="17.9" y2="46.1"/>
						<line x1="17" y1="32" x2="12" y2="32"/>
						<line x1="21.4" y1="21.4" x2="17.9" y2="17.9"/>
						<line x1="32" y1="17" x2="32" y2="12"/>
						<line x1="42.6" y1="21.4" x2="46.1" y2="17.9"/>
					</g>
					<circle cx="32" cy="32" r="13" fill="%2$s" stroke="%3$s" stroke-width="2.2"/>
				</svg>',
				esc_attr( $c['pine'] ),
				esc_attr( $c['sun'] ),
				esc_attr( $c['sun-deep'] )
			);

		case 'monogram':
			return sprintf(
				'<svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true">
					<rect x="8" y="8" width="48" height="48" rx="16" fill="none" stroke="%1$s" stroke-width="3"/>
					<text x="32" y="44" font-family="%2$s, Georgia, serif" font-size="32" font-weight="700" font-style="italic" text-anchor="middle" fill="%3$s">L</text>
				</svg>',
				esc_attr( $c['pine'] ),
				esc_attr( $c['display'] ),
				esc_attr( $c['sun-deep'] )
			);

		case 'mobil':
		default:
			return sprintf(
				'<svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true">
					<path d="M12 20 Q32 10 52 20" fill="none" stroke="%1$s" stroke-width="3.4" stroke-linecap="round"/>
					<line x1="20" y1="19" x2="20" y2="28" stroke="%1$s" stroke-width="2.2"/>
					<line x1="44" y1="19" x2="44" y2="26" stroke="%1$s" stroke-width="2.2"/>
					<circle cx="20" cy="37" r="10" fill="%2$s" stroke="%3$s" stroke-width="2"/>
					<rect x="35" y="27" width="18" height="18" rx="5" fill="none" stroke="%1$s" stroke-width="2.2" stroke-dasharray="3 3" transform="rotate(8 44 36)"/>
				</svg>',
				esc_attr( $c['pine'] ),
				esc_attr( $c['sun'] ),
				esc_attr( $c['sun-deep'] )
			);
	}
}

function lene_registrer_logo_indstilling() {
	register_setting(
		'lene_indstillinger',
		LENE_LOGO_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $input ) {
				return array_key_exists( $input, lene_logo_varianter() ) ? $input : 'mobil';
			},
			'default'           => 'mobil',
		)
	);

	add_settings_section(
		'lene_logo_sektion',
		'Logo',
		function () {
			echo '<p>Vælg hvilket ikon der vises i menuen. Ikonet skifter automatisk farve efter det farvetema, der er valgt under Redigering → Design → Stilarter.</p>';
		},
		'lene-indstillinger'
	);

	add_settings_field(
		'lene_logo_variant',
		'Ikon',
		function () {
			$valgt = lene_hent_logo_variant();
			$preview_farver = array(
				'pine'     => '#1B3326',
				'sun'      => '#F7C24B',
				'sun-deep' => '#D99A0B',
				'display'  => 'Fraunces',
			);
			echo '<div style="display:flex;gap:22px;flex-wrap:wrap;">';
			foreach ( lene_logo_varianter() as $key => $label ) {
				printf(
					'<label style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px;border:2px solid %1$s;border-radius:12px;cursor:pointer;min-width:90px;">
						<input type="radio" name="%2$s" value="%3$s" %4$s style="margin:0 0 4px;">
						<span>%5$s</span>
						<span>%6$s</span>
					</label>',
					$valgt === $key ? '#D99A0B' : '#ddd',
					esc_attr( LENE_LOGO_OPTION ),
					esc_attr( $key ),
					checked( $valgt, $key, false ),
					lene_logo_svg( $key, $preview_farver ),
					esc_html( $label )
				);
			}
			echo '</div>';
		},
		'lene-indstillinger',
		'lene_logo_sektion'
	);
}
add_action( 'admin_init', 'lene_registrer_logo_indstilling' );

/**
 * Hver farve-stilart i styles/*.json har sin egen unikke "pine"-farve.
 * Bruges til at slå op hvilken stilart der er aktiv lige nu, uden at
 * skulle læse selve JSON-filerne (den aktive stilart er en overskrivning
 * gemt i en wp_global_styles-post, ikke nødvendigvis identisk med filen).
 */
function lene_farvetema_noegler(): array {
	return array(
		'371D27' => 'bordeaux',
		'1B3A42' => 'havblaa',
		'3D2B1F' => 'ler',
		'362F4A' => 'lavendel',
	);
}

/**
 * Hvilket farvetema er aktivt lige nu? Returnerer 'standard' (temaets
 * grønne theme.json-farver) hvis der ingen overskrivning er, ellers
 * nøglen for den matchende stilart i styles/*.json.
 */
function lene_hent_aktivt_farvetema(): string {
	$post = get_page_by_path( 'wp-global-styles-' . get_stylesheet(), OBJECT, 'wp_global_styles' );
	if ( ! $post || ! $post->post_content ) {
		return 'standard';
	}
	$data    = json_decode( $post->post_content, true );
	$palette = $data['settings']['color']['palette']['theme'] ?? array();
	foreach ( $palette as $farve ) {
		if ( 'pine' === ( $farve['slug'] ?? '' ) ) {
			$hex = strtoupper( ltrim( $farve['color'] ?? '', '#' ) );
			return lene_farvetema_noegler()[ $hex ] ?? 'standard';
		}
	}
	return 'standard';
}

/**
 * Favoritikonet (browser-fane) kan ikke bruge var(--pine) osv., da det
 * vises uden for sitets CSS — så hver ikon-variant findes som ét
 * færdigtegnet PNG pr. farvetema i assets/favicons/ (fx "hus.png" for
 * standard-temaet, "hus-bordeaux.png", "hus-havblaa.png" osv.). Når man
 * skifter ikon under Indstillinger, ELLER skifter farvetema i Site
 * Editor, importeres/genbruges det matchende PNG som medie og sættes
 * automatisk som site_icon.
 */
function lene_favicon_noegle( string $variant ): string {
	$tema = lene_hent_aktivt_farvetema();
	return 'standard' === $tema ? $variant : "{$variant}-{$tema}";
}

function lene_favicon_attachment_id( string $noegle ): int {
	$eksisterende = get_posts(
		array(
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'meta_key'       => '_lene_favicon_variant',
			'meta_value'     => $noegle,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $eksisterende ) ) {
		return (int) $eksisterende[0];
	}

	$kilde = get_theme_file_path( "assets/favicons/{$noegle}.png" );
	if ( ! file_exists( $kilde ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$upload_dir = wp_upload_dir();
	$filnavn    = wp_unique_filename( $upload_dir['path'], "favicon-{$noegle}.png" );
	$destination = $upload_dir['path'] . '/' . $filnavn;

	if ( ! copy( $kilde, $destination ) ) {
		return 0;
	}

	$temanavne = array(
		'bordeaux' => 'bordeaux',
		'havblaa'  => 'havblå',
		'ler'      => 'ler',
		'lavendel' => 'lavendel',
	);
	$variant = $noegle;
	$tema_titel = '';
	foreach ( $temanavne as $tema_noegle => $tema_navn ) {
		if ( str_ends_with( $noegle, "-{$tema_noegle}" ) ) {
			$variant    = substr( $noegle, 0, -strlen( "-{$tema_noegle}" ) );
			$tema_titel = ", {$tema_navn}";
			break;
		}
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Site-ikon (' . ( lene_logo_varianter()[ $variant ] ?? $variant ) . $tema_titel . ')',
			'post_status'    => 'inherit',
		),
		$destination
	);

	if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $destination ) );
		update_post_meta( $attachment_id, '_lene_favicon_variant', $noegle );
		return (int) $attachment_id;
	}

	return 0;
}

function lene_synkroniser_favicon_nu() {
	$attachment_id = lene_favicon_attachment_id( lene_favicon_noegle( lene_hent_logo_variant() ) );
	if ( $attachment_id ) {
		update_option( 'site_icon', $attachment_id );
	}
}

function lene_synkroniser_favicon_ved_ikonskift( $old_value, $value ) {
	if ( $old_value === $value ) {
		return;
	}
	lene_synkroniser_favicon_nu();
}
add_action( 'update_option_' . LENE_LOGO_OPTION, 'lene_synkroniser_favicon_ved_ikonskift', 10, 2 );

/**
 * Global stilart (farvetema) gemmes som en revision af wp_global_styles-
 * indlægget, når man klikker en anden stilart i Site Editor. Synkroniser
 * favicon igen, så den følger med til det nye farvetema.
 */
function lene_synkroniser_favicon_ved_temaskift( $post_id, $post ) {
	if ( 'wp_global_styles' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( 'wp-global-styles-' . get_stylesheet() !== $post->post_name ) {
		return;
	}
	lene_synkroniser_favicon_nu();
}
add_action( 'save_post', 'lene_synkroniser_favicon_ved_temaskift', 20, 2 );
