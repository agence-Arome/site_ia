<?php
/**
 * Presentation-only theme setup.
 *
 * @package ExpertWPTheme
 */

defined( 'ABSPATH' ) || exit;

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
	}
);
