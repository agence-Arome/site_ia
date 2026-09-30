<?php
/**
 * Public forms, server validation and abuse controls.
 *
 * @package ExpertWP
 */

namespace ExpertWP;

defined( 'ABSPATH' ) || exit;

/** Public submission boundary. */
final class Form {
	/** Read a validated anonymous session identifier. */
	private static function cookie() {
		$value = isset( $_COOKIE['ewp_session'] ) && is_string( $_COOKIE['ewp_session'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ewp_session'] ) ) : '';
		return preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : '';
	}

	/** Reject cross-origin browser requests, including sibling subdomains. */
	public static function same_origin() {
		if ( ! isset( $_SERVER['HTTP_ORIGIN'] ) ) {
			return true;
		}
		$origin = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
		$parts  = wp_parse_url( home_url( '/' ) );
		$site   = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		return hash_equals( $site, rtrim( $origin, '/' ) );
	}

	/** Wire submission and token refresh. */
	public static function boot() {
		add_action(
			'template_redirect',
			static function () {
				if ( is_singular() && ( has_block( 'expert-wp/quote' ) || has_block( 'expert-wp/confirmation' ) ) ) {
					self::session();
				}
			}
		);
		add_action( 'admin_post_nopriv_ewp_submit', array( self::class, 'submit' ) );
		add_action( 'admin_post_ewp_submit', array( self::class, 'submit' ) );
		add_action(
			'rest_api_init',
			static function () {
				register_rest_route(
					'expert-wp/v1',
					'/token',
					array(
						'methods'             => 'GET',
						'permission_callback' => static function () {
							return self::same_origin() ? true : new \WP_Error( 'origin', 'Origine refusée.', array( 'status' => 403 ) );
						},
						'callback'            => static function () {
							$response = new \WP_REST_Response( array( 'token' => self::token() ) );
							$response->header( 'Cache-Control', 'no-store, private' );
							return $response;
						},
					)
				);
			}
		);
	}

	/** Session cookie binds tokens to a browser, including anonymous visitors. */
	public static function session() {
		if ( isset( $_COOKIE['ewp_session'] ) && is_string( $_COOKIE['ewp_session'] ) && preg_match( '/^[a-f0-9]{64}$/', self::cookie() ) ) {
			return;
		}
		if ( headers_sent() ) {
			return;
		}
		$value = bin2hex( random_bytes( 32 ) );
		setcookie(
			'ewp_session',
			$value,
			array(
				'expires'  => 0,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE['ewp_session'] = $value;
	}

	/** Issue a short-lived HMAC token. */
	public static function token() {
		self::session();
		$body = time() . '.' . wp_generate_uuid4();
		return $body . '.' . hash_hmac( 'sha256', $body . '|' . ( self::cookie() ), wp_salt( 'nonce' ) );
	}

	/** Verify age and browser binding; WP nonces alone cannot protect guest submissions.
	 *
	 * @param string $token Signed token.
	 */
	public static function verify_token( $token ) {
		$parts = explode( '.', $token );
		if ( 3 !== count( $parts ) || ! ctype_digit( $parts[0] ) || empty( $_COOKIE['ewp_session'] ) || ! preg_match( '/^[a-f0-9-]{36}$/', $parts[1] ) ) {
			return false;
		}
		$age      = time() - (int) $parts[0];
		$expected = hash_hmac( 'sha256', $parts[0] . '.' . $parts[1] . '|' . self::cookie(), wp_salt( 'nonce' ) );
		return $age >= 0 && $age <= 2 * HOUR_IN_SECONDS && hash_equals( $expected, $parts[2] );
	}

	/** Services and their one essential qualification question. */
	public static function services() {
		return array(
			'creation'      => array(
				'label'    => 'Création de site WordPress',
				'question' => 'Quel est l’objectif principal du nouveau site ?',
			),
			'refonte'       => array(
				'label'    => 'Refonte de site WordPress',
				'question' => 'Que souhaitez-vous améliorer sur votre site actuel ?',
			),
			'depannage'     => array(
				'label'    => 'Dépannage de site piraté',
				'question' => 'Quels symptômes avez-vous constatés et depuis quand ?',
			),
			'securite'      => array(
				'label'    => 'Audit et sécurisation',
				'question' => 'Quel périmètre souhaitez-vous faire auditer ?',
			),
			'maintenance'   => array(
				'label'    => 'Maintenance et mises à jour',
				'question' => 'Quel suivi recherchez-vous et à quelle fréquence ?',
			),
			'developpement' => array(
				'label'    => 'Développement sur mesure',
				'question' => 'Quelle fonctionnalité ou intégration souhaitez-vous créer ?',
			),
		);
	}

	/** Read scalar, bounded input; arrays are never silently accepted.
	 *
	 * @param array  $input Input.
	 * @param string $key Key.
	 */
	private static function value( $input, $key ) {
		return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? trim( $input[ $key ] ) : '';
	}

	/** Validate raw unslashed input using allowlists and field-specific limits.
	 *
	 * @param array $input Unslashed input.
	 * @return array|\WP_Error Valid data or field errors.
	 */
	public static function validate( $input ) {
		$errors = new \WP_Error();
		$mode   = self::value( $input, 'mode' );
		if ( ! in_array( $mode, array( 'quote', 'contact' ), true ) ) {
			$errors->add( 'mode', 'Le type de demande est invalide.' );
		}
		$data = array( 'mode' => $mode );
		foreach ( array(
			'name'    => 120,
			'email'   => 254,
			'company' => 160,
			'phone'   => 40,
			'website' => 500,
			'message' => 4000,
			'details' => 2000,
		) as $key => $limit ) {
			$value = self::value( $input, $key );
			if ( strlen( $value ) > $limit || ( isset( $input[ $key ] ) && ! is_string( $input[ $key ] ) ) ) {
				$errors->add( $key, 'Un champ dépasse la longueur autorisée ou possède un format incorrect : ' . $key . '.' );
			}
			$data[ $key ] = in_array( $key, array( 'message', 'details' ), true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
		}
		if ( '' === $data['name'] ) {
			$errors->add( 'name', 'Indiquez votre nom.' );
		}
		if ( ! is_email( $data['email'] ) ) {
			$errors->add( 'email', 'Indiquez une adresse e-mail valide.' );
		}
		if ( strlen( $data['message'] ) < 10 ) {
			$errors->add( 'message', 'Décrivez votre besoin en au moins 10 caractères.' );
		}
		if ( $data['website'] && ( ! filter_var( $data['website'], FILTER_VALIDATE_URL ) || ! in_array( wp_parse_url( $data['website'], PHP_URL_SCHEME ), array( 'https', 'http' ), true ) ) ) {
			$errors->add( 'website', 'Indiquez une URL complète commençant par https:// ou http://.' );
		}
		$data['service'] = 'contact' === $mode ? 'contact' : self::value( $input, 'service' );
		if ( 'quote' === $mode && ! isset( self::services()[ $data['service'] ] ) ) {
			$errors->add( 'service', 'Sélectionnez une prestation.' );
		}
		if ( 'quote' === $mode && strlen( $data['details'] ) < 5 ) {
			$errors->add( 'details', 'Répondez à la question concernant votre prestation.' );
		}
		foreach ( array(
			'budget'   => array( '', 'a-definir', 'moins-2000', '2000-5000', '5000-10000', 'plus-10000' ),
			'timeline' => array( '', 'urgent', '1-mois', '3-mois', 'flexible' ),
		) as $key => $allowed ) {
			$data[ $key ] = 'contact' === $mode ? '' : self::value( $input, $key );
			if ( ( isset( $input[ $key ] ) && ! is_string( $input[ $key ] ) ) || ! in_array( $data[ $key ], $allowed, true ) ) {
				$errors->add( $key, 'Sélectionnez une option proposée pour le budget ou le délai.' );
			}
		}
		if ( '1' !== self::value( $input, 'privacy' ) ) {
			$errors->add( 'privacy', 'Veuillez prendre connaissance de l’information sur les données personnelles.' );
		}
		if ( 'contact' === $mode ) {
			$data['details'] = '';
		}
		$data['notice_version'] = '2026-09-v1';
		return $errors->has_errors() ? $errors : $data;
	}

	/** Apply temporary pseudonymous rate limit; no IP address is persisted.
	 *
	 * @return bool Whether request is allowed.
	 */
	public static function rate_limit() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key    = 'ewp_rate_' . hash_hmac( 'sha256', $remote, wp_salt( 'auth' ) );
		$state  = get_transient( $key );
		if ( ! is_array( $state ) ) {
			$state = array(
				'count'   => 0,
				'expires' => time() + 10 * MINUTE_IN_SECONDS,
			);
		}
		++$state['count'];
		set_transient( $key, $state, max( 1, $state['expires'] - time() ) );
		return $state['count'] <= 8;
	}

	/** Save validated data with atomic duplicate protection.
	 *
	 * @param array  $data Validated data.
	 * @param string $token Submission token.
	 * @return int|\WP_Error Lead ID.
	 */
	public static function store( $data, $token ) {
		$key  = hash( 'sha256', $token );
		$lock = '_ewp_lock_' . $key;
		if ( ! add_option( $lock, 'pending', '', false ) ) {
			$previous = get_option( $lock );
			return is_numeric( $previous ) ? (int) $previous : new \WP_Error( 'pending', 'Cette demande est déjà en cours. Patientez quelques secondes.' );
		}
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'ewp_unlock', array( $key ) );
		$id = wp_insert_post(
			array(
				'post_type'   => 'ewp_lead',
				'post_status' => 'private',
				'post_title'  => 'Demande ' . wp_generate_uuid4(),
				'post_author' => 0,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			delete_option( $lock );
			return new \WP_Error( 'storage', 'La demande n’a pas pu être enregistrée. Veuillez réessayer.' );
		}
		$saved       = add_post_meta( $id, '_ewp_data', wp_slash( $data ), true );
		$email_saved = add_post_meta( $id, '_ewp_email', $data['email'], true );
		if ( ! $saved || ! $email_saved ) {
			wp_delete_post( $id, true );
			delete_option( $lock );
			return new \WP_Error( 'storage', 'L’enregistrement est incomplet. Veuillez réessayer.' );
		}
		update_post_meta( $id, '_ewp_service', $data['service'] );
		update_post_meta( $id, '_ewp_status', 'new' );
		update_post_meta( $id, '_ewp_mail', 'pending' );
		update_option( $lock, $id, false );
		wp_schedule_single_event( time() + 1, 'ewp_notify', array( $id ) );
		return $id;
	}

	/** Public endpoint: validate before writing or notifying. */
	public static function submit() {
		nocache_headers();
		if ( ! self::same_origin() ) {
			wp_die( 'Origine refusée.', '', array( 'response' => 403 ) );
		}
		if ( 'production' === wp_get_environment_type() && ! get_privacy_policy_url() ) {
			wp_die( 'Les demandes en ligne sont temporairement indisponibles.', '', array( 'response' => 503 ) );
		}
		if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			wp_die( 'Méthode non autorisée.', '', array( 'response' => 405 ) );
		}
		if ( absint( $_SERVER['CONTENT_LENGTH'] ?? 0 ) > 20000 ) {
			wp_die( 'Demande trop volumineuse.', '', array( 'response' => 413 ) );
		}
		// Public endpoint uses a session-bound HMAC token rather than a shared guest WP nonce.
		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$token = self::value( $input, 'token' );
		if ( ! self::rate_limit() ) {
			self::failure( new \WP_Error( 'rate', 'Trop de tentatives. Réessayez dans dix minutes.' ), $input, 429 );
		}
		if ( ! self::verify_token( $token ) ) {
			self::failure( new \WP_Error( 'token', 'Le formulaire a expiré. Vérifiez vos informations et envoyez-le à nouveau.' ), $input, 403 );
		}
		if ( '' !== self::value( $input, 'fax' ) ) {
			self::failure( new \WP_Error( 'spam', 'La demande a été refusée par la protection anti-spam.' ), array(), 400 );
		}
		$data = self::validate( $input );
		if ( is_wp_error( $data ) ) {
			self::failure( $data, $input, 422 );
		}
		$id = self::store( $data, $token );
		if ( is_wp_error( $id ) ) {
			self::failure( $id, $input, 503 );
		}
		$conversion_key = hash( 'sha256', 'conversion|' . $token );
		if ( ! add_option( '_ewp_lock_' . $conversion_key, 'issued', '', false ) ) {
			wp_safe_redirect( home_url( '/merci/' ), 303 );
			exit;
		}
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'ewp_unlock', array( $conversion_key ) );
		$receipt = wp_generate_uuid4();
		set_transient( 'ewp_receipt_' . hash( 'sha256', $receipt ), hash( 'sha256', self::cookie() ), 10 * MINUTE_IN_SECONDS );
		setcookie(
			'ewp_receipt',
			$receipt,
			array(
				'expires'  => time() + 600,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		wp_safe_redirect( home_url( '/merci/' ), 303 );
		exit;
	}

	/** Re-render safe values on error, never put personal data in URLs.
	 *
	 * @param \WP_Error $error Error.
	 * @param array     $input Input.
	 * @param int       $status HTTP status.
	 */
	private static function failure( $error, $input, $status ) {
		status_header( $status );
		$mode    = 'contact' === self::value( $input, 'mode' ) ? 'contact' : 'quote';
		$content = '<h1>Vérifiez votre demande</h1><div role="alert"><ul>';
		foreach ( $error->get_error_messages() as $message ) {
			$content .= '<li>' . esc_html( $message ) . '</li>';
		}
		$content .= '</ul></div>' . self::render( array( 'mode' => $mode ), '', null, $input );
		wp_die( $content, 'Votre demande', array( 'response' => $status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Form renderer escapes every value.
	}

	/** Server-rendered form; JavaScript progressively updates conditional labels.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Block content.
	 * @param mixed  $block Block.
	 * @param array  $values Values after a failed submission.
	 */
	public static function render( $attributes = array(), $content = '', $block = null, $values = array() ) {
		if ( 'production' === wp_get_environment_type() && ! get_privacy_policy_url() ) {
			return '<p>Les demandes en ligne sont temporairement indisponibles.</p>';
		}
		$mode = 'contact' === ( $attributes['mode'] ?? '' ) ? 'contact' : 'quote';
		wp_enqueue_style( 'ewp-form', plugins_url( 'assets/form.css', EWP_FILE ), array(), EWP_VERSION );
		wp_enqueue_script( 'ewp-form', plugins_url( 'assets/form.js', EWP_FILE ), array(), EWP_VERSION, true );
		$prefix   = wp_unique_id( 'ewp-' );
		$selected = self::value( $values, 'service' );
		if ( ! $selected && isset( $_GET['service'] ) && is_string( $_GET['service'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presentation-only preselection.
			$selected = sanitize_key( wp_unslash( $_GET['service'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		ob_start();
		?>
		<form class="ewp-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-token-url="<?php echo esc_url( rest_url( 'expert-wp/v1/token' ) ); ?>">
			<input type="hidden" name="action" value="ewp_submit">
			<input type="hidden" name="mode" value="<?php echo esc_attr( $mode ); ?>">
			<input type="hidden" name="token" value="<?php echo esc_attr( self::token() ); ?>">
			<p class="ewp-form-intro">Les champs marqués d’un astérisque sont obligatoires. Ne transmettez aucun mot de passe.</p>
			<div class="ewp-trap" aria-hidden="true"><label>Fax<input type="text" name="fax" tabindex="-1" autocomplete="off"></label></div>
			<?php if ( 'quote' === $mode ) : ?>
			<label for="<?php echo esc_attr( $prefix ); ?>service">Votre prestation *</label>
			<select id="<?php echo esc_attr( $prefix ); ?>service" name="service" required>
				<option value="">Choisissez une prestation</option>
				<?php foreach ( self::services() as $key => $service ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" data-question="<?php echo esc_attr( $service['question'] ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $service['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<div class="ewp-condition"><label data-question-label for="<?php echo esc_attr( $prefix ); ?>details"><?php echo esc_html( self::services()[ $selected ]['question'] ?? 'Précisez votre besoin pour la prestation choisie.' ); ?> *</label><textarea id="<?php echo esc_attr( $prefix ); ?>details" name="details" rows="3" maxlength="2000" required><?php echo esc_textarea( self::value( $values, 'details' ) ); ?></textarea></div>
			<?php endif; ?>
			<div class="ewp-form-grid">
			<?php
			foreach ( array(
				'name'    => array( 'Votre nom *', 'text', 'name', 120 ),
				'email'   => array( 'Votre e-mail *', 'email', 'email', 254 ),
				'company' => array( 'Entreprise', 'text', 'organization', 160 ),
				'phone'   => array( 'Téléphone (facultatif)', 'tel', 'tel', 40 ),
				'website' => array( 'Adresse de votre site', 'url', 'url', 500 ),
			) as $key => $field ) :
				?>
				<div><label for="<?php echo esc_attr( $prefix . $key ); ?>"><?php echo esc_html( $field[0] ); ?></label><input id="<?php echo esc_attr( $prefix . $key ); ?>" type="<?php echo esc_attr( $field[1] ); ?>" name="<?php echo esc_attr( $key ); ?>" autocomplete="<?php echo esc_attr( $field[2] ); ?>" maxlength="<?php echo esc_attr( $field[3] ); ?>" value="<?php echo esc_attr( self::value( $values, $key ) ); ?>" <?php echo in_array( $key, array( 'name', 'email' ), true ) ? 'required' : ''; ?>></div>
			<?php endforeach; ?>
			</div>
			<?php if ( 'quote' === $mode ) : ?>
			<div class="ewp-form-grid">
				<?php
				foreach ( array(
					'budget'   => array(
						'Budget envisagé',
						array(
							''           => 'Facultatif',
							'a-definir'  => 'À définir ensemble',
							'moins-2000' => 'Moins de 2 000 €',
							'2000-5000'  => '2 000 à 5 000 €',
							'5000-10000' => '5 000 à 10 000 €',
							'plus-10000' => 'Plus de 10 000 €',
						),
					),
					'timeline' => array(
						'Délai souhaité',
						array(
							''         => 'Facultatif',
							'urgent'   => 'Besoin urgent',
							'1-mois'   => 'Dans un mois',
							'3-mois'   => 'Dans trois mois',
							'flexible' => 'Flexible',
						),
					),
				) as $key => $field ) :
					?>
				<div><label for="<?php echo esc_attr( $prefix . $key ); ?>"><?php echo esc_html( $field[0] ); ?></label><select id="<?php echo esc_attr( $prefix . $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
					<?php
					foreach ( $field[1] as $value => $label ) :
						?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( self::value( $values, $key ), $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
			<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<label for="<?php echo esc_attr( $prefix ); ?>message">Parlez-nous de votre projet *</label>
			<textarea id="<?php echo esc_attr( $prefix ); ?>message" name="message" rows="5" minlength="10" maxlength="4000" required><?php echo esc_textarea( self::value( $values, 'message' ) ); ?></textarea>
			<label class="ewp-check"><input type="checkbox" name="privacy" value="1" required <?php checked( self::value( $values, 'privacy' ), '1' ); ?>><span>J’ai pris connaissance de la <a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/confidentialite/' ) ); ?>">politique de confidentialité</a>. Ces informations servent à traiter ma demande. *</span></label>
			<button type="submit">Envoyer ma demande <span aria-hidden="true">↗</span></button>
			<p class="ewp-form-note">Un premier échange pour préciser votre besoin. Aucun engagement à ce stade.</p>
			<p class="ewp-form-status" role="status" aria-live="polite"></p>
		</form>
		<?php
		return ob_get_clean();
	}

	/** Only server-confirmed, session-bound receipts emit one conversion event. */
	public static function confirmation() {
		$receipt  = isset( $_COOKIE['ewp_receipt'] ) && is_string( $_COOKIE['ewp_receipt'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ewp_receipt'] ) ) : '';
		$key      = 'ewp_receipt_' . hash( 'sha256', $receipt );
		$expected = get_transient( $key );
		if ( ! $receipt || ! $expected || ! hash_equals( $expected, hash( 'sha256', self::cookie() ) ) ) {
			return '<p>Vous avez un projet ? <a href="' . esc_url( home_url( '/devis/' ) ) . '">Présentez-nous votre besoin.</a></p>';
		}
		$claim = hash( 'sha256', 'receipt|' . $receipt );
		if ( ! add_option( '_ewp_lock_' . $claim, 'consumed', '', false ) ) {
			return '<p>Votre demande a déjà été enregistrée.</p>';
		}
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'ewp_unlock', array( $claim ) );
		delete_transient( $key );
		wp_enqueue_script( 'ewp-conversion', plugins_url( 'assets/conversion.js', EWP_FILE ), array(), EWP_VERSION, true );
		return '<div class="ewp-success" role="status"><p><strong>Votre demande a bien été enregistrée.</strong></p><p>Nous disposons des informations nécessaires pour préparer notre échange.</p></div>';
	}
}
