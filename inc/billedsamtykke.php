<?php
/**
 * Samtykke-markering på billeder med genkendelige børn.
 *
 * Ikke juridisk vandtæt — det er en huskeseddel til Lene, ikke en
 * spærring. Se lene/galleri, der viser en advarsel i editoren hvis et
 * umarkeret billede lægges i galleriet.
 */

defined( 'ABSPATH' ) || exit;

function lene_register_samtykke_meta() {
	register_post_meta(
		'attachment',
		'samtykke_noteret',
		array(
			'type'          => 'boolean',
			'single'        => true,
			'show_in_rest'  => true,
			'default'       => false,
			'auth_callback' => fn() => current_user_can( 'upload_files' ),
		)
	);
}
add_action( 'init', 'lene_register_samtykke_meta' );

function lene_billede_samtykke_felt( array $form_fields, WP_Post $post ): array {
	if ( ! wp_attachment_is_image( $post ) ) {
		return $form_fields;
	}
	$checked = get_post_meta( $post->ID, 'samtykke_noteret', true );
	$form_fields['samtykke_noteret'] = array(
		'label' => 'Forældresamtykke',
		'input' => 'html',
		'html'  => sprintf(
			'<label><input type="checkbox" name="attachments[%1$d][samtykke_noteret]" value="1" %2$s> Der er indhentet samtykke fra forældrene til dette billede</label>',
			$post->ID,
			checked( $checked, true, false )
		),
		'helps' => 'Bruges af lene/galleri til at advare, hvis et umarkeret billede lægges i fotoalbummet.',
	);
	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'lene_billede_samtykke_felt', 10, 2 );

function lene_billede_samtykke_gem( array $post, array $attachment ): array {
	update_post_meta( $post['ID'], 'samtykke_noteret', ! empty( $attachment['samtykke_noteret'] ) );
	return $post;
}
add_filter( 'attachment_fields_to_save', 'lene_billede_samtykke_gem', 10, 2 );
