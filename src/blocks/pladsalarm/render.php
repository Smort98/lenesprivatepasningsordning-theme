<?php
/**
 * Server-render af lene/pladsalarm.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$titel    = $attributes['titel'] ?? '';
$tekst    = $attributes['tekst'] ?? '';
$baggrund = 'sky' === ( $attributes['baggrund'] ?? 'paper' ) ? 'sky' : 'paper';
$status   = isset( $_GET['pladsalarm'] ) ? sanitize_key( wp_unslash( $_GET['pladsalarm'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => "lene-pladsalarm section section--{$baggrund}",
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="pladsalarm__box">
			<?php if ( 'tak' === $status ) : ?>
				<p class="form__besked form__besked--ok" style="margin:0;">Tak! Vi skriver til dig, så snart der er en ledig plads.</p>
			<?php elseif ( 'fejl' === $status ) : ?>
				<p class="form__besked form__besked--fejl" style="margin:0;">Der gik noget galt — tjek at e-mailen er rigtig, og prøv igen.</p>
			<?php else : ?>
				<div>
					<?php if ( $titel ) : ?><h2><?php echo esc_html( $titel ); ?></h2><?php endif; ?>
					<?php if ( $tekst ) : ?><p><?php echo esc_html( $tekst ); ?></p><?php endif; ?>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pladsalarm__form">
					<input type="hidden" name="action" value="lene_pladsalarm">
					<?php wp_nonce_field( 'lene_pladsalarm_indsend', 'lene_pladsalarm_nonce' ); ?>
					<p class="lene-honeypot" aria-hidden="true">
						<label for="pa_web">Website</label>
						<input type="text" id="pa_web" name="lene_web" tabindex="-1" autocomplete="off">
					</p>
					<label for="pa_email" class="screen-reader-text">E-mail</label>
					<input id="pa_email" name="email" type="email" placeholder="din@email.dk" required>
					<button class="btn btn--primary" type="submit">Få besked</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</section>
