<?php
/**
 * Append phone cards after the contact form, preserving existing content.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
$ewp_page = get_page_by_path( 'contact' );
if ( ! $ewp_page ) {
	WP_CLI::error( 'Page contact absente.' );
}
if ( false !== strpos( $ewp_page->post_content, 'home-contact-section' ) ) {
	WP_CLI::success( 'Blocs déjà présents.' );
	return;
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled content.
$ewp_items = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
foreach ( $ewp_items as $ewp_item ) {
	if ( 'contact' !== $ewp_item['slug'] ) {
		continue;
	}
	foreach ( parse_blocks( $ewp_item['content'] ) as $ewp_block ) {
		if ( 'home-contact-section' === ( $ewp_block['attrs']['className'] ?? '' ) ) {
			$ewp_result = wp_update_post(
				wp_slash(
					array(
						'ID'           => $ewp_page->ID,
						'post_content' => $ewp_page->post_content . serialize_block( $ewp_block ),
					)
				),
				true
			);
			if ( is_wp_error( $ewp_result ) ) {
				WP_CLI::error( $ewp_result->get_error_message() );
			}
			WP_CLI::success( 'Blocs téléphone ajoutés sous le formulaire.' );
			return;
		}
	}
}
WP_CLI::error( 'Blocs téléphone introuvables.' );
