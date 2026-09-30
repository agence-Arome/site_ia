<?php
/** Integration suite against an isolated real WordPress database. @package ExpertWP */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	exit( 'Local WordPress required.' );
}

$GLOBALS['ewp_failures'] = 0;
$GLOBALS['ewp_checks'] = 0;
function ewp_assert( $condition, $message ) {
	global $ewp_failures, $ewp_checks;
	++$ewp_checks;
	if ( ! $condition ) {
		++$ewp_failures;
		WP_CLI::warning( 'FAIL: ' . $message );
	} else {
		WP_CLI::log( 'PASS: ' . $message );
	}
}

$ewp_base = array( 'mode' => 'quote', 'service' => 'creation', 'name' => 'Test local', 'email' => 'fixture@example.test', 'message' => 'Un projet de test sans données réelles.', 'details' => 'Objectif de qualification.', 'privacy' => '1', 'budget' => 'a-definir', 'timeline' => 'flexible' );
foreach ( \ExpertWP\Form::services() as $ewp_key => $ewp_service ) {
	ewp_assert( ! is_wp_error( \ExpertWP\Form::validate( array_merge( $ewp_base, array( 'service' => $ewp_key ) ) ) ), 'Service validé : ' . $ewp_key );
}
foreach ( array( array( 'email' => "invalid\r\nBcc:bad@example.test" ), array( 'email' => array( 'nested' ) ), array( 'service' => 'unknown' ), array( 'name' => '' ), array( 'details' => '' ), array( 'message' => str_repeat( 'a', 4001 ) ), array( 'website' => 'javascript:alert(1)' ), array( 'privacy' => '0' ), array( 'budget' => 'unexpected' ), array( 'timeline' => 'tomorrow' ), array( 'mode' => 'other' ) ) as $ewp_invalid ) {
	ewp_assert( is_wp_error( \ExpertWP\Form::validate( array_merge( $ewp_base, $ewp_invalid ) ) ), 'Rejet du champ : ' . array_key_first( $ewp_invalid ) );
}
$ewp_contact = \ExpertWP\Form::validate( array_merge( $ewp_base, array( 'mode' => 'contact', 'service' => 'injected', 'details' => 'ignored' ) ) );
ewp_assert( 'contact' === $ewp_contact['service'] && '' === $ewp_contact['details'], 'Le contact ignore les champs de qualification' );
$ewp_clean = \ExpertWP\Form::validate( array_merge( $ewp_base, array( 'name' => '<script>alert(1)</script>Nom' ) ) );
ewp_assert( ! str_contains( $ewp_clean['name'], '<' ), 'Assainissement XSS' );
$_COOKIE['ewp_session'] = str_repeat( 'a', 64 );
$ewp_token = \ExpertWP\Form::token();
ewp_assert( \ExpertWP\Form::verify_token( $ewp_token ), 'Jeton valide' );
ewp_assert( ! \ExpertWP\Form::verify_token( $ewp_token . '0' ), 'Jeton altéré rejeté' );
$_COOKIE['ewp_session'] = str_repeat( 'b', 64 );
ewp_assert( ! \ExpertWP\Form::verify_token( $ewp_token ), 'Jeton d’un autre navigateur rejeté' );
$_COOKIE['ewp_session'] = str_repeat( 'a', 64 );
$ewp_old_body = ( time() - 7201 ) . '.' . wp_generate_uuid4();
$ewp_old = $ewp_old_body . '.' . hash_hmac( 'sha256', $ewp_old_body . '|' . $_COOKIE['ewp_session'], wp_salt( 'nonce' ) );
ewp_assert( ! \ExpertWP\Form::verify_token( $ewp_old ), 'Jeton expiré rejeté' );
$ewp_valid = \ExpertWP\Form::validate( $ewp_base );
$ewp_id = \ExpertWP\Form::store( $ewp_valid, $ewp_token );
ewp_assert( is_int( $ewp_id ) && $ewp_id > 0, 'Lead enregistré' );
ewp_assert( $ewp_id === \ExpertWP\Form::store( $ewp_valid, $ewp_token ), 'Double envoi idempotent' );
ewp_assert( 'private' === get_post_status( $ewp_id ), 'Lead privé' );
$ewp_type = get_post_type_object( 'ewp_lead' );
ewp_assert( ! $ewp_type->public && ! $ewp_type->show_in_rest && ! $ewp_type->publicly_queryable && ! $ewp_type->can_export, 'Aucune exposition publique ni export éditorial' );
wp_set_current_user( 0 );
ewp_assert( ! current_user_can( 'manage_ewp_leads' ), 'Anonyme sans permission' );
$ewp_user = wp_create_user( 'ewp-test-' . wp_rand(), wp_generate_password( 30 ), 'role-fixture@example.test' );
$ewp_wp_user = new WP_User( $ewp_user );
$ewp_wp_user->set_role( 'editor' );
wp_set_current_user( $ewp_user );
ewp_assert( ! current_user_can( 'manage_ewp_leads' ) && ! current_user_can( 'edit_post', $ewp_id ), 'Éditeur sans accès commercial' );
wp_set_current_user( 1 );
ewp_assert( current_user_can( 'manage_ewp_leads' ), 'Administrateur habilité' );
$ewp_export = \ExpertWP\Plugin::export( $ewp_base['email'] );
ewp_assert( ! empty( $ewp_export['data'] ), 'Export via les outils de confidentialité' );
$ewp_original = get_option( 'ewp_settings', array() );
update_option( 'ewp_settings', array_merge( \ExpertWP\Plugin::settings(), array( 'notify' => 1, 'recipient' => 'notify@example.test' ) ) );
$ewp_fail_mail = static function () { return false; };
add_filter( 'pre_wp_mail', $ewp_fail_mail );
\ExpertWP\Plugin::notify( $ewp_id );
ewp_assert( 'failed' === get_post_meta( $ewp_id, '_ewp_mail', true ) && null !== get_post( $ewp_id ), 'Échec e-mail sans perte du lead' );
remove_filter( 'pre_wp_mail', $ewp_fail_mail );
$ewp_pass_mail = static function () { return true; };
add_filter( 'pre_wp_mail', $ewp_pass_mail );
\ExpertWP\Plugin::notify( $ewp_id );
ewp_assert( 'sent' === get_post_meta( $ewp_id, '_ewp_mail', true ), 'Reprise de notification' );
$ewp_attempts = get_post_meta( $ewp_id, '_ewp_attempts', true );
\ExpertWP\Plugin::notify( $ewp_id );
ewp_assert( $ewp_attempts === get_post_meta( $ewp_id, '_ewp_attempts', true ), 'Notification envoyée non rejouée' );
remove_filter( 'pre_wp_mail', $ewp_pass_mail );
update_option( 'ewp_settings', $ewp_original );
$ewp_erased = \ExpertWP\Plugin::erase( $ewp_base['email'] );
ewp_assert( $ewp_erased['items_removed'] && null === get_post( $ewp_id ), 'Effacement effectif des données' );
\ExpertWP\Plugin::unlock( hash( 'sha256', $ewp_token ) );
wp_clear_scheduled_hook( 'ewp_unlock', array( hash( 'sha256', $ewp_token ) ) );
$ewp_origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$_SERVER['HTTP_ORIGIN'] = 'https://untrusted.example.test';
ewp_assert( ! \ExpertWP\Form::same_origin(), 'Origine externe refusée' );
$_SERVER['HTTP_ORIGIN'] = rtrim( home_url( '/' ), '/' );
ewp_assert( \ExpertWP\Form::same_origin(), 'Origine du site autorisée' );
if ( null === $ewp_origin ) {
	unset( $_SERVER['HTTP_ORIGIN'] );
} else {
	$_SERVER['HTTP_ORIGIN'] = $ewp_origin;
}
$ewp_ip = $_SERVER['REMOTE_ADDR'] ?? null;
$_SERVER['REMOTE_ADDR'] = '192.0.2.207';
$ewp_rate_key = 'ewp_rate_' . hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'], wp_salt( 'auth' ) );
delete_transient( $ewp_rate_key );
$ewp_allowed = array();
for ( $ewp_i = 0; $ewp_i < 9; ++$ewp_i ) {
	$ewp_allowed[] = \ExpertWP\Form::rate_limit();
}
ewp_assert( 8 === count( array_filter( $ewp_allowed ) ) && false === $ewp_allowed[8], 'Neuvième tentative limitée' );
delete_transient( $ewp_rate_key );
if ( null === $ewp_ip ) {
	unset( $_SERVER['REMOTE_ADDR'] );
} else {
	$_SERVER['REMOTE_ADDR'] = $ewp_ip;
}
$ewp_purge_token = \ExpertWP\Form::token();
$ewp_old_id = \ExpertWP\Form::store( $ewp_valid, $ewp_purge_token );
wp_update_post( array( 'ID' => $ewp_old_id, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 100 * DAY_IN_SECONDS ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 100 * DAY_IN_SECONDS ) ) );
update_option( 'ewp_settings', array_merge( \ExpertWP\Plugin::settings(), array( 'retention' => 90 ) ) );
\ExpertWP\Plugin::purge();
ewp_assert( null === get_post( $ewp_old_id ), 'Purge des demandes expirées' );
update_option( 'ewp_settings', $ewp_original );
\ExpertWP\Plugin::unlock( hash( 'sha256', $ewp_purge_token ) );
wp_clear_scheduled_hook( 'ewp_unlock', array( hash( 'sha256', $ewp_purge_token ) ) );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $ewp_user );
WP_CLI::log( $GLOBALS['ewp_checks'] . ' assertions, ' . $GLOBALS['ewp_failures'] . ' échec(s).' );
if ( $GLOBALS['ewp_failures'] ) {
	WP_CLI::error( 'Tests échoués.' );
}
WP_CLI::success( 'Tests d’intégration réussis.' );
