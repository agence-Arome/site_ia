<?php
/**
 * Lead management and configuration.
 *
 * @package ExpertWP
 */

namespace ExpertWP;

defined( 'ABSPATH' ) || exit;

/** All commercial data stays behind a dedicated capability. */
final class Admin {
	/** Register hooks. */
	public static function boot() {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_ewp_status', array( self::class, 'save' ) );
		add_action( 'admin_init', array( self::class, 'settings' ) );
	}

	/** Navigation. */
	public static function menu() {
		add_menu_page( 'Demandes', 'Demandes', 'manage_ewp_leads', 'ewp-leads', array( self::class, 'page' ), 'dashicons-email-alt', 26 );
		add_submenu_page( 'ewp-leads', 'Réglages', 'Réglages', 'manage_options', 'ewp-settings', array( self::class, 'settings_page' ) );
	}

	/** Settings API supplies nonce and manage_options checks. */
	public static function settings() {
		register_setting(
			'ewp',
			'ewp_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/** Validate settings without accepting mail header injection.
	 *
	 * @param array $input Settings.
	 */
	public static function sanitize( $input ) {
		$old   = Plugin::settings();
		$input = is_array( $input ) ? $input : array();
		$email = isset( $input['recipient'] ) && is_string( $input['recipient'] ) ? trim( $input['recipient'] ) : '';
		if ( $email && ! is_email( $email ) ) {
			add_settings_error( 'ewp', 'email', 'Adresse de notification invalide : la précédente est conservée.' );
			$email = $old['recipient'];
		}
		return array(
			'recipient'    => $email,
			'notify'       => empty( $input['notify'] ) ? 0 : 1,
			'retention'    => max( 1, min( 1095, absint( $input['retention'] ?? 90 ) ) ),
			'seo'          => empty( $input['seo'] ) ? 0 : 1,
			'organization' => isset( $input['organization'] ) && is_string( $input['organization'] ) ? sanitize_text_field( $input['organization'] ) : '',
		);
	}

	/** Configuration view. */
	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
		}
		$settings = Plugin::settings();
		?>
		<div class="wrap"><h1>Réglages des demandes</h1>
		<?php settings_errors( 'ewp' ); ?>
		<p>Configurez un transport SMTP ou transactionnel auprès de votre hébergeur. « Accepté par le transport » ne garantit pas la livraison en boîte de réception.</p>
		<form action="options.php" method="post">
		<?php settings_fields( 'ewp' ); ?>
		<table class="form-table" role="presentation">
		<tr><th><label for="ewp-recipient">Destinataire</label></th><td><input class="regular-text" id="ewp-recipient" type="email" name="ewp_settings[recipient]" value="<?php echo esc_attr( $settings['recipient'] ); ?>"></td></tr>
		<tr><th>Notifications</th><td><label><input type="checkbox" name="ewp_settings[notify]" value="1" <?php checked( $settings['notify'], 1 ); ?>> Activer les notifications internes, sans copie du contenu personnel</label></td></tr>
		<tr><th><label for="ewp-retention">Conservation en jours</label></th><td><input id="ewp-retention" type="number" name="ewp_settings[retention]" min="1" max="1095" value="<?php echo esc_attr( $settings['retention'] ); ?>"><p class="description">Suppression définitive après ce délai depuis la réception, tous statuts confondus. Valeur provisoire : 90 jours. Alignez la politique de confidentialité et les sauvegardes.</p></td></tr>
		<tr><th>SEO essentiel</th><td><label><input type="checkbox" name="ewp_settings[seo]" value="1" <?php checked( $settings['seo'], 1 ); ?>> Activer les descriptions et données structurées (désactiver si une extension SEO prend le relais)</label></td></tr>
		<tr><th><label for="ewp-organization">Nom réel de l’entreprise</label></th><td><input id="ewp-organization" class="regular-text" name="ewp_settings[organization]" value="<?php echo esc_attr( $settings['organization'] ); ?>"><p class="description">Laisser vide tant que l’identité n’est pas confirmée. Aucun balisage Organization ne sera alors produit.</p></td></tr>
		</table><?php submit_button(); ?></form></div>
		<?php
	}

	/** Read harmless filter inputs.
	 *
	 * @param string $key Query key.
	 */
	private static function query( $key ) {
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filtering behind capability check.
	}

