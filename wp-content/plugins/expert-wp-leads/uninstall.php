<?php
/**
 * Preserve commercial records unless an explicit verified privacy deletion is performed.
 *
 * @package ExpertWP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
wp_clear_scheduled_hook( 'ewp_purge' );
// Leads and settings intentionally remain. Export/erase from WordPress privacy tools before uninstalling.
$ewp_role = get_role( 'administrator' );
if ( $ewp_role ) {
	$ewp_role->remove_cap( 'manage_ewp_leads' );
}
