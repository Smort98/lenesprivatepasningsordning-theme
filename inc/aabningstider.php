<?php
/**
 * Delte hjælpefunktioner til åbningstider. lene/aabningstider-blokken på
 * Praktisk info-siden er den ene sandhed — her læses dens attributter,
 * så den lille status-widget i headeren og schema.org-dataene altid
 * matcher det, der står under Praktisk info, uden at tiderne skal
 * indtastes to steder.
 */

defined( 'ABSPATH' ) || exit;

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

function lene_aabningstider_minutter( string $hhmm ): int {
	$dele = explode( ':', $hhmm );
	if ( count( $dele ) < 2 ) {
		return 0;
	}
	return ( (int) $dele[0] ) * 60 + (int) $dele[1];
}

/**
 * Finder det første lene/aabningstider-blok i sideindholdet og læser
 * dens "dage" + "note"-attributter. Falder tilbage til blokkens egne
 * standardværdier hvis attributten matcher standarden (og derfor ikke
 * er gemt i selve blok-kommentaren — sådan serialiserer WordPress).
 */
function lene_hent_aabningstider_data(): array {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}

	$block_type   = WP_Block_Type_Registry::get_instance()->get_registered( 'lene/aabningstider' );
	$default_dage = $block_type->attributes['dage']['default'] ?? array();
	$default_note = $block_type->attributes['note']['default'] ?? '';

	$data = array(
		'dage' => $default_dage,
		'note' => $default_note,
	);

	$side = get_page_by_path( 'praktisk-info' );
	if ( ! $side ) {
		return $data;
	}

	foreach ( parse_blocks( $side->post_content ) as $blok ) {
		if ( 'lene/aabningstider' === ( $blok['blockName'] ?? '' ) ) {
			$attrs = $blok['attrs'] ?? array();
			$data  = array(
				'dage' => is_array( $attrs['dage'] ?? null ) ? $attrs['dage'] : $default_dage,
				'note' => $attrs['note'] ?? $default_note,
			);
			break;
		}
	}

	return $data;
}

/**
 * "Åbent nu · lukker 16.00" / "Lukket nu" ud fra en "dage"-liste (samme
 * format som lene/aabningstider bruger). Bruges af både blokken selv og
 * den lille header-widget.
 */
function lene_aabningstider_beregn_status( array $dage ): string {
	$nu_ugedag   = (int) current_time( 'N' );
	$nu_minutter = ( (int) current_time( 'G' ) ) * 60 + (int) current_time( 'i' );

	foreach ( $dage as $dag ) {
		$ugedage = is_array( $dag['ugedage'] ?? null ) ? array_map( 'intval', $dag['ugedage'] ) : array();
		if ( ! in_array( $nu_ugedag, $ugedage, true ) ) {
			continue;
		}
		$aabner = lene_aabningstider_minutter( $dag['aabner'] );
		$lukker = lene_aabningstider_minutter( $dag['lukker'] );
		if ( $nu_minutter >= $aabner && $nu_minutter < $lukker ) {
			return 'Åbent nu · lukker ' . lene_aabningstider_format_tid( $dag['lukker'] );
		}
		break;
	}

	return 'Lukket nu';
}
