<?php
/**
 * Server-render af lene/pladschip — headerens "X ledige pladser"-mærkat.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$link  = $attributes['link'] ?? '/ledige-pladser/';
$antal = lene_antal_ledige_pladser_total();
$ord   = 1 === $antal ? 'ledig' : 'ledige';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'lene-pladschip status-chip',
	)
);
?>
<a <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( home_url( $link ) ); ?>">
	<span class="dot"></span><span><?php echo esc_html( $antal . ' ' . $ord ); ?> <span class="word">pladser</span></span>
</a>
