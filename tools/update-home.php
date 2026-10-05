<?php
/**
 * Apply the homepage revision while preserving custom editorial content.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled files.
$ewp_items    = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
$ewp_previous = trim( file_get_contents( __DIR__ . '/home-previous-sha256.txt' ) );
// phpcs:enable
$ewp_home = get_page_by_path( 'accueil' );
if ( ! $ewp_home ) {
	WP_CLI::error( 'Créer les pages avec seed-content.php avant cette mise à jour.' );
}
foreach ( $ewp_items as $ewp_item ) {
	if ( 'accueil' !== $ewp_item['slug'] ) {
		continue;
	}
	if ( $ewp_home->post_content === $ewp_item['content'] ) {
		WP_CLI::success( 'Accueil déjà à jour.' );
		return;
	}
	if ( hash( 'sha256', $ewp_home->post_content ) !== $ewp_previous ) {
		WP_CLI::error( 'Accueil personnalisé conservé. Fusionner les sections manuellement depuis content.json.' );
	}
	$ewp_result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $ewp_home->ID,
				'post_content' => $ewp_item['content'],
			)
		),
		true
	);
	if ( is_wp_error( $ewp_result ) ) {
		WP_CLI::error( $ewp_result->get_error_message() );
	}
	update_post_meta( $ewp_home->ID, '_ewp_description', $ewp_item['description'] );
	WP_CLI::success( 'Accueil mis à jour.' );
}
