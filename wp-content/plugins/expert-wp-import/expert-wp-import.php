<?php
/**
 * Plugin Name: Les Experts WordPress — Import du site
 * Description: Installation des contenus fournis, sans SSH et sans écraser les pages existantes.
 * Version: 1.0.0
 * Requires PHP: 8.3
 * Requires at least: 6.8
 * License: GPL-2.0-or-later
 *
 * @package ExpertWPImport
 */

namespace ExpertWPImport;

defined( 'ABSPATH' ) || exit;

/** Create missing content only. */
function import_content() {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bundled local data, never a user-supplied path.
	$items = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
	if ( ! is_array( $items ) ) {
		return new \WP_Error( 'content', 'Le fichier de contenus est illisible.' );
	}
	foreach ( $items as $item ) {
		if ( empty( $item['slug'] ) || ! isset( $item['title'], $item['content'] ) || ! in_array( $item['type'] ?? 'page', array( 'page', 'post' ), true ) || ! in_array( $item['status'] ?? 'publish', array( 'publish', 'draft' ), true ) ) {
			return new \WP_Error( 'content', 'Le fichier contient un contenu non valide.' );
		}
	}
	$result = array(
		'ids'     => array(),
		'created' => 0,
		'kept'    => 0,
	);
	foreach ( $items as $item ) {
		$type     = $item['type'] ?? 'page';
		$path     = isset( $item['parent'] ) ? $item['parent'] . '/' . $item['slug'] : $item['slug'];
		$existing = get_page_by_path( $path, OBJECT, $type );
		if ( $existing ) {
			$result['ids'][ $item['slug'] ] = $existing->ID;
			++$result['kept'];
			continue;
		}
		if ( isset( $item['parent'] ) && empty( $result['ids'][ $item['parent'] ] ) ) {
			return new \WP_Error( 'parent', 'Page parente absente : ' . $item['parent'] );
		}
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => $type,
					'post_name'    => sanitize_title( $item['slug'] ),
					'post_title'   => sanitize_text_field( $item['title'] ),
					'post_content' => wp_kses_post( $item['content'] ),
					'post_status'  => $item['status'] ?? 'publish',
					'post_parent'  => $result['ids'][ $item['parent'] ?? '' ] ?? 0,
				),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$result['ids'][ $item['slug'] ] = $id;
		++$result['created'];
		if ( isset( $item['template'] ) ) {
			update_post_meta( $id, '_wp_page_template', sanitize_key( $item['template'] ) );
		}
		if ( isset( $item['description'] ) ) {
			update_post_meta( $id, '_ewp_description', sanitize_text_field( $item['description'] ) );
		}
		if ( isset( $item['seo'] ) ) {
			$seo = $item['seo'];
			update_post_meta( $id, '_ewp_seo_title', sanitize_text_field( $seo['title'] ) );
			update_post_meta( $id, '_yoast_wpseo_title', sanitize_text_field( $seo['title'] ) );
			update_post_meta( $id, '_yoast_wpseo_metadesc', sanitize_text_field( $seo['description'] ) );
			update_post_meta( $id, '_yoast_wpseo_focuskw', sanitize_text_field( $seo['keyphrase'] ) );
			update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', 'noindex' === $seo['robots'] ? '1' : '0' );
		}
	}
	return $result;
}

/** Admin screen. */
function screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ready = 'expert-wp' === get_stylesheet() && defined( 'EWP_VERSION' );
	?>
	<div class="wrap">
		<h1>Importer le site Les Experts WordPress</h1>
		<p>Ajoute les pages de la version bleue : accueil, expertises, agence, contact et devis. Les pages existantes portant les mêmes adresses sont conservées sans modification.</p>
		<p>Les mentions légales et la confidentialité restent en brouillon. Les notifications e-mail se configurent séparément dans Demandes → Réglages.</p>
		<?php if ( ! $ready ) : ?>
			<div class="notice notice-error inline"><p>Activez d’abord le thème Expert WP et l’extension Expert WP — Demandes &amp; contenus.</p></div>
		<?php else : ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ewp_import_site">
				<?php wp_nonce_field( 'ewp_import_site' ); ?>
				<p><label><input type="checkbox" name="configure" value="1"> Définir l’accueil et le blog du projet, le titre « Les Experts WordPress » et les permaliens par nom de page (recommandé pour une installation neuve).</label></p>
				<p><label><input type="checkbox" name="confirm" value="1" required> J’ai sauvegardé mon site si nécessaire et je souhaite créer les pages manquantes. Les pages commerciales seront publiées.</label></p>
				<?php submit_button( 'Importer les pages du site' ); ?>
			</form>
		<?php endif; ?>
		<p>Vous pourrez désactiver puis supprimer cette extension après l’import : vos contenus seront conservés.</p>
	</div>
	<?php
}

add_action(
	'admin_menu',
	static function () {
		add_management_page( 'Importer Les Experts WordPress', 'Importer le site Expert WP', 'manage_options', 'expert-wp-import', __NAMESPACE__ . '\\screen' );
	}
);

add_action(
	'admin_post_ewp_import_site',
	static function () {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) || ! current_user_can( 'publish_posts' ) ) {
			wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ewp_import_site' );
		if ( 'expert-wp' !== get_stylesheet() || ! defined( 'EWP_VERSION' ) || ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) {
			wp_die( 'Activez le thème et l’extension métier, puis confirmez l’import.' );
		}
		$result = import_content();
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() . ' Les pages déjà créées sont conservées ; vous pouvez relancer l’import.' ) );
		}
		if ( isset( $_POST['configure'] ) && '1' === $_POST['configure'] ) {
			update_option( 'blogname', 'Les Experts WordPress' );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $result['ids']['accueil'] );
			update_option( 'page_for_posts', $result['ids']['blog'] );
			update_option( 'permalink_structure', '/%postname%/' );
			update_option( 'ewp_content_initialized', 1 );
			flush_rewrite_rules();
		}
		wp_die(
			esc_html( sprintf( 'Import terminé : %1$d contenus créés, %2$d contenus existants conservés.', $result['created'], $result['kept'] ) ) . '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">Voir les pages</a> · <a href="' . esc_url( home_url( '/' ) ) . '">Voir le site</a></p>',
			'Import terminé',
			array( 'response' => 200 )
		);
	}
);