	/** List or detail page. */
	public static function page() {
		if ( ! current_user_can( 'manage_ewp_leads' ) ) {
			wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap"><h1>Demandes reçues</h1>';
		$id = absint( self::query( 'lead' ) );
		if ( $id ) {
			self::detail( $id );
			echo '</div>';
			return;
		}
		$status  = self::query( 'status' );
		$service = self::query( 'service' );
		$search  = self::query( 's' );
		$meta    = array( 'relation' => 'AND' );
		if ( isset( Plugin::statuses()[ $status ] ) ) {
			$meta[] = array(
				'key'   => '_ewp_status',
				'value' => $status,
			);
		}
		if ( isset( Form::services()[ $service ] ) || 'contact' === $service ) {
			$meta[] = array(
				'key'   => '_ewp_service',
				'value' => $service,
			);
		}
		if ( $search ) {
			$meta[] = array(
				'key'     => '_ewp_email',
				'value'   => substr( $search, 0, 254 ),
				'compare' => 'LIKE',
			);
		}
		$args = array(
			'post_type'      => 'ewp_lead',
			'post_status'    => 'private',
			'posts_per_page' => 20,
			'paged'          => max( 1, absint( self::query( 'paged' ) ) ),
			'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded admin-only MVP query.
		);
		$date = self::query( 'after' );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$args['date_query'] = array(
				array(
					'after'     => $date,
					'inclusive' => true,
				),
			);
		}
		$query = new \WP_Query( $args );
		?>
		<form method="get"><input type="hidden" name="page" value="ewp-leads">
		<label>Statut <select name="status"><option value="">Tous</option>
		<?php
		foreach ( Plugin::statuses() as $key => $label ) :
			?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
		<label>Service <select name="service"><option value="">Tous</option>
		<?php
		foreach ( array_merge( Form::services(), array( 'contact' => array( 'label' => 'Contact' ) ) ) as $key => $item ) :
			?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $service, $key ); ?>><?php echo esc_html( $item['label'] ); ?></option><?php endforeach; ?></select></label>
		<label>Depuis <input type="date" name="after" value="<?php echo esc_attr( $date ); ?>"></label>
		<label>E-mail <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"></label><button class="button">Filtrer</button>
		</form>
		<p><?php echo esc_html( (string) $query->found_posts ); ?> demande(s) correspondant aux filtres.</p>
		<table class="widefat striped"><thead><tr><th scope="col">Référence</th><th scope="col">Date</th><th scope="col">Service</th><th scope="col">Contact</th><th scope="col">Statut</th><th scope="col">Notification</th></tr></thead><tbody>
		<?php
		foreach ( $query->posts as $post ) :
			$data = get_post_meta( $post->ID, '_ewp_data', true );
			?>
			<tr><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=ewp-leads&lead=' . $post->ID ) ); ?>">#<?php echo esc_html( (string) $post->ID ); ?></a></td><td><?php echo esc_html( get_the_date( 'd/m/Y H:i', $post ) ); ?></td><td><?php echo esc_html( Form::services()[ $data['service'] ?? '' ]['label'] ?? 'Contact' ); ?></td><td><?php echo esc_html( $data['name'] ?? '' ); ?><br><?php echo esc_html( $data['email'] ?? '' ); ?></td><td><?php echo esc_html( Plugin::statuses()[ get_post_meta( $post->ID, '_ewp_status', true ) ] ?? 'Nouveau' ); ?></td><td><?php echo esc_html( self::mail_label( $post->ID ) ); ?></td></tr>
		<?php endforeach; ?>
		<?php
		if ( ! $query->posts ) :
			?>
			<tr><td colspan="6">Aucune demande pour ces filtres.</td></tr><?php endif; ?>
		</tbody></table>
		<?php
		echo wp_kses_post(
			paginate_links(
				array(
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $args['paged'],
					'total'   => $query->max_num_pages,
				)
			)
		);
		echo '</div>';
	}

	/** Human-readable transport state.
	 *
	 * @param int $id Lead.
	 */
	private static function mail_label( $id ) {
		return array(
			'sent'    => 'Accepté par le transport',
			'failed'  => 'Échec du transport',
			'pending' => Plugin::settings()['notify'] ? 'En attente' : 'Désactivée',
		)[ get_post_meta( $id, '_ewp_mail', true ) ] ?? 'Non envoyée';
	}

	/** Detail view.
	 *
	 * @param int $id Lead ID.
	 */
	private static function detail( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'ewp_lead' !== $post->post_type ) {
			echo '<p>Demande introuvable.</p>';
			return;
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ewp-leads' ) ) . '">← Toutes les demandes</a></p><h2>Demande #' . esc_html( (string) $id ) . '</h2><dl>';
		$labels = array(
			'mode'           => 'Type',
			'name'           => 'Nom',
			'email'          => 'E-mail',
			'company'        => 'Entreprise',
			'phone'          => 'Téléphone',
			'website'        => 'Site',
			'message'        => 'Projet',
			'details'        => 'Précisions',
			'service'        => 'Prestation',
			'budget'         => 'Budget',
			'timeline'       => 'Délai',
			'notice_version' => 'Version de la notice',
		);
		foreach ( (array) get_post_meta( $id, '_ewp_data', true ) as $key => $value ) {
			echo '<dt><strong>' . esc_html( $labels[ $key ] ?? $key ) . '</strong></dt><dd style="white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html( $value ) . '</dd>';
		}
		echo '</dl><p>Notification : ' . esc_html( self::mail_label( $id ) ) . '</p>';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ewp_status"><input type="hidden" name="lead" value="<?php echo esc_attr( $id ); ?>">
		<?php wp_nonce_field( 'ewp_lead_' . $id ); ?>
		<label for="ewp-status">Statut</label><select name="status" id="ewp-status">
		<?php
		foreach ( Plugin::statuses() as $key => $label ) :
			?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_post_meta( $id, '_ewp_status', true ), $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<button class="button button-primary" name="operation" value="status">Enregistrer</button>
		<button class="button" name="operation" value="retry">Relancer la notification</button>
		<p><label><input type="checkbox" name="confirm_delete" value="1"> Je confirme l’effacement définitif de cette demande</label> <button class="button" name="operation" value="delete">Effacer</button></p>
		</form><h3>Historique des statuts</h3><ul>
		<?php
		$history = get_post_meta( $id, '_ewp_history', true );
		foreach ( is_array( $history ) ? $history : array() as $entry ) :
			?>
		<li><?php echo esc_html( $entry['at'] . ' UTC — ' . ( Plugin::statuses()[ $entry['status'] ] ?? $entry['status'] ) . ' — utilisateur #' . $entry['by'] ); ?></li>
		<?php endforeach; ?></ul>
		<?php
	}

	/** Protected mutations. */
	public static function save() {
		if ( ! current_user_can( 'manage_ewp_leads' ) ) {
			wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
		}
		$id = isset( $_POST['lead'] ) ? absint( $_POST['lead'] ) : 0;
		check_admin_referer( 'ewp_lead_' . $id );
		if ( 'ewp_lead' !== get_post_type( $id ) ) {
			wp_die( 'Demande introuvable.', '', array( 'response' => 404 ) );
		}
		$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( $_POST['operation'] ) : '';
		if ( 'delete' === $operation && isset( $_POST['confirm_delete'] ) && '1' === $_POST['confirm_delete'] ) {
			wp_clear_scheduled_hook( 'ewp_notify', array( $id ) );
			wp_delete_post( $id, true );
			$id = 0;
		} elseif ( 'retry' === $operation ) {
			wp_clear_scheduled_hook( 'ewp_notify', array( $id ) );
			update_post_meta( $id, '_ewp_attempts', 0 );
			update_post_meta( $id, '_ewp_mail', 'pending' );
			wp_schedule_single_event( time() + 1, 'ewp_notify', array( $id ) );
		} elseif ( 'status' === $operation ) {
			$status = isset( $_POST['status'] ) && is_string( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
			if ( ! isset( Plugin::statuses()[ $status ] ) ) {
				wp_die( 'Statut invalide.', '', array( 'response' => 400 ) );
			}
			if ( get_post_meta( $id, '_ewp_status', true ) !== $status ) {
				$history   = get_post_meta( $id, '_ewp_history', true );
				$history   = is_array( $history ) ? $history : array();
				$history[] = array(
					'at'     => gmdate( 'Y-m-d H:i:s' ),
					'status' => $status,
					'by'     => get_current_user_id(),
				);
				update_post_meta( $id, '_ewp_history', array_slice( $history, -100 ) );
				update_post_meta( $id, '_ewp_status', $status );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ewp-leads' . ( $id ? '&lead=' . $id : '' ) ) );
		exit;
	}
}
