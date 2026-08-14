<?php
/**
 * "Lene-appen" — en lille installerbar PWA på /app/, adskilt fra det
 * almindelige tema (intet header/footer/nav), hvor man kan redigere
 * pladser, åbningstider, priser og lukkedage fra telefonen uden at
 * skulle igennem hele wp-admin. Gates bag almindeligt WP-login —
 * ingen ny konto-infrastruktur, bare det, der allerede findes.
 *
 * /app/               -> app-skallen (HTML/JS)
 * /app/manifest.json  -> PWA-manifest
 * /app/sw.js          -> service worker (app-shell-cache, aldrig API-kald)
 */

defined( 'ABSPATH' ) || exit;

function lene_app_rewrite_regler() {
	add_rewrite_rule( '^app/manifest\.json$', 'index.php?lene_app_route=manifest', 'top' );
	add_rewrite_rule( '^app/sw\.js$', 'index.php?lene_app_route=sw', 'top' );
	add_rewrite_rule( '^app/?$', 'index.php?lene_app_route=shell', 'top' );
}
add_action( 'init', 'lene_app_rewrite_regler' );

function lene_app_query_vars( array $vars ): array {
	$vars[] = 'lene_app_route';
	return $vars;
}
add_filter( 'query_vars', 'lene_app_query_vars' );

/**
 * Rewrite-reglerne skal flushes én gang, når de tilføjes/ændres.
 */
function lene_app_flush_ved_temaskift() {
	lene_app_rewrite_regler();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lene_app_flush_ved_temaskift' );

/**
 * /app/manifest.json og /app/sw.js er filer, ikke sider — uden dette
 * forsøger WordPress' egen kanoniske omdirigering at tilføje et
 * afsluttende skråstreg (samme mekanisme som for almindelige
 * side-URL'er), hvilket en service worker-registrering nægter at følge.
 */
function lene_app_undgaa_kanonisk_redirect( $redirect_url ) {
	return get_query_var( 'lene_app_route' ) && 'shell' !== get_query_var( 'lene_app_route' ) ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'lene_app_undgaa_kanonisk_redirect' );

function lene_app_haandter_route() {
	$route = get_query_var( 'lene_app_route' );
	if ( ! $route ) {
		return;
	}
	if ( 'manifest' === $route ) {
		lene_app_output_manifest();
	} elseif ( 'sw' === $route ) {
		lene_app_output_service_worker();
	} elseif ( 'shell' === $route ) {
		lene_app_output_shell();
	}
	exit;
}
add_action( 'template_redirect', 'lene_app_haandter_route' );

function lene_app_output_manifest(): void {
	nocache_headers();
	header( 'Content-Type: application/manifest+json' );
	echo wp_json_encode(
		array(
			'name'             => 'Lene-appen',
			'short_name'       => 'Lene-appen',
			'description'      => 'Redigér pladser, åbningstider, priser og lukkedage.',
			'start_url'        => home_url( '/app/' ),
			'scope'            => home_url( '/app/' ),
			'display'          => 'standalone',
			'background_color' => '#1B3326',
			'theme_color'      => '#1B3326',
			'lang'             => 'da',
			'icons'            => array(
				array(
					'src'     => get_theme_file_uri( 'assets/app/icon-192.png' ),
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'any maskable',
				),
				array(
					'src'     => get_theme_file_uri( 'assets/app/icon-512.png' ),
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'any maskable',
				),
			),
		)
	);
}

function lene_app_output_service_worker(): void {
	nocache_headers();
	header( 'Content-Type: application/javascript; charset=UTF-8' );
	$assets = array_values(
		array(
			home_url( '/app/' ),
			get_theme_file_uri( 'assets/app/app.css' ),
			get_theme_file_uri( 'assets/app/app.js' ),
			get_theme_file_uri( 'assets/app/icon-192.png' ),
			get_theme_file_uri( 'assets/app/icon-512.png' ),
		)
	);
	?>
const CACHE = 'lene-app-v1';
const ASSETS = <?php echo wp_json_encode( $assets ); ?>;

self.addEventListener( 'install', ( event ) => {
	event.waitUntil( caches.open( CACHE ).then( ( cache ) => cache.addAll( ASSETS ) ) );
	self.skipWaiting();
} );

self.addEventListener( 'activate', ( event ) => {
	event.waitUntil(
		caches.keys().then( ( keys ) => Promise.all( keys.filter( ( k ) => k !== CACHE ).map( ( k ) => caches.delete( k ) ) ) )
	);
	self.clients.claim();
} );

self.addEventListener( 'fetch', ( event ) => {
	// Aldrig cache API-kald eller ikke-GET — appen er online-først, kun
	// selve app-skallen (HTML/CSS/JS/ikoner) caches til hurtig opstart.
	if ( event.request.method !== 'GET' || event.request.url.includes( '/wp-json/' ) ) {
		return;
	}
	event.respondWith( caches.match( event.request ).then( ( cached ) => cached || fetch( event.request ) ) );
} );
	<?php
}

function lene_app_output_shell(): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( home_url( '/app/' ) ) );
		exit;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Din bruger har ikke adgang til denne app.', 'Ingen adgang', array( 'response' => 403 ) );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );

	$version = wp_get_theme()->get( 'Version' ) ?: '1.0';

	$standard_farver = array(
		'pine'     => '#1B3326',
		'sun'      => '#F7C24B',
		'sun-deep' => '#D99A0B',
		'sky'      => '#E9EFE8',
		'paper'    => '#FFFFFF',
		'stone'    => '#6B7C6F',
		'berry'    => '#B4576B',
	);
	$farver = $standard_farver;
	foreach ( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) as $f ) {
		if ( isset( $f['slug'], $f['color'] ) && array_key_exists( $f['slug'], $farver ) ) {
			$farver[ $f['slug'] ] = $f['color'];
		}
	}
	?>
