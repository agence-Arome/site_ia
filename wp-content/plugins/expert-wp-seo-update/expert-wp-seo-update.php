<?php
/**
 * Plugin Name: Les Experts WordPress — Mise à jour SEO
 * Description: Mise à jour éditoriale contrôlée avec prévisualisation, sauvegarde et restauration.
 * Version: 1.0.0
 * Requires PHP: 8.3
 * Requires at least: 6.8
 * License: GPL-2.0-or-later
 *
 * @package ExpertWPSeoUpdate
 */

namespace ExpertWPSeoUpdate;

defined( 'ABSPATH' ) || exit;

/** Read the fixed, bundled editorial plan. */
function items() {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed bundled file.
	$data = json_decode( file_get_contents( __DIR__ . '/migration.json' ), true );
	return is_array( $data ) ? $data : array();
}

/** Compare imports without treating harmless whitespace as a content change.
 *
 * @param string $text Block content.
 */
function normalized( $text ) {
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/** Determine whether replacing the source is safe.
 *
 * @param \WP_Post $post Current page.
 * @param array    $item Plan entry.
 */
function compatible( $post, $item ) {
	$valid = array( $item['before']['content'], wp_kses_post( $item['before']['content'] ), $item['after']['content'], wp_kses_post( $item['after']['content'] ) );
	return in_array( normalized( $post->post_content ), array_map( __NAMESPACE__ . '\\normalized', $valid ), true ) && in_array( $post->post_title, array( $item['before']['title'], $item['after']['title'] ), true );
}

/** Only the metadata belonging to this migration.
 *
 * @param array $seo Editorial metadata.
 */
function metadata( $seo ) {
	return array(
		'_yoast_wpseo_title'               => sanitize_text_field( $seo['title'] ),
		'_yoast_wpseo_metadesc'            => sanitize_text_field( $seo['description'] ),
		'_yoast_wpseo_focuskw'             => sanitize_text_field( $seo['keyphrase'] ),
		'_yoast_wpseo_meta-robots-noindex' => 'noindex' === $seo['robots'] ? '1' : '0',
		'_ewp_seo_title'                   => sanitize_text_field( $seo['title'] ),
		'_ewp_description'                 => sanitize_text_field( $seo['description'] ),
	);
}

/** Admin preview and explicit apply/restore controls. */
function screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$backup = get_option( 'ewp_seo_backup_20261007', array() );
	echo '<div class="wrap"><h1>Optimisation SEO — Les Experts WordPress</h1><p>URL, statuts et mode maintenance conservés. Yoast reçoit les métadonnées ; aucun score artificiel. Les pages modifiées depuis le fichier source sont ignorées et signalées.</p>';
	if ( ! defined( 'WPSEO_VERSION' ) ) {
		echo '<p>Activez Yoast SEO avant cette opération.</p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>Page</th><th>Compatibilité</th><th>Titre SEO</th><th>Expression principale</th></tr></thead><tbody>';
	foreach ( items() as $item ) {
		$post  = get_page_by_path( $item['path'], OBJECT, $item['type'] );
		$state = ! $post ? 'Absente : ignorée' : ( compatible( $post, $item ) ? 'Prête' : 'Modification locale : ignorée' );
		echo '<tr><td>' . esc_html( $item['path'] ) . '</td><td>' . esc_html( $state ) . '</td><td>' . esc_html( $item['after']['seo']['title'] ) . '</td><td>' . esc_html( $item['after']['seo']['keyphrase'] ) . '</td></tr>';
	}
	echo '</tbody></table><p>La sauvegarde interne conserve les textes et les métadonnées précédents. Les révisions WordPress restent disponibles. Une restauration refuse d’écraser des modifications ultérieures.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ewp_seo_apply">';
	wp_nonce_field( 'ewp_seo_apply' );
	submit_button( 'Appliquer les optimisations SEO' );
	echo '</form>';
	if ( $backup ) {
		echo '<p>' . esc_html( count( $backup ) ) . ' contenus sauvegardés.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ewp_seo_restore">';
		wp_nonce_field( 'ewp_seo_restore' );
		submit_button( 'Restaurer les contenus précédents', 'secondary' );
		echo '</form>';
	}
	echo '</div>';
}

/** Authenticated operations only.
 *
 * @param string $action Nonce action.
 */
function authorize( $action ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_others_pages' ) || ! current_user_can( 'unfiltered_html' ) ) {
		wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( $action );
}

/** Apply a compatible page once, preserving a recoverable snapshot. */
function apply() {
	authorize( 'ewp_seo_apply' );
	if ( ! defined( 'WPSEO_VERSION' ) ) {
		wp_die( 'Yoast SEO doit être actif.' );
	}
	$backup = get_option( 'ewp_seo_backup_20261007', array() );
	$log    = array();
	foreach ( items() as $item ) {
		$post = get_page_by_path( $item['path'], OBJECT, $item['type'] );
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) || ! compatible( $post, $item ) ) {
			$log[] = $item['path'] . ' : ignorée (absente ou modifiée).';
			continue;
		}
		if ( isset( $backup[ $post->ID ] ) ) {
			$log[] = $item['path'] . ' : déjà traitée.';
			continue;
		}
		$meta   = metadata( $item['after']['seo'] );
		$record = array(
			'title'         => $post->post_title,
			'content'       => $post->post_content,
			'meta'          => array(),
			'after_title'   => $item['after']['title'],
			'after_content' => $item['after']['content'],
			'after_meta'    => $meta,
		);
		foreach ( $meta as $key => $value ) {
			$record['meta'][ $key ] = array(
				'exists' => metadata_exists( 'post', $post->ID, $key ),
				'value'  => get_post_meta( $post->ID, $key, true ),
			);
		}
		$backup[ $post->ID ] = $record;
		update_option( 'ewp_seo_backup_20261007', $backup, false );
		if ( get_option( 'ewp_seo_backup_20261007' ) !== $backup ) {
			wp_die( 'Sauvegarde impossible : opération interrompue.' );
		}
		wp_save_post_revision( $post->ID );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post->ID, $key, $value );
		}
		// Fixed bundled Gutenberg markup, never request input. Requires unfiltered_html above.
		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $post->ID,
					'post_title'   => sanitize_text_field( $item['after']['title'] ),
					'post_content' => $item['after']['content'],
				)
			),
			true
		);
		$log[]  = $item['path'] . ( is_wp_error( $result ) ? ' : erreur, sauvegarde conservée.' : ' : mise à jour.' );
	}
	wp_die( '<h1>Résultat SEO</h1><pre>' . esc_html( implode( "\n", $log ) ) . '</pre><p><a href="' . esc_url( admin_url( 'tools.php?page=ewp-seo-update' ) ) . '">Retour au contrôle SEO</a></p>', 'Résultat SEO', array( 'response' => 200 ) );
}

