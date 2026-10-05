<?php
/**
 * Update unmodified starter pages without overwriting editorial changes.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled JSON, not remote URLs.
$ewp_previous = json_decode( file_get_contents( __DIR__ . '/services-previous-content.json' ), true );
$ewp_updates  = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
// phpcs:enable
foreach ( $ewp_updates as $ewp_update ) {
	if ( ! isset( $ewp_previous[ $ewp_update['slug'] ] ) ) {
		continue;
	}
	$ewp_page = get_page_by_path( $ewp_update['slug'] );
	if ( ! $ewp_page || $ewp_page->post_content === $ewp_update['content'] ) {
		continue;
	}
	if ( hash( 'sha256', $ewp_page->post_content ) !== $ewp_previous[ $ewp_update['slug'] ] ) {
		WP_CLI::warning( 'Contenu personnalisé conservé : ' . $ewp_update['slug'] . '. Fusionner manuellement les nouveaux liens depuis content.json.' );
		continue;
	}
	$ewp_result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $ewp_page->ID,
				'post_title'   => $ewp_update['title'],
				'post_content' => $ewp_update['content'],
			)
		),
		true
	);
	if ( is_wp_error( $ewp_result ) ) {
		WP_CLI::error( $ewp_result->get_error_message() );
	}
	update_post_meta( $ewp_page->ID, '_wp_page_template', $ewp_update['template'] );
	if ( isset( $ewp_update['description'] ) ) {
		update_post_meta( $ewp_page->ID, '_ewp_description', $ewp_update['description'] );
	}
	WP_CLI::log( 'Mis à jour : ' . $ewp_update['slug'] );
}
require __DIR__ . '/seed-content.php';
