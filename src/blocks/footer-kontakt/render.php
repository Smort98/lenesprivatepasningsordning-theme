<?php
/**
 * Server-render af lene/footer-kontakt.
 */

defined( 'ABSPATH' ) || exit;

$kontakt = lene_hent_kontaktoplysninger();

$wrapper_attributes = get_block_wrapper_attributes();
?>
<ul <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<li><?php echo esc_html( $kontakt['navn'] ); ?></li>
	<li><?php echo esc_html( $kontakt['adresse'] ); ?></li>
	<li><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $kontakt['telefon'] ) ); ?>"><?php echo esc_html( $kontakt['telefon'] ); ?></a></li>
	<li><a href="mailto:<?php echo esc_attr( $kontakt['email'] ); ?>"><?php echo esc_html( $kontakt['email'] ); ?></a></li>
</ul>
