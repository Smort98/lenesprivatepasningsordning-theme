<?php
/**
 * Server-render af lene/home-link. $content er de indre blokkes
 * allerede-renderede markup (fx logo-SVG'en + navne-spannet) — vi
 * lægger blot selve <a href="<?php echo home_url(); ?>">-taggen udenom,
 * i stedet for at have href'en hardcodet i en statisk skabelon.
 *
 * @var array  $attributes Blokkens attributter.
 * @var string $content    De indre blokkes renderede HTML.
 */

defined( 'ABSPATH' ) || exit;

$klasse = trim( (string) ( $attributes['klasse'] ?? '' ) );
?>
<a class="<?php echo esc_attr( $klasse ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
