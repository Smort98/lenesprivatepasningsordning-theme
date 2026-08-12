<?php
/**
 * Server-render af lene/kontaktkort.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$eyebrow      = $attributes['eyebrow'] ?? '';
$titel        = $attributes['titel'] ?? '';
$tekst        = $attributes['tekst'] ?? '';
$vis_formular = $attributes['visFormular'] ?? true;
$kontakt      = lene_hent_kontaktoplysninger();

$status = isset( $_GET['henvendelse'] ) ? sanitize_key( wp_unslash( $_GET['henvendelse'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-kontaktkort section section--sky',
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap contact">
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
			<ul class="contact__list">
				<li><span>Navn</span><div><?php echo esc_html( $kontakt['navn'] ); ?></div></li>
				<li><span>Adresse</span><div><?php echo esc_html( $kontakt['adresse'] ); ?></div></li>
				<li><span>Telefon</span><div><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $kontakt['telefon'] ) ); ?>"><?php echo esc_html( $kontakt['telefon'] ); ?></a></div></li>
				<li><span>E-mail</span><div><a href="mailto:<?php echo esc_attr( $kontakt['email'] ); ?>"><?php echo esc_html( $kontakt['email'] ); ?></a></div></li>
			</ul>
		</div>
		<?php if ( $vis_formular ) : ?>
			<div class="form">
				<?php if ( 'tak' === $status ) : ?>
					<p class="form__besked form__besked--ok">Tak for din besked! Lene vender tilbage hurtigst muligt.</p>
				<?php elseif ( 'fejl' === $status ) : ?>
					<p class="form__besked form__besked--fejl">Der gik noget galt — prøv igen, eller skriv direkte til <?php echo esc_html( $kontakt['email'] ); ?>.</p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lene_henvendelse">
					<input type="hidden" name="kilde" value="kontakt">
					<?php wp_nonce_field( 'lene_formular_indsend', 'lene_formular_nonce' ); ?>
					<p class="lene-honeypot" aria-hidden="true">
						<label for="kk_web">Website</label>
						<input type="text" id="kk_web" name="lene_web" tabindex="-1" autocomplete="off">
					</p>
					<div class="field">
						<label for="kk_navn">Dit navn</label>
						<input id="kk_navn" name="navn" type="text" placeholder="Fx Maria Jensen" required>
					</div>
					<div class="field">
						<label for="kk_email">E-mail</label>
						<input id="kk_email" name="email" type="email" placeholder="dig@eksempel.dk" required>
					</div>
					<div class="field">
						<label for="kk_start">Ønsket startdato</label>
						<input id="kk_start" name="oensket_start" type="text" placeholder="Fx august 2027">
					</div>
					<div class="field">
						<label for="kk_besked">Besked</label>
						<textarea id="kk_besked" name="besked" placeholder="Fortæl lidt om dit barn"></textarea>
					</div>
					<button class="btn btn--primary" type="submit">Send besked</button>
				</form>
			</div>
		<?php endif; ?>
	</div>
</section>
