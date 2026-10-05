<?php
/**
 * Update the site identity and add the fourth hero label.
 *
 * @package ExpertWP
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

update_option( 'blogname', 'Les Experts WordPress' );
$ewp_home = get_page_by_path( 'accueil' );
if ( $ewp_home && false === strpos( $ewp_home->post_content, 'blueprint-tag tag-d' ) ) {
	$ewp_content = str_replace(
		'<div class="blueprint-tag tag-c">03 · FAIRE ÉVOLUER</div>',
		'<div class="blueprint-tag tag-c">03 · FAIRE ÉVOLUER</div><div class="blueprint-tag tag-d">04 · SÉCURISER</div>',
		$ewp_home->post_content,
		$ewp_count
	);
	if ( $ewp_count ) {
		$ewp_result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $ewp_home->ID,
					'post_content' => $ewp_content,
				)
			),
			true
		);
		if ( is_wp_error( $ewp_result ) ) {
			WP_CLI::error( $ewp_result->get_error_message() );
		}
	} else {
		WP_CLI::warning( 'Bannière personnalisée conservée : ajouter le quatrième repère manuellement.' );
	}
}
WP_CLI::success( 'Identité du site mise à jour.' );
