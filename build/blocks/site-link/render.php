<?php
/**
 * Server-render af lene/site-link — internt link bygget om home_url(),
 * så det peger rigtigt uanset om sitet kører i en undermappe eller på
 * domænets rod. Samme princip som lene/pladschip's "link"-attribut,
 * bare som et generelt tekstlink i stedet for pladse-mærkatet.
 *
 * @var array $attributes Blokkens attributter.
 */

defined( 'ABSPATH' ) || exit;

$sti   = $attributes['sti'] ?? '/';
$tekst = $attributes['tekst'] ?? '';

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => trim( (string) ( $attributes['klasse'] ?? '' ) ) ) );
?>
<a <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( home_url( $sti ) ); ?>"><?php echo esc_html( $tekst ); ?></a>
