<?php
/**
 * Server-render af lene/hero.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$overrubrik   = $attributes['overrubrik'] ?? '';
$titel        = $attributes['titel'] ?? '';
$tekst        = $attributes['tekst'] ?? '';
$knap1_tekst  = $attributes['knapPrimaerTekst'] ?? '';
$knap1_link   = $attributes['knapPrimaerLink'] ?? '#';
$knap2_tekst  = $attributes['knapSekundaerTekst'] ?? '';
$knap2_link   = $attributes['knapSekundaerLink'] ?? '#';
$fakta        = is_array( $attributes['fakta'] ?? null ) ? $attributes['fakta'] : array();
$billede      = $attributes['billede'] ?? array();
$vis_naeste   = $attributes['visNaestePlads'] ?? true;
$vis_aabningstider = $attributes['visAabningstider'] ?? true;
$vis_lukkedage = $attributes['visLukkedage'] ?? true;

$naeste = $vis_naeste ? lene_naeste_ledige_plads() : null;

$aabningstider_status = '';
if ( $vis_aabningstider ) {
	$aabningstider_data   = lene_hent_aabningstider_data();
	$aabningstider_status = lene_aabningstider_beregn_status( $aabningstider_data['dage'] );
}

$naeste_lukkedag = null;
if ( $vis_lukkedage && function_exists( 'lene_hent_lukkedage' ) ) {
	$lukkedage = lene_hent_lukkedage();
	$naeste_lukkedag = $lukkedage[0] ?? null;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-hero hero',
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap hero__grid">
		<div>
			<?php if ( $overrubrik ) : ?>
				<p class="eyebrow"><?php echo esc_html( $overrubrik ); ?></p>
			<?php endif; ?>
			<?php if ( $titel ) : ?>
				<h1><?php echo esc_html( $titel ); ?></h1>
			<?php endif; ?>
			<?php if ( $tekst ) : ?>
				<p class="lede"><?php echo esc_html( $tekst ); ?></p>
			<?php endif; ?>
			<?php if ( $knap1_tekst || $knap2_tekst ) : ?>
				<div class="hero__cta">
					<?php if ( $knap1_tekst ) : ?>
						<a class="btn btn--primary" href="<?php echo esc_url( home_url( $knap1_link ) ); ?>"><?php echo esc_html( $knap1_tekst ); ?></a>
					<?php endif; ?>
					<?php if ( $knap2_tekst ) : ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( home_url( $knap2_link ) ); ?>"><?php echo esc_html( $knap2_tekst ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $fakta ) || $aabningstider_status || $naeste_lukkedag ) : ?>
				<ul class="hero__facts">
					<?php foreach ( $fakta as $punkt ) : ?>
						<?php if ( '' === trim( (string) $punkt ) ) continue; ?>
						<li><?php echo esc_html( $punkt ); ?></li>
					<?php endforeach; ?>
					<?php if ( $aabningstider_status ) : ?>
						<li><?php echo esc_html( $aabningstider_status ); ?></li>
					<?php endif; ?>
					<?php if ( $naeste_lukkedag ) : ?>
						<li>
							<a href="<?php echo esc_url( home_url( '/praktisk-info/#lukkedage' ) ); ?>">
								<?php echo esc_html( ( $naeste_lukkedag['aarsag'] ?: $naeste_lukkedag['periode'] ) . ': ' . $naeste_lukkedag['datoer'] ); ?>
							</a>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="hero__media">
			<div class="frame">
				<?php if ( ! empty( $billede['url'] ) ) : ?>
					<img src="<?php echo esc_url( $billede['url'] ); ?>" alt="<?php echo esc_attr( $billede['alt'] ?? '' ); ?>">
				<?php endif; ?>
			</div>
			<?php if ( $naeste ) : ?>
				<div class="next-card">
					<small>Næste ledige plads</small>
					<strong><?php echo esc_html( lene_dansk_dato( $naeste['timestamp'] ) ); ?></strong>
					<span>Skriv til Lene for venteliste</span>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
