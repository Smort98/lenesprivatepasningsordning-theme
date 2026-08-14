<?php
/**
 * Lene-temaet: theme-support, block-registrering og globale hjælpefunktioner.
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'inc/cpt-plads.php' );
require_once get_theme_file_path( 'inc/cpt-lukkedag.php' );
require_once get_theme_file_path( 'inc/billedsamtykke.php' );
require_once get_theme_file_path( 'inc/indstillinger.php' );
require_once get_theme_file_path( 'inc/cpt-henvendelse.php' );
require_once get_theme_file_path( 'inc/formular-handler.php' );
require_once get_theme_file_path( 'inc/logo.php' );

/**
 * Theme support.
 */
function lene_theme_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'assets/css/base.css' );

	register_nav_menus(
		array(
			'primary' => 'Primær navigation',
		)
	);
}
add_action( 'after_setup_theme', 'lene_theme_setup' );

/**
 * Global CSS (tokens + chrome) — samme fil til forside og editor-canvas,
 * så et attributskift altid ser ens ud begge steder.
 */
function lene_enqueue_assets() {
	wp_enqueue_style( 'dashicons' );

	$base_path = get_theme_file_path( 'assets/css/base.css' );
	wp_enqueue_style( 'lene-base', get_theme_file_uri( 'assets/css/base.css' ), array(), file_exists( $base_path ) ? filemtime( $base_path ) : false );

	$nav_path = get_theme_file_path( 'assets/js/navigation.js' );
	wp_enqueue_script( 'lene-navigation', get_theme_file_uri( 'assets/js/navigation.js' ), array(), file_exists( $nav_path ) ? filemtime( $nav_path ) : false, true );
}
add_action( 'wp_enqueue_scripts', 'lene_enqueue_assets' );

/**
 * Egen block-kategori, så Lene-sektionerne er samlet i "+"-menuen.
 */
function lene_block_category( array $categories ): array {
	array_unshift(
		$categories,
		array(
			'slug'  => 'lene-sektioner',
			'title' => 'Lene Sektioner',
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'lene_block_category' );

/**
 * 301-redirects for sider hvis indhold er lagt sammen med andre sider
 * under omlægningen fra Kadence. "Vores dag" er lagt ind under "Om Lene",
 * og "Åbningstider" er lagt ind under "Praktisk info" (som foreslået i
 * mockuppen).
 */
function lene_gamle_side_redirects() {
	if ( is_page( 'vores-dag' ) ) {
		wp_safe_redirect( home_url( '/#rytme' ), 301 );
		exit;
	}
	if ( is_page( 'aabningstider' ) ) {
		wp_safe_redirect( home_url( '/praktisk-info/#aabningstider' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'lene_gamle_side_redirects' );

/**
 * Auto-registrér alle blokke bygget med wp-scripts.
 * Hver blok ligger i build/blocks/<navn>/block.json — ingen manuel
 * registrering nødvendig når en ny blok tilføjes under src/blocks/.
 */
function lene_register_blocks() {
	$build_dir = get_theme_file_path( 'build/blocks' );
	if ( ! is_dir( $build_dir ) ) {
		return;
	}
	foreach ( glob( $build_dir . '/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
}
add_action( 'init', 'lene_register_blocks' );
