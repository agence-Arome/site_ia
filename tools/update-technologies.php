<?php
/**
 * Insert the technology grid without replacing existing homepage content.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$ewp_home = get_page_by_path( 'accueil' );
if ( ! $ewp_home ) {
	WP_CLI::error( 'La page accueil est absente.' );
}
if ( false !== strpos( $ewp_home->post_content, 'technology-section' ) ) {
	WP_CLI::success( 'La grille des technologies est déjà présente.' );
	return;
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled JSON.
$ewp_items = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
$ewp_grid  = null;
foreach ( $ewp_items as $ewp_item ) {
	if ( 'accueil' !== $ewp_item['slug'] ) {
		continue;
	}
	foreach ( parse_blocks( $ewp_item['content'] ) as $ewp_block ) {
		if ( 'section technology-section' === ( $ewp_block['attrs']['className'] ?? '' ) ) {
			$ewp_grid = $ewp_block;
		}
	}
}
if ( ! $ewp_grid ) {
	WP_CLI::error( 'Grille introuvable dans les contenus fournis.' );
}
$ewp_blocks = parse_blocks( $ewp_home->post_content );
$ewp_added  = false;
foreach ( $ewp_blocks as $ewp_index => $ewp_block ) {
	if ( 'section expertise-section expertise-woocommerce' === ( $ewp_block['attrs']['className'] ?? '' ) ) {
		array_splice( $ewp_blocks, $ewp_index + 1, 0, array( $ewp_grid ) );
		$ewp_added = true;
		break;
	}
}
if ( ! $ewp_added ) {
	WP_CLI::error( 'Section WooCommerce personnalisée : intégrer la grille manuellement.' );
}
$ewp_result = wp_update_post(
	wp_slash(
		array(
			'ID'           => $ewp_home->ID,
			'post_content' => serialize_blocks( $ewp_blocks ),
		)
	),
	true
);
if ( is_wp_error( $ewp_result ) ) {
	WP_CLI::error( $ewp_result->get_error_message() );
}
WP_CLI::success( 'Grille ajoutée après les expertises WooCommerce.' );
