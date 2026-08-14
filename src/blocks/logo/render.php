<?php
/**
 * Server-render af lene/logo — læser den valgte ikon-variant fra
 * indstillingerne (inc/logo.php) og tegner den med temaets farve-variabler.
 */

defined( 'ABSPATH' ) || exit;

$variant = lene_hent_logo_variant();

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'brand__mark' ) );
?>
<span <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo lene_logo_svg( $variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</span>
