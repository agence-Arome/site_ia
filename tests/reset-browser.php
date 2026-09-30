<?php
/** Remove only reserved browser fixtures on a local test site. @package ExpertWP */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	exit( 'Local WordPress required.' );
}
foreach ( array( 'browser-fixture@example.test', 'nojs-fixture@example.test' ) as $ewp_email ) {
	do {
		$ewp_result = \ExpertWP\Plugin::erase( $ewp_email );
	} while ( ! $ewp_result['done'] && ! $ewp_result['items_retained'] );
}
global $wpdb;
$ewp_rate_names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_ewp_rate_' ) . '%' ) );
foreach ( $ewp_rate_names as $ewp_name ) {
	delete_transient( substr( $ewp_name, strlen( '_transient_' ) ) );
}
WP_CLI::success( 'Fixtures navigateur et compteurs locaux nettoyés.' );
