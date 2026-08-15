<?php
/**
 * Server-render af lene/lukkedage.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$eyebrow   = $attributes['eyebrow'] ?? '';
$titel     = $attributes['titel'] ?? '';
$foot      = $attributes['footTekst'] ?? '';
$perioder  = lene_hent_lukkedage();
$baggrund  = 'sky' === ( $attributes['baggrund'] ?? 'paper' ) ? 'sky' : 'paper';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'id'    => 'lukkedage',
		'class' => "lene-lukkedage section section--{$baggrund}",
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
			</div>
		</div>

		<?php if ( empty( $perioder ) ) : ?>
			<p class="lede">Der er ingen kommende lukkeperioder lige nu.</p>
		<?php else : ?>
			<ul class="closed">
				<?php foreach ( $perioder as $i => $periode ) : ?>
					<li class="closed__row<?php echo 0 === $i ? ' is-next' : ''; ?>">
						<p class="closed__uge"><?php echo esc_html( $periode['periode'] ); ?></p>
						<div class="closed__body">
							<p class="closed__dates"><?php echo esc_html( $periode['datoer'] ); ?></p>
							<?php if ( $periode['aarsag'] ) : ?>
								<p class="closed__note"><?php echo esc_html( $periode['aarsag'] ); ?></p>
							<?php endif; ?>
						</div>
						<span class="closed__flag"><?php echo 0 === $i ? 'Næste lukkeuge' : 'Lukket'; ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $foot ) : ?>
			<p class="closed__foot"><?php echo esc_html( $foot ); ?></p>
		<?php endif; ?>
	</div>
</section>
