<?php
/**
 * "Drift" — samlet admin-side for Pladser, Lukkedage og Henvendelser,
 * i samme .sadmin-designsystem som Kontaktoplysninger og cookie-samtykke.
 * Erstatter de tre CPT'ers egne topmenupunkter (se show_in_menu=false i
 * hver af inc/cpt-plads.php, inc/cpt-lukkedag.php, inc/cpt-henvendelse.php)
 * — selve post-redigeringsskærmene er stadig de samme, bare ikke linket
 * fra sidemenuen længere.
 */

defined( 'ABSPATH' ) || exit;

function lene_drift_menu() {
	add_menu_page(
		'Drift',
		'Drift',
		'manage_options',
		'lene-drift',
		'lene_render_drift_side',
		'dashicons-clipboard',
		25
	);
}
add_action( 'admin_menu', 'lene_drift_menu' );

function lene_drift_admin_assets( string $hook ) {
	if ( 'toplevel_page_lene-drift' !== $hook ) {
		return;
	}
	$version = wp_get_theme()->get( 'Version' ) ?: '1.0';
	wp_enqueue_style( 'lene-sadmin', get_theme_file_uri( 'assets/css/sadmin.css' ), array(), $version );

	$css = ':root{';
	foreach ( lene_admin_sadmin_farver() as $noegle => $vaerdi ) {
		$css .= esc_html( $noegle ) . ':' . esc_html( $vaerdi ) . ';';
	}
	$css .= '}';
	wp_add_inline_style( 'lene-sadmin', $css );
}
add_action( 'admin_enqueue_scripts', 'lene_drift_admin_assets' );

function lene_drift_badge_klasse( string $status ): string {
	return in_array( $status, array( 'ledig', 'delvis' ), true ) ? 'sadmin-badge--ok' : 'sadmin-badge--neutral';
}