/** Restore only entries which still match the migrated state. */
function restore() {
	authorize( 'ewp_seo_restore' );
	$backup = get_option( 'ewp_seo_backup_20261007', array() );
	$log    = array();
	foreach ( $backup as $id => $record ) {
		$post = get_post( $id );
		if ( ! $post || ! current_user_can( 'edit_post', $id ) || $post->post_title !== $record['after_title'] || normalized( $post->post_content ) !== normalized( $record['after_content'] ) ) {
			$log[] = $id . ' : conservée (modification ultérieure ou erreur).';
			continue;
		}
		foreach ( $record['after_meta'] as $key => $value ) {
			if ( (string) get_post_meta( $id, $key, true ) !== (string) $value ) {
				$log[] = $id . ' : métadonnées modifiées, conservée.';
				continue 2;
			}
		}
		foreach ( $record['meta'] as $key => $old ) {
			if ( $old['exists'] ) {
				update_post_meta( $id, $key, $old['value'] );
			} else {
				delete_post_meta( $id, $key );
			}
		}
		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_title'   => $record['title'],
					'post_content' => $record['content'],
				)
			),
			true
		);
		if ( ! is_wp_error( $result ) ) {
			unset( $backup[ $id ] );
			update_option( 'ewp_seo_backup_20261007', $backup, false );
			$log[] = $id . ' : restaurée.';
		}
	}
	wp_die( '<pre>' . esc_html( implode( "\n", $log ) ) . '</pre>', 'Restauration SEO', array( 'response' => 200 ) );
}

add_action(
	'admin_menu',
	static function () {
		add_management_page( 'Optimisation SEO', 'Optimisation SEO Expert WP', 'manage_options', 'ewp-seo-update', __NAMESPACE__ . '\\screen' );
	}
);
add_action( 'admin_post_ewp_seo_apply', __NAMESPACE__ . '\\apply' );
add_action( 'admin_post_ewp_seo_restore', __NAMESPACE__ . '\\restore' );
