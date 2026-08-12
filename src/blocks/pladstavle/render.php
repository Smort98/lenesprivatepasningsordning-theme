<?php
/**
 * Server-render af lene/pladstavle.
 *
 * @var array    $attributes Blokkens attributter.
 * @var string   $content    (ubrugt, dynamisk blok uden InnerBlocks)
 * @var WP_Block $block      Blokinstansen.
 */

defined( 'ABSPATH' ) || exit;

$retning       = ( 'lodret' === ( $attributes['retning'] ?? 'vandret' ) ) ? 'lodret' : 'vandret';
$overskrift    = $attributes['overskrift'] ?? 'Pladser og venteliste';
$vis_kun_ledige = ! empty( $attributes['visKunLedige'] );
$vis_prikker    = $attributes['visPrikker'] ?? true;
$maks_antal     = (int) ( $attributes['maksAntal'] ?? 0 );
$vis_opdateret  = $attributes['visOpdateret'] ?? true;
$tilmelding_link = $attributes['tilmeldingLink'] ?? '/tilmelding/';
$kort_maaned    = 'vandret' === $retning;

$pladser = lene_hent_pladser( true );

if ( $vis_kun_ledige ) {
	$pladser = array_values(
		array_filter(
			$pladser,
			static fn( $p ) => in_array( $p['status'], array( 'ledig', 'delvis' ), true )
		)
	);
}

if ( $maks_antal > 0 ) {
	$pladser = array_slice( $pladser, 0, $maks_antal );
}

$sidst_aendret = lene_seneste_plads_aendring();

$wrapper_classes = array( 'rail' );
if ( 'lodret' === $retning ) {
	$wrapper_classes[] = 'rail--lodret';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-pladstavle section section--sky',
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="section__head">
			<?php if ( $overskrift ) : ?>
				<h2><?php echo esc_html( $overskrift ); ?></h2>
			<?php endif; ?>
			<?php if ( $vis_opdateret && $sidst_aendret ) : ?>
				<p class="updated">Opdateret <?php echo esc_html( lene_dansk_dato( $sidst_aendret ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php if ( empty( $pladser ) ) : ?>
				<p class="lede">Der er ingen kommende pladser at vise lige nu.</p>
			<?php else : ?>
				<ul class="rail__items">
					<?php foreach ( $pladser as $plads ) : ?>
						<?php
						$dele = lene_dansk_dato_dele( $plads['timestamp'], $kort_maaned );
						?>
						<?php
						$kan_tilmelde = 'optaget' !== $plads['status'];
						$tag_element  = $kan_tilmelde ? 'a' : 'div';
						$tag_href     = $kan_tilmelde
							? ' href="' . esc_url( add_query_arg( 'plads', $plads['id'], home_url( $tilmelding_link ) ) ) . '"'
							: '';
						?>
						<li class="plads is-<?php echo esc_attr( $plads['status'] ); ?>">
							<span class="plads__string"></span>
							<<?php echo esc_html( $tag_element ) . $tag_href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="plads__tag">
								<span class="plads__hole"></span>
								<p class="plads__date"><?php echo esc_html( $dele['dag_maaned'] ); ?><span><?php echo esc_html( $dele['aar'] ); ?></span></p>
								<p class="plads__count<?php echo 0 === $plads['antal_ledige'] ? ' is-zero' : ''; ?>">
									<b><?php echo esc_html( (string) $plads['antal_ledige'] ); ?></b> <?php echo esc_html( $plads['tekst_hale'] ); ?>
								</p>
								<?php if ( $vis_prikker ) : ?>
									<ul class="plads__dots" aria-hidden="true">
										<?php for ( $i = 0; $i < $plads['antal']; $i++ ) : ?>
											<li class="<?php echo $i < $plads['antal_ledige'] ? 'free' : 'taken'; ?>"></li>
										<?php endfor; ?>
									</ul>
								<?php endif; ?>
								<p class="plads__status"><?php echo esc_html( $plads['status_label'] ); ?></p>
							</<?php echo esc_html( $tag_element ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>

				<ul class="rail__legend">
					<li><span class="swatch swatch--ledig"></span> Ledig — hele datoen er fri</li>
					<li><span class="swatch swatch--delvis"></span> Delvist ledig — nogle pladser er taget</li>
					<li><span class="swatch swatch--res"></span> Reserveret — står på venteliste</li>
					<li><span class="swatch swatch--opt"></span> Optaget</li>
				</ul>

				<div class="rail__foot">
					<p>Har du et barn på vej? Lene tager imod tilmeldinger i god tid, og du kan komme på venteliste til en reserveret plads.</p>
					<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Skriv til Lene</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
