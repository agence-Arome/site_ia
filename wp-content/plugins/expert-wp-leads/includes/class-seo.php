<?php
/**
 * Technical SEO with explicit ownership and privacy exclusions.
 *
 * @package ExpertWP
 */

namespace ExpertWP;

defined( 'ABSPATH' ) || exit;

/** Minimal SEO, no fabricated ratings or organization data. */
final class Seo {
	/** Register integration. */
	public static function boot() {
		add_action( 'wp_head', array( self::class, 'head' ), 5 );
		add_filter( 'wp_robots', array( self::class, 'robots' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( self::class, 'sitemap' ), 10, 2 );
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post', array( self::class, 'save' ) );
		add_action( 'template_redirect', array( self::class, 'no_cache' ) );
		add_filter( 'pre_get_document_title', array( self::class, 'title' ) );
	}

	/** Avoid simultaneous ownership by common SEO suites. */
	private static function enabled() {
		return Plugin::settings()['seo'] && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'AIOSEO_VERSION' );
	}

	/** Fresh tokens and one-time confirmations must never be page-cached. */
	public static function no_cache() {
		if ( is_page( array( 'devis', 'contact', 'merci' ) ) || ( is_singular() && has_block( 'expert-wp/quote' ) ) ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
		}
	}

	/** Robots rules.
	 *
	 * @param array $robots Directives.
	 */
	public static function robots( $robots ) {
		if ( 'production' !== wp_get_environment_type() || is_page( 'merci' ) || is_search() ) {
			$robots['noindex'] = true;
			unset( $robots['index'] );
		}
		return $robots;
	}

	/** Exclude confirmation from sitemap.
	 *
	 * @param array  $args Query args.
	 * @param string $type Post type.
	 */
	public static function sitemap( $args, $type ) {
		if ( 'page' === $type ) {
			$page = get_page_by_path( 'merci' );
			if ( $page ) {
				$args['post__not_in'][] = $page->ID;
			}
		}
		return $args;
	}

	/** Optional editorial title.
	 *
	 * @param string $title Original title.
	 */
	public static function title( $title ) {
		$custom = self::enabled() && is_singular() ? get_post_meta( get_queried_object_id(), '_ewp_seo_title', true ) : '';
		return $custom ? $custom : $title;
	}

	/** Emit safe metadata; WordPress owns canonical URLs on singular pages. */
	public static function head() {
		if ( ! self::enabled() || ! is_singular() || is_page( 'merci' ) ) {
			return;
		}
		$id          = get_queried_object_id();
		$description = get_post_meta( $id, '_ewp_description', true );
		if ( ! $description ) {
			$description = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 28, '…' );
		}
		$url = get_permalink( $id );
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
		if ( has_post_thumbnail( $id ) ) {
			echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( $id, 'large' ) ) . '">' . "\n";
		}
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => is_singular( 'post' ) ? 'Article' : 'WebPage',
			'name'        => get_the_title( $id ),
			'url'         => $url,
			'description' => $description,
		);
		if ( is_singular( 'post' ) ) {
			$schema['headline']      = get_the_title( $id );
			$schema['datePublished'] = get_the_date( DATE_W3C, $id );
			$schema['dateModified']  = get_the_modified_date( DATE_W3C, $id );
		}
		$organization = Plugin::settings()['organization'];
		if ( $organization && is_front_page() ) {
			$schema['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $organization,
				'url'   => home_url( '/' ),
			);
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HTML-safe flags.
	}

	/** Metadata editor. */
	public static function boxes() {
		add_meta_box( 'ewp-seo', 'SEO essentiel', array( self::class, 'box' ), array( 'page', 'post', 'ewp_case' ), 'normal' );
	}

	/** Fields.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function box( $post ) {
		wp_nonce_field( 'ewp_seo_' . $post->ID, 'ewp_seo_nonce' );
		echo '<p><label>Titre SEO<br><input class="widefat" name="ewp_seo_title" maxlength="160" value="' . esc_attr( get_post_meta( $post->ID, '_ewp_seo_title', true ) ) . '"></label></p>';
		echo '<p><label>Description<br><textarea class="widefat" name="ewp_description" maxlength="320">' . esc_textarea( get_post_meta( $post->ID, '_ewp_description', true ) ) . '</textarea></label></p>';
	}

	/** Authorized metadata save.
	 *
	 * @param int $id Post ID.
	 */
	public static function save( $id ) {
		if ( ! isset( $_POST['ewp_seo_nonce'] ) || ! is_string( $_POST['ewp_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ewp_seo_nonce'] ) ), 'ewp_seo_' . $id ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) ) {
			return;
		}
		foreach ( array( 'ewp_seo_title', 'ewp_description' ) as $key ) {
			if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) {
				update_post_meta( $id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}
}
