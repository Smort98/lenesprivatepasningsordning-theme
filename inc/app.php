<?php
/**
 * "LenesPrivatePasningsordning" — en lille installerbar PWA på /app/, adskilt fra det
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
		if ( isset( $_POST['lene_app_login'] ) ) {
			lene_app_haandter_login_forsoeg();
		}
		lene_app_output_shell();
	}
	exit;
}
add_action( 'template_redirect', 'lene_app_haandter_route' );

/**
 * Temaets aktive farver, brugt til både login-siden og selve app-skallen.
 */
function lene_app_hent_farver(): array {
	$farver = array(
		'pine'     => '#1B3326',
		'sun'      => '#F7C24B',
		'sun-deep' => '#D99A0B',
		'sky'      => '#E9EFE8',
		'paper'    => '#FFFFFF',
		'stone'    => '#6B7C6F',
		'berry'    => '#B4576B',
	);
	foreach ( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) as $f ) {
		if ( isset( $f['slug'], $f['color'] ) && array_key_exists( $f['slug'], $farver ) ) {
			$farver[ $f['slug'] ] = $f['color'];
		}
	}
	return $farver;
}

/**
 * App-ikonet (til hjemmeskærm/manifest) følger automatisk den valgte
 * logo-variant og det aktive farvetema — samme princip som favicon-
 * systemet i inc/logo.php, blot med et forudberegnet sæt filer i stedet
 * for medie-bibliotek-attachments (manifestets icons[].src skal bare
 * være en URL, ingen grund til at gå vejen om et vedhæftet medie).
 */
function lene_app_ikon_url( string $storrelse ): string {
	$variant = function_exists( 'lene_hent_logo_variant' ) ? lene_hent_logo_variant() : 'mobil';
	$tema    = function_exists( 'lene_hent_aktivt_farvetema' ) ? lene_hent_aktivt_farvetema() : 'standard';
	$sti     = "assets/app/ikoner/{$variant}-{$tema}-{$storrelse}.png";
	if ( ! file_exists( get_theme_file_path( $sti ) ) ) {
		$sti = "assets/app/ikoner/mobil-standard-{$storrelse}.png";
	}
	return get_theme_file_uri( $sti );
}

/**
 * Behandler indsendelse af login-formularen på /app/. Selve
 * godkendelsen sker via WordPress' egen wp_signon() — vi bygger ikke
 * vores egen autentificering, kun en tilpasset visning omkring den.
 */
function lene_app_haandter_login_forsoeg(): void {
	if ( ! empty( $_POST['lene_app_website'] ) ) {
		return; // Honeypot udfyldt — vis blot loginformularen igen uden fejl.
	}

	$creds = array(
		'user_login'    => sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ),
		'user_password' => $_POST['pwd'] ?? '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- adgangskoder unslashes bevidst ikke, jf. wp-login.php.
		'remember'      => ! empty( $_POST['husk'] ),
	);

	$bruger = wp_signon( $creds, is_ssl() );
	if ( is_wp_error( $bruger ) ) {
		lene_app_output_login( 'Forkert brugernavn eller adgangskode.' );
		exit;
	}
	if ( ! user_can( $bruger, 'edit_posts' ) ) {
		wp_logout();
		lene_app_output_login( 'Denne bruger har ikke adgang til appen.' );
		exit;
	}

	wp_safe_redirect( home_url( '/app/' ) );
	exit;
}

function lene_app_output_login( string $fejl = '' ): void {
	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );

	$farver  = lene_app_hent_farver();
	$version = wp_get_theme()->get( 'Version' ) ?: '1.0';
	?>
