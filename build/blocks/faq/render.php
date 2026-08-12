<?php
/**
 * Server-render af lene/faq.
 *
 * $content er allerede den fulde, rigtige markup (samme som editoren
 * viser) — den ændres ikke her. Det eneste render.php tilføjer er
 * FAQPage-schemaet, udtrukket ved at parse spørgsmål/svar direkte ud
 * af den rendered HTML (mere robust end at stole på at rich-text-
 * attributter er tilgængelige server-side).
 *
 * @var string   $content Det allerede renderede indhold (fra save()).
 */

defined( 'ABSPATH' ) || exit;

$sporgsmaal_svar = array();

if ( trim( $content ) ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $content . '</div>', LIBXML_NOERROR );
	libxml_clear_errors();

	foreach ( $dom->getElementsByTagName( 'details' ) as $details ) {
		$sporgsmaal = '';
		$svar       = '';
		foreach ( $details->childNodes as $child ) {
			if ( 'summary' === $child->nodeName ) {
				$sporgsmaal = trim( $child->textContent );
			} elseif ( 'p' === $child->nodeName ) {
				$svar = trim( $child->textContent );
			}
		}
		if ( $sporgsmaal && $svar ) {
			$sporgsmaal_svar[] = array( 'sporgsmaal' => $sporgsmaal, 'svar' => $svar );
		}
	}
}

if ( ! empty( $sporgsmaal_svar ) ) {
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			static fn( $par ) => array(
				'@type'          => 'Question',
				'name'           => $par['sporgsmaal'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $par['svar'],
				),
			),
			$sporgsmaal_svar
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
