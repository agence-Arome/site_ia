<?php
/**
 * Presentation-only theme setup.
 *
 * @package ExpertWPTheme
 */

defined( 'ABSPATH' ) || exit;

// Render menu categories as headings; all their service links remain visible.
add_filter(
	'render_block_core/navigation-submenu',
	static function ( $content, $block ) {
		if ( 'service-menu-section' !== ( $block['attrs']['className'] ?? '' ) ) {
			return $content;
		}
		$content = preg_replace( '/<button\b[^>]*>.*?<\/button>/s', '<span class="service-menu-heading">' . esc_html( $block['attrs']['label'] ?? '' ) . '</span>', $content, 1 );
		return preg_replace( '/<span class="wp-block-navigation__submenu-icon">.*?<\/span>/s', '', $content, 1 );
	},
	10,
	2
);

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/site.css' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		load_theme_textdomain( 'expert-wp', get_template_directory() . '/languages' );
		register_block_pattern_category( 'expert-wp', array( 'label' => 'Expert WP — compositions' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'expert-wp-site', get_theme_file_uri( 'assets/site.css' ), array(), wp_get_theme()->get( 'Version' ) );
		if ( is_front_page() ) {
			wp_enqueue_script( 'expert-wp-agency-stats', get_theme_file_uri( 'assets/agency-stats.js' ), array(), wp_get_theme()->get( 'Version' ), true );
		}
	}
);