function lene_render_drift_side() {
	$faner      = array(
		'pladser'      => 'Pladser',
		'lukkedage'    => 'Lukkedage',
		'henvendelser' => 'Henvendelser',
	);
	$aktiv_fane = 'pladser';
	if ( isset( $_GET['fane'] ) ) {
		$oensket = sanitize_key( wp_unslash( $_GET['fane'] ) );
		if ( isset( $faner[ $oensket ] ) ) {
			$aktiv_fane = $oensket;
		}
	}
	?>
	<div class="wrap sadmin">
		<div class="sadmin-header">
			<div>
				<p class="sadmin-header__eyebrow">Tema-funktion</p>
				<h1>Drift</h1>
				<p>Pladser, lukkedage og henvendelser — de samme data som i telefon-appen på /app/, bare samlet her til overblik og redigering fra computeren.</p>
			</div>
		</div>

		<nav class="sadmin-tabs">
			<?php foreach ( $faner as $noegle => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'lene-drift', 'fane' => $noegle ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $noegle === $aktiv_fane ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'pladser' === $aktiv_fane ) : ?>

			<div class="sadmin-card">
				<h2>Pladser</h2>
				<p class="sadmin-card__intro">Ledige pladser og deres status. Redigér antal, dato og note direkte på hver plads.</p>

				<p style="margin:0 0 16px;">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=plads' ) ); ?>" class="button button-primary">+ Tilføj ny plads</a>
				</p>

				<?php $pladser = lene_hent_pladser(); ?>
				<?php if ( ! $pladser ) : ?>
					<p class="sadmin-empty">Ingen pladser oprettet endnu.</p>
				<?php else : ?>
					<div class="sadmin-table-wrap">
						<table class="sadmin-table">
							<thead>
								<tr>
									<th>Dato</th>
									<th>Pladser</th>
									<th>Status</th>
									<th>Note</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $pladser as $p ) : ?>
									<tr>
										<td><?php echo esc_html( $p['timestamp'] ? lene_dansk_dato( $p['timestamp'] ) : '—' ); ?></td>
										<td><?php echo esc_html( $p['tekst'] ); ?></td>
										<td><span class="sadmin-badge <?php echo esc_attr( lene_drift_badge_klasse( $p['status'] ) ); ?>"><?php echo esc_html( $p['status_label'] ); ?></span></td>
										<td><?php echo esc_html( $p['note'] ?: '—' ); ?></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $p['id'] ) ); ?>">Redigér</a>
											· <a href="<?php echo esc_url( get_delete_post_link( $p['id'] ) ); ?>" onclick="return confirm('Flyt denne plads til papirkurven?');">Slet</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

		<?php elseif ( 'lukkedage' === $aktiv_fane ) : ?>

			<div class="sadmin-card">
				<h2>Lukkedage</h2>
				<p class="sadmin-card__intro">Ferie- og lukkeperioder, som vises på Praktisk info-siden og i hero'en på forsiden.</p>

				<p style="margin:0 0 16px;">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lukkedag' ) ); ?>" class="button button-primary">+ Tilføj ny lukkeperiode</a>
				</p>

				<?php
			// lene_hent_lukkedage() er til FORSIDEN og skjuler bevidst udløbne
			// perioder — her på Drift-siden skal alle kunne ses og redigeres,
			// derfor lene_app_hent_alle_lukkedage() (samme kilde som PWA'en).
			$lukkedage = function_exists( 'lene_app_hent_alle_lukkedage' ) ? lene_app_hent_alle_lukkedage() : array();
			?>
				<?php if ( ! $lukkedage ) : ?>
					<p class="sadmin-empty">Ingen lukkeperioder oprettet endnu.</p>
				<?php else : ?>
					<div class="sadmin-table-wrap">
						<table class="sadmin-table">
							<thead>
								<tr>
									<th>Periode</th>
									<th>Datoer</th>
									<th>Årsag</th>
									<th>Skjules efter</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $lukkedage as $l ) : ?>
									<tr>
										<td><?php echo esc_html( $l['periode'] ?: '(uden titel)' ); ?></td>
										<td><?php echo esc_html( $l['datoer'] ); ?></td>
										<td><?php echo esc_html( $l['aarsag'] ?: '—' ); ?></td>
										<td><?php echo esc_html( $l['skjul_efter'] ? lene_dansk_dato( strtotime( $l['skjul_efter'] ) ) : '—' ); ?></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $l['id'] ) ); ?>">Redigér</a>
											· <a href="<?php echo esc_url( get_delete_post_link( $l['id'] ) ); ?>" onclick="return confirm('Flyt denne lukkeperiode til papirkurven?');">Slet</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

		<?php else : ?>

			<div class="sadmin-card">
				<h2>Henvendelser</h2>
				<p class="sadmin-card__intro">Indsendelser fra kontaktformularen og tilmeldingsformularen. Gemmes altid her, selvom en mail skulle fejle.</p>

				<p style="margin:0 0 16px;">
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'lene_export_henvendelser_csv' ), admin_url( 'admin-post.php' ) ), 'lene_export_henvendelser_csv' ) ); ?>" class="button button-secondary">Eksportér alle til CSV</a>
				</p>

				<?php
				$henvendelser = get_posts(
					array(
						'post_type'      => 'henvendelse',
						'post_status'    => 'publish',
						'posts_per_page' => 50,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'no_found_rows'  => true,
					)
				);
				?>
				<?php if ( ! $henvendelser ) : ?>
					<p class="sadmin-empty">Ingen henvendelser endnu.</p>
				<?php else : ?>
					<div class="sadmin-table-wrap">
						<table class="sadmin-table">
							<thead>
								<tr>
									<th>Dato</th>
									<th>Kilde</th>
									<th>Navn</th>
									<th>E-mail</th>
									<th>Besked</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $henvendelser as $h ) : ?>
									<?php $besked = get_post_meta( $h->ID, 'besked', true ); ?>
									<tr>
										<td><?php echo esc_html( get_the_date( 'j. F Y', $h ) ); ?></td>
										<td><?php echo esc_html( lene_henvendelse_kilde_label( get_post_meta( $h->ID, 'kilde', true ) ) ); ?></td>
										<td><?php echo esc_html( get_post_meta( $h->ID, 'navn', true ) ?: '—' ); ?></td>
										<td><?php echo esc_html( get_post_meta( $h->ID, 'email', true ) ?: '—' ); ?></td>
										<td><?php echo esc_html( $besked ? ( mb_strlen( $besked ) > 60 ? mb_substr( $besked, 0, 60 ) . '…' : $besked ) : '—' ); ?></td>
										<td><a href="<?php echo esc_url( get_edit_post_link( $h->ID ) ); ?>">Vis</a></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ( count( $henvendelser ) >= 50 ) : ?>
						<p class="sadmin-card__intro" style="margin-top:12px;">Viser kun de 50 seneste.</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		<?php endif; ?>
	</div>
	<?php
}