<!doctype html>
<html lang="da">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
	<title>Log ind — LenesPrivatePasningsordning</title>
	<link rel="manifest" href="<?php echo esc_url( home_url( '/app/manifest.json' ) ); ?>">
	<meta name="theme-color" content="<?php echo esc_attr( $farver['pine'] ); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="LenesPrivatePasningsordning">
	<link rel="apple-touch-icon" href="<?php echo esc_url( lene_app_ikon_url( '192' ) ); ?>">
	<link rel="icon" href="<?php echo esc_url( lene_app_ikon_url( '192' ) ); ?>">
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
<body class="login-krop">
	<div class="login-side">
		<div class="login-kort">
			<div class="login-ikon">
				<svg viewBox="0 0 64 64" aria-hidden="true">
					<path d="M10 30 L32 12 L54 30" fill="none" stroke="var(--sun)" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round"/>
					<rect x="16" y="30" width="32" height="22" rx="3" fill="none" stroke="var(--sun)" stroke-width="2.6"/>
					<path d="M32 45 C26 39 21 39 21 34 C21 30.5 24.5 28.5 27.5 30.5 C29 31.5 31 33.5 32 35.5 C33 33.5 35 31.5 36.5 30.5 C39.5 28.5 43 30.5 43 34 C43 39 38 39 32 45 Z" fill="var(--sun)" stroke="var(--sun-deep)" stroke-width="1.4"/>
				</svg>
			</div>
			<h1>LenesPrivatePasningsordning</h1>
			<p class="login-undertekst">Log ind for at redigere pladser, åbningstider, priser og lukkedage.</p>
			<?php if ( $fejl ) : ?>
				<p class="login-fejl"><?php echo esc_html( $fejl ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( home_url( '/app/' ) ); ?>">
				<input type="hidden" name="lene_app_login" value="1">
				<p style="position:absolute;left:-9999px;" aria-hidden="true">
					<label>Lad dette felt stå tomt<input type="text" name="lene_app_website" tabindex="-1" autocomplete="off"></label>
				</p>
				<div class="login-felt">
					<label for="log">Brugernavn eller e-mail</label>
					<input type="text" id="log" name="log" autocomplete="username" required autofocus>
				</div>
				<div class="login-felt">
					<label for="pwd">Adgangskode</label>
					<input type="password" id="pwd" name="pwd" autocomplete="current-password" required>
				</div>
				<label class="login-husk"><input type="checkbox" name="husk" checked> Forbliv logget ind</label>
				<button type="submit" class="knap knap--primaer knap--fuld-bredde">Log ind</button>
			</form>
			<a class="login-glemt" href="<?php echo esc_url( wp_lostpassword_url( home_url( '/app/' ) ) ); ?>">Glemt adgangskode?</a>
		</div>
	</div>
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

function lene_app_output_manifest(): void {
	nocache_headers();
	header( 'Content-Type: application/manifest+json' );
	$farver = lene_app_hent_farver();
	echo wp_json_encode(
		array(
			'name'             => 'LenesPrivatePasningsordning',
			'short_name'       => 'LenesPrivatePasningsordning',
			'description'      => 'Redigér pladser, åbningstider, priser og lukkedage.',
			'start_url'        => home_url( '/app/' ),
			'scope'            => home_url( '/app/' ),
			'display'          => 'standalone',
			'background_color' => $farver['pine'],
			'theme_color'      => $farver['pine'],
			'lang'             => 'da',
			'icons'            => array(
				array(
					'src'     => lene_app_ikon_url( '192' ),
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'any maskable',
				),
				array(
					'src'     => lene_app_ikon_url( '512' ),
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
			lene_app_ikon_url( '192' ),
			lene_app_ikon_url( '512' ),
		)
	);
	?>
const CACHE = 'lene-app-v2';
const ASSETS = <?php echo wp_json_encode( $assets ); ?>;
const SHELL_URL = <?php echo wp_json_encode( home_url( '/app/' ) ); ?>;

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
	// Aldrig cache API-kald eller ikke-GET.
	if ( event.request.method !== 'GET' || event.request.url.includes( '/wp-json/' ) ) {
		return;
	}
	// Selve app-skallen afhænger af login-status og indeholder en nonce,
	// der udløber — den skal ALTID hentes friskt fra serveren (ellers
	// virker "genindlæs" ikke, når sessionen er udløbet, fordi man bare
	// får den samme forældede side fra cachen igen). Cache bruges kun
	// som nødløsning, hvis man reelt er offline.
	if ( event.request.mode === 'navigate' || event.request.url === SHELL_URL ) {
		event.respondWith( fetch( event.request ).catch( () => caches.match( event.request ) ) );
		return;
	}
	// Statiske filer (css/js/ikoner) ændrer sig ikke med login-status —
	// fint at servere fra cache først for hurtig opstart.
	event.respondWith( caches.match( event.request ).then( ( cached ) => cached || fetch( event.request ) ) );
} );
	<?php
}

function lene_app_output_shell(): void {
	if ( ! is_user_logged_in() ) {
		lene_app_output_login();
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Din bruger har ikke adgang til denne app.', 'Ingen adgang', array( 'response' => 403 ) );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );

	$version = wp_get_theme()->get( 'Version' ) ?: '1.0';
	$farver  = lene_app_hent_farver();
	?>
<!doctype html>
<html lang="da">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
	<title>LenesPrivatePasningsordning</title>
	<link rel="manifest" href="<?php echo esc_url( home_url( '/app/manifest.json' ) ); ?>">
	<meta name="theme-color" content="<?php echo esc_attr( $farver['pine'] ); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="LenesPrivatePasningsordning">
	<link rel="apple-touch-icon" href="<?php echo esc_url( lene_app_ikon_url( '192' ) ); ?>">
	<link rel="icon" href="<?php echo esc_url( lene_app_ikon_url( '192' ) ); ?>">
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
			logoutUrl: <?php echo wp_json_encode( esc_url_raw( html_entity_decode( wp_logout_url( home_url( '/app/' ) ) ) ) ); ?>,
			brugerNavn: <?php echo wp_json_encode( wp_get_current_user()->display_name ); ?>
		};
	</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/app/app.js' ) ); ?>?v=<?php echo esc_attr( $version ); ?>"></script>
	<script>
		if ( 'serviceWorker' in navigator ) {
			// Hvis en tidligere installeret (forældet) service worker
			// opdateres i baggrunden og overtager siden, genindlæser vi
			// automatisk én gang — ellers ville en bruger med en gammel
			// SW skulle klikke "genindlæs" to gange for at få den friske
			// version, i stedet for at det bare virker første gang.
			navigator.serviceWorker.addEventListener( 'controllerchange', function () {
				window.location.reload();
			} );
			window.addEventListener( 'load', function () {
				navigator.serviceWorker.register( '<?php echo esc_url( home_url( '/app/sw.js' ) ); ?>', { scope: '<?php echo esc_url( home_url( '/app/' ) ); ?>' } );
			} );
		}
	</script>
</body>
</html>
	<?php
}
