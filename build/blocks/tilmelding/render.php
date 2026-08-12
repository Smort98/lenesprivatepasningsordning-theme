<?php
/**
 * Server-render af lene/tilmelding.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$eyebrow = $attributes['eyebrow'] ?? '';
$titel   = $attributes['titel'] ?? '';
$tekst   = $attributes['tekst'] ?? '';

$plads_id     = isset( $_GET['plads'] ) ? absint( $_GET['plads'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$valgt_plads  = null;
$oensket_start = '';
if ( $plads_id && 'plads' === get_post_type( $plads_id ) ) {
	$valgt_plads   = lene_plads_beregn( $plads_id );
	$oensket_start = $valgt_plads['timestamp'] ? lene_dansk_dato( $valgt_plads['timestamp'] ) : '';
}

$status = isset( $_GET['henvendelse'] ) ? sanitize_key( wp_unslash( $_GET['henvendelse'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$kontakt = lene_hent_kontaktoplysninger();

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-tilmelding section',
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="section__head">
			<div>
				<?php if ( $eyebrow ) : ?>
					<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( $titel ) : ?>
					<h2><?php echo esc_html( $titel ); ?></h2>
				<?php endif; ?>
				<?php if ( $tekst ) : ?>
					<p class="lede"><?php echo esc_html( $tekst ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $valgt_plads ) : ?>
			<p class="tilmelding__plads">Du skriver om pladsen <?php echo esc_html( lene_dansk_dato( $valgt_plads['timestamp'] ) ); ?></p>
		<?php endif; ?>

		<div class="form tilmelding__form">
			<?php if ( 'tak' === $status ) : ?>
				<p class="form__besked form__besked--ok">Tak for din tilmelding! Lene vender tilbage hurtigst muligt.</p>
			<?php elseif ( 'fejl' === $status ) : ?>
				<p class="form__besked form__besked--fejl">Der gik noget galt — prøv igen, eller skriv direkte til <?php echo esc_html( $kontakt['email'] ); ?>.</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lene_henvendelse">
				<input type="hidden" name="kilde" value="tilmelding">
				<input type="hidden" name="plads_id" value="<?php echo esc_attr( (string) $plads_id ); ?>">
				<?php wp_nonce_field( 'lene_formular_indsend', 'lene_formular_nonce' ); ?>
				<p class="lene-honeypot" aria-hidden="true">
					<label for="tm_web">Website</label>
					<input type="text" id="tm_web" name="lene_web" tabindex="-1" autocomplete="off">
				</p>
				<div class="field">
					<label for="tm_navn">Dit navn</label>
					<input id="tm_navn" name="navn" type="text" placeholder="Fx Maria Jensen" required>
				</div>
				<div class="field">
					<label for="tm_email">E-mail</label>
					<input id="tm_email" name="email" type="email" placeholder="dig@eksempel.dk" required>
				</div>
				<div class="field">
					<label for="tm_telefon">Telefon</label>
					<input id="tm_telefon" name="telefon" type="tel" placeholder="Fx 12 34 56 78">
				</div>
				<div class="field">
					<label for="tm_foedselsdato">Barnets fødselsdato</label>
					<input id="tm_foedselsdato" name="barnets_foedselsdato" type="date">
				</div>
				<div class="field">
					<label for="tm_start">Ønsket startdato</label>
					<input id="tm_start" name="oensket_start" type="text" value="<?php echo esc_attr( $oensket_start ); ?>" placeholder="Fx august 2027">
				</div>
				<div class="field">
					<label for="tm_besked">Besked</label>
					<textarea id="tm_besked" name="besked" placeholder="Fortæl lidt om dit barn"></textarea>
				</div>
				<button class="btn btn--primary" type="submit">Send tilmelding</button>
			</form>
		</div>
	</div>
</section>
