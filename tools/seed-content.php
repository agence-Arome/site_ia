<?php
/** Idempotent starter content, run explicitly via wp eval-file. @package ExpertWP */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$ewp_items = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
$ewp_ids = array();
foreach ( $ewp_items as $ewp_item ) {
	$ewp_type = $ewp_item['type'] ?? 'page';
	$ewp_path = isset( $ewp_item['parent'] ) ? $ewp_item['parent'] . '/' . $ewp_item['slug'] : $ewp_item['slug'];
	$ewp_existing = get_page_by_path( $ewp_path, OBJECT, $ewp_type );
	if ( $ewp_existing ) {
		$ewp_ids[ $ewp_item['slug'] ] = $ewp_existing->ID;
		WP_CLI::log( 'Conservé : ' . $ewp_path );
		continue;
	}
	$ewp_id = wp_insert_post( array(
		'post_type' => $ewp_type,
		'post_name' => $ewp_item['slug'],
		'post_title' => $ewp_item['title'],
		'post_content' => wp_slash( $ewp_item['content'] ),
		'post_status' => $ewp_item['status'] ?? 'publish',
		'post_parent' => $ewp_ids[ $ewp_item['parent'] ?? '' ] ?? 0,
	), true );
	if ( is_wp_error( $ewp_id ) ) {
		WP_CLI::error( $ewp_id->get_error_message() );
	}
	$ewp_ids[ $ewp_item['slug'] ] = $ewp_id;
	if ( isset( $ewp_item['template'] ) ) {
		update_post_meta( $ewp_id, '_wp_page_template', $ewp_item['template'] );
	}
	if ( isset( $ewp_item['description'] ) ) {
		update_post_meta( $ewp_id, '_ewp_description', $ewp_item['description'] );
	}
	WP_CLI::log( 'Créé : ' . $ewp_path );
}
if ( ! get_option( 'ewp_content_initialized' ) ) {
	update_option( 'blogname', 'Les Experts Wordpress' );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ewp_ids['accueil'] );
	update_option( 'page_for_posts', $ewp_ids['blog'] );
	update_option( 'wp_page_for_privacy_policy', $ewp_ids['confidentialite'] );
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'ewp_content_initialized', 1 );
	flush_rewrite_rules();
}
WP_CLI::success( 'Contenus préparés. Mentions légales, confidentialité et premier article restent en brouillon.' );
