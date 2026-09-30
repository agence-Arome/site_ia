<?php
/**
 * Plugin Name: Expert WP — Demandes & contenus
 * Description: Devis conditionnels, leads privés, notifications et SEO essentiel.
 * Version: 0.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 * Text Domain: expert-wp-leads
 *
 * @package ExpertWP
 */

namespace ExpertWP;

defined( 'ABSPATH' ) || exit;

define( 'EWP_FILE', __FILE__ );
define( 'EWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'EWP_VERSION', '0.1.0' );

require_once EWP_DIR . 'includes/class-plugin.php';
require_once EWP_DIR . 'includes/class-form.php';
require_once EWP_DIR . 'includes/class-admin.php';
require_once EWP_DIR . 'includes/class-seo.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );
Plugin::boot();