<!doctype html>
<html lang="da">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
	<title>Lene-appen</title>
	<link rel="manifest" href="<?php echo esc_url( home_url( '/app/manifest.json' ) ); ?>">
	<meta name="theme-color" content="#1B3326">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Lene-appen">
	<link rel="apple-touch-icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/app/icon-192.png' ) ); ?>">
	<link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/app/icon-192.png' ) ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/app/app.css' ) ); ?>?v=<?php echo esc_attr( $version ); ?>">
	<style>
		:root{
			--pine: <?php echo esc_html( $farver['pine'] ); ?>;
			--sun: <?php echo esc_html( $farver['sun'] ); ?>;
			--sun-deep: <?php echo esc_html( $farver['sun-deep'] ); ?>;
			--sky: <?php echo esc_html( $farver['sky'] ); ?>;
			--paper: <?php echo esc_html( $farver['paper'] ); ?>;
			--stone: <?php echo esc_html( $farver['stone'] ); ?>;
			--berry: <?php echo esc_html( $farver['berry'] ); ?>;
		}
	</style>
</head>
<body>
	<div id="app">Indlæser…</div>
	<script>
		window.LENE_APP = {
			restBase: <?php echo wp_json_encode( esc_url_raw( rest_url( 'lene-app/v1' ) ) ); ?>,
			nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
			logoutUrl: <?php echo wp_json_encode( wp_logout_url( home_url( '/app/' ) ) ); ?>,
			brugerNavn: <?php echo wp_json_encode( wp_get_current_user()->display_name ); ?>
		};
	</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/app/app.js' ) ); ?>?v=<?php echo esc_attr( $version ); ?>"></script>
	<script>
		if ( 'serviceWorker' in navigator ) {
			window.addEventListener( 'load', function () {
				navigator.serviceWorker.register( '<?php echo esc_url( home_url( '/app/sw.js' ) ); ?>', { scope: '<?php echo esc_url( home_url( '/app/' ) ); ?>' } );
			} );
		}
	</script>
</body>
</html>
	<?php
}
