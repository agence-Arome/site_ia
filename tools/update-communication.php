<?php
/**
 * Add digital communication and creative services while preserving edited pages.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled JSON.
$ewp_hashes = json_decode( file_get_contents( __DIR__ . '/communication-previous.json' ), true );
$ewp_items  = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
// phpcs:enable
foreach ( $ewp_items as $ewp_item ) {
	if ( ! isset( $ewp_hashes[ $ewp_item['slug'] ] ) ) {
		continue;
	}
	$ewp_path = ( isset( $ewp_item['parent'] ) ? $ewp_item['parent'] . '/' : '' ) . $ewp_item['slug'];
	$ewp_page = get_page_by_path( $ewp_path );
	if ( ! $ewp_page || $ewp_page->post_content === $ewp_item['content'] ) {
		continue;
	}
	if ( hash( 'sha256', $ewp_page->post_content ) !== $ewp_hashes[ $ewp_item['slug'] ] ) {
		WP_CLI::warning( 'Contenu personnalisé conservé : ' . $ewp_path . '. Fusionner manuellement depuis content.json.' );
		continue;
	}
	$ewp_result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $ewp_page->ID,
				'post_content' => $ewp_item['content'],
			)
		),
		true
	);
	if ( is_wp_error( $ewp_result ) ) {
		WP_CLI::error( $ewp_result->get_error_message() );
	}
	WP_CLI::log( 'Mis à jour : ' . $ewp_path );
}
require __DIR__ . '/seed-content.php';
