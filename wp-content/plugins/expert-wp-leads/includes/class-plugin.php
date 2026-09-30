<?php
/**
 * Business lifecycle, privacy and notifications.
 *
 * @package ExpertWP
 */

namespace ExpertWP;

defined( 'ABSPATH' ) || exit;

/** Persistent business features, independent of the theme. */
final class Plugin {

	/** Register hooks. */
	public static function boot() {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'ewp_notify', array( self::class, 'notify' ) );
		add_action( 'ewp_purge', array( self::class, 'purge' ) );
		add_action( 'ewp_unlock', array( self::class, 'unlock' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'erasers' ) );
		add_action( 'phpmailer_init', array( self::class, 'local_mail' ) );
		Form::boot();
		Admin::boot();
		Seo::boot();
	}

	/** Defaults contain no business identity or external tracking. */
	public static function settings() {
		return wp_parse_args(
			get_option( 'ewp_settings', array() ),
			array(
				'recipient'    => '',
				'retention'    => 90,
				'notify'       => 0,
				'seo'          => 1,
				'organization' => '',
			)
		);
	}

	/** Register strictly private leads and public editorial cases. */
	public static function register() {
		register_post_type(
			'ewp_lead',
			array(
				'label'               => __( 'Demandes', 'expert-wp-leads' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'supports'            => array(),
				'capabilities'        => array(
					'read_post'          => 'manage_ewp_leads',
					'edit_post'          => 'manage_ewp_leads',
					'delete_post'        => 'manage_ewp_leads',
					'edit_posts'         => 'manage_ewp_leads',
					'read_private_posts' => 'manage_ewp_leads',
					'create_posts'       => 'do_not_allow',
				),
				'map_meta_cap'        => false,
			)
		);
		register_post_type(
			'ewp_case',
			array(
				'labels'       => array(
					'name'          => __( 'Réalisations', 'expert-wp-leads' ),
					'singular_name' => __( 'Réalisation', 'expert-wp-leads' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'realisation' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'menu_icon'    => 'dashicons-portfolio',
			)
		);
		wp_register_script( 'ewp-editor', plugins_url( 'assets/editor.js', EWP_FILE ), array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), EWP_VERSION, true );
		register_block_type( EWP_DIR . 'blocks/quote', array( 'render_callback' => array( Form::class, 'render' ) ) );
		register_block_type( EWP_DIR . 'blocks/confirmation', array( 'render_callback' => array( Form::class, 'confirmation' ) ) );
	}

	/** Activate safely without creating public content. */
	public static function activate() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_ewp_leads' );
		}
		self::register();
		if ( ! wp_next_scheduled( 'ewp_purge' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ewp_purge' );
		}
		flush_rewrite_rules();
	}

	/** Pause tasks; keep commercial data on deactivation. */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ewp_purge' );
		flush_rewrite_rules();
	}

	/** Status labels. */
	public static function statuses() {
		return array(
			'new'        => 'Nouveau',
			'qualifying' => 'À qualifier',
			'qualified'  => 'Qualifié',
			'quoted'     => 'Devis envoyé',
			'won'        => 'Gagné',
			'lost'       => 'Perdu',
			'spam'       => 'Spam',
		);
	}

	/** Send a minimal notification; failures never remove a lead.
	 *
	 * @param int $id Lead ID.
	 */
	public static function notify( $id ) {
		$lead = get_post( $id );
		if ( ! $lead || 'ewp_lead' !== $lead->post_type ) {
			return;
		}
		$settings = self::settings();
		$attempts = (int) get_post_meta( $id, '_ewp_attempts', true );
		if ( ! $settings['notify'] || ! is_email( $settings['recipient'] ) || $attempts >= 3 || 'sent' === get_post_meta( $id, '_ewp_mail', true ) ) {
			return;
		}
		update_post_meta( $id, '_ewp_attempts', ++$attempts );
		$link = admin_url( 'admin.php?page=ewp-leads&lead=' . absint( $id ) );
		$sent = wp_mail( $settings['recipient'], '[WordPress] Nouvelle demande #' . absint( $id ), "Une demande est disponible dans votre administration.\n\n" . $link );
		update_post_meta( $id, '_ewp_mail', $sent ? 'sent' : 'failed' );
		if ( ! $sent && $attempts < 3 ) {
			wp_schedule_single_event( time() + 300 * $attempts, 'ewp_notify', array( $id ) );
		}
	}

	/** Delete expired leads in bounded batches. */
	public static function purge() {
		$days = max( 1, (int) self::settings()['retention'] );
		$ids  = get_posts(
			array(
				'post_type'      => 'ewp_lead',
				'post_status'    => 'private',
				'fields'         => 'ids',
				'posts_per_page' => 100,
				'date_query'     => array(
					array(
						'column'    => 'post_date_gmt',
						'before'    => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ),
						'inclusive' => false,
					),
				),
			)
		);
		foreach ( $ids as $id ) {
			wp_clear_scheduled_hook( 'ewp_notify', array( $id ) );
			wp_delete_post( $id, true );
		}
		if ( 100 === count( $ids ) ) {
			wp_schedule_single_event( time() + 60, 'ewp_purge' );
		}
	}

	/** Release submission deduplication lock.
	 *
	 * @param string $key Lock suffix.
	 */
	public static function unlock( $key ) {
		if ( preg_match( '/^[a-f0-9]{64}$/', $key ) ) {
			delete_option( '_ewp_lock_' . $key );
		}
	}

	/** Register exporter.
	 *
	 * @param array $items Exporters.
	 */
	public static function exporters( $items ) {
		$items['expert-wp'] = array(
			'exporter_friendly_name' => 'Demandes Expert WP',
			'callback'               => array( self::class, 'export' ),
		);
		return $items;
	}

	/** Register eraser.
	 *
	 * @param array $items Erasers.
	 */
	public static function erasers( $items ) {
		$items['expert-wp'] = array(
			'eraser_friendly_name' => 'Demandes Expert WP',
			'callback'             => array( self::class, 'erase' ),
		);
		return $items;
	}

	/** Find leads for a verified privacy request.
	 *
	 * @param string $email Verified email.
	 * @param int    $page Page.
	 */
	private static function personal( $email, $page ) {
		return get_posts(
			array(
				'post_type'      => 'ewp_lead',
				'post_status'    => 'private',
				'posts_per_page' => 50,
				'paged'          => $page,
				'meta_key'       => '_ewp_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Verified privacy request, bounded batch.
				'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Verified privacy request, bounded batch.
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
	}

	/** Export personal data via WordPress privacy tools.
	 *
	 * @param string $email Verified email.
	 * @param int    $page Page.
	 */
	public static function export( $email, $page = 1 ) {
		$posts = self::personal( $email, $page );
		$data  = array();
		foreach ( $posts as $post ) {
			$fields = get_post_meta( $post->ID, '_ewp_data', true );
			$values = array();
			foreach ( (array) $fields as $key => $value ) {
				$values[] = array(
					'name'  => $key,
					'value' => (string) $value,
				);
			}
			$values[] = array(
				'name'  => 'Date',
				'value' => $post->post_date_gmt,
			);
			$values[] = array(
				'name'  => 'Statut',
				'value' => get_post_meta( $post->ID, '_ewp_status', true ),
			);
			$data[]   = array(
				'group_id'    => 'ewp-leads',
				'group_label' => 'Demandes',
				'item_id'     => 'lead-' . $post->ID,
				'data'        => $values,
			);
		}
		return array(
			'data' => $data,
			'done' => count( $posts ) < 50,
		);
	}

	/** Erase matching records; always fetch page one as records disappear.
	 *
	 * @param string $email Verified email.
	 */
	public static function erase( $email ) {
		$posts    = self::personal( $email, 1 );
		$retained = false;
		foreach ( $posts as $post ) {
			wp_clear_scheduled_hook( 'ewp_notify', array( $post->ID ) );
			if ( ! wp_delete_post( $post->ID, true ) ) {
				$retained = true;
			}
		}
		return array(
			'items_removed'  => ! empty( $posts ),
			'items_retained' => $retained,
			'messages'       => $retained ? array( 'Un effacement a échoué ; vérifier les permissions de la base.' ) : array(),
			'done'           => count( $posts ) < 50,
		);
	}

	/** Docker-only mail capture, explicitly enabled by environment constant.
	 *
	 * @param object $mailer PHPMailer.
	 */
	public static function local_mail( $mailer ) {
		if ( 'local' === wp_get_environment_type() && defined( 'EWP_MAILPIT' ) && EWP_MAILPIT ) {
			$mailer->isSMTP();
			$mailer->Host     = 'mailpit'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API property.
			$mailer->Port     = 1025; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API property.
			$mailer->SMTPAuth = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API property.
		}
	}
}
