<?php
/**
 * Server-render af lene/aabningstider.
 *
 * Tiderne er data (etiket, ugedage, åbner, lukker) — aksen, bjælkernes
 * placering og "Åbent nu"-mærkatet beregnes altid herfra.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lene_aabningstider_format_tid' ) ) {
	/**
	 * "06:45" -> "6.45" (dansk klokkeslætsformat, ingen foranstillet nul).
	 */
	function lene_aabningstider_format_tid( string $hhmm ): string {
		$dele = explode( ':', $hhmm );
		if ( count( $dele ) < 2 ) {
			return $hhmm;
		}
		return (int) $dele[0] . '.' . $dele[1];
	}
}

if ( ! function_exists( 'lene_aabningstider_minutter' ) ) {
	function lene_aabningstider_minutter( string $hhmm ): int {
		$dele = explode( ':', $hhmm );
		if ( count( $dele ) < 2 ) {
			return 0;
		}
		return ( (int) $dele[0] ) * 60 + (int) $dele[1];
	}
}

$titel      = $attributes['titel'] ?? '';
$eyebrow    = $attributes['eyebrow'] ?? '';
$dage       = is_array( $attributes['dage'] ?? null ) ? $attributes['dage'] : array();
$vis_status = $attributes['visStatus'] ?? true;
$note       = $attributes['note'] ?? '';

$dage = array_values(
	array_filter(
		$dage,
		static fn( $d ) => ! empty( $d['aabner'] ) && ! empty( $d['lukker'] )
	)
);

$akse_start_time = null;
$akse_slut_time  = null;
foreach ( $dage as $dag ) {
	$aabner = lene_aabningstider_minutter( $dag['aabner'] );
	$lukker = lene_aabningstider_minutter( $dag['lukker'] );
	if ( null === $akse_start_time || $aabner < $akse_start_time ) {
		$akse_start_time = $aabner;
	}
	if ( null === $akse_slut_time || $lukker > $akse_slut_time ) {
		$akse_slut_time = $lukker;
	}
}
$akse_start_time ??= 6 * 60;
$akse_slut_time  ??= 18 * 60;

$akse_start_time = (int) ( floor( $akse_start_time / 60 ) * 60 );
$akse_slut_time  = (int) ( ceil( $akse_slut_time / 60 ) * 60 );
$akse_span       = max( 1, $akse_slut_time - $akse_start_time );

$ticks = array();
for ( $t = $akse_start_time; $t <= $akse_slut_time; $t += 120 ) {
	$ticks[] = array(
		'pct'  => ( $t - $akse_start_time ) / $akse_span * 100,
		'label' => (int) ( $t / 60 ),
	);
}

$status_tekst = '';
if ( $vis_status ) {
	$nu_ugedag  = (int) current_time( 'N' );
	$nu_minutter = ( (int) current_time( 'G' ) ) * 60 + (int) current_time( 'i' );
	foreach ( $dage as $dag ) {
		$ugedage = is_array( $dag['ugedage'] ?? null ) ? array_map( 'intval', $dag['ugedage'] ) : array();
		if ( ! in_array( $nu_ugedag, $ugedage, true ) ) {
			continue;
		}
		$aabner = lene_aabningstider_minutter( $dag['aabner'] );
		$lukker = lene_aabningstider_minutter( $dag['lukker'] );
		if ( $nu_minutter >= $aabner && $nu_minutter < $lukker ) {
			$status_tekst = 'Åbent nu · lukker ' . lene_aabningstider_format_tid( $dag['lukker'] );
		}
		break;
	}
	if ( '' === $status_tekst ) {
		$status_tekst = 'Lukket nu';
	}
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-aabningstider section',
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
			<?php if ( $vis_status && $status_tekst ) : ?>
				<p class="hours__now"><span class="dot"></span><?php echo esc_html( $status_tekst ); ?></p>
			<?php endif; ?>
		</div>

		<div class="hours">
			<?php foreach ( $dage as $dag ) : ?>
				<?php
				$aabner = lene_aabningstider_minutter( $dag['aabner'] );
				$lukker = lene_aabningstider_minutter( $dag['lukker'] );
				$left   = ( $aabner - $akse_start_time ) / $akse_span * 100;
				$width  = ( $lukker - $aabner ) / $akse_span * 100;
				?>
				<div class="hours__row">
					<p class="hours__day"><?php echo esc_html( $dag['etiket'] ?? '' ); ?></p>
					<div class="hours__track">
						<div class="hours__bar" style="left:<?php echo esc_attr( round( $left, 2 ) ); ?>%;width:<?php echo esc_attr( round( $width, 2 ) ); ?>%">
							<span><?php echo esc_html( lene_aabningstider_format_tid( $dag['aabner'] ) ); ?></span>
							<span><?php echo esc_html( lene_aabningstider_format_tid( $dag['lukker'] ) ); ?></span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="hours__axis">
				<span></span>
				<div class="hours__ticks">
					<?php foreach ( $ticks as $tick ) : ?>
						<span style="left:<?php echo esc_attr( round( $tick['pct'], 2 ) ); ?>%"><?php echo esc_html( (string) $tick['label'] ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php if ( $note ) : ?>
			<p class="hours__note"><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
	</div>
</section>
