<?php
/**
 * Retire the approach page and update links without overwriting page edits.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
$ewp_pages = get_posts(
	array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => -1,
	)
);
foreach ( $ewp_pages as $ewp_page ) {
	$ewp_content = str_replace( array( '/notre-approche/', 'Découvrir notre approche' ), array( '/qui-sommes-nous/', 'Découvrir notre agence' ), $ewp_page->post_content );
	if ( $ewp_content !== $ewp_page->post_content ) {
		$ewp_result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $ewp_page->ID,
					'post_content' => $ewp_content,
				)
			),
			true
		);
		if ( is_wp_error( $ewp_result ) ) {
			WP_CLI::error( $ewp_result->get_error_message() );
		}
	}
}
$ewp_approach = get_page_by_path( 'notre-approche' );
if ( $ewp_approach && ! wp_trash_post( $ewp_approach->ID ) ) {
	WP_CLI::error( 'Impossible de placer la page dans la corbeille.' );
}
WP_CLI::success( 'Page retirée et liens actualisés.' );
