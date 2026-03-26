<?php
/**
 * SEO meta description handler.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Meta_Handler
 *
 * Reads and writes SEO meta descriptions for taxonomy terms and posts.
 * Supports Yoast SEO and RankMath. Returns WP_Error when no supported
 * SEO plugin is active (no meta key is available to write to).
 */
class Meta_Handler {

	/**
	 * Active SEO plugin: 'yoast', 'rankmath', or 'none'.
	 *
	 * @var string
	 */
	private string $seo_plugin;

	/**
	 * Constructor.
	 *
	 * @param string $seo_plugin Active SEO plugin slug ('yoast', 'rankmath', or 'none').
	 */
	public function __construct( string $seo_plugin ) {
		$this->seo_plugin = $seo_plugin;
	}

	/**
	 * Reads the existing meta description for a taxonomy term.
	 *
	 * Returns an empty string when no meta description is set or when
	 * no SEO plugin is active (no known meta key to read).
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug (unused; included for API consistency).
	 *
	 * @return string Existing meta description, or empty string if none.
	 */
	public function get_term_meta( int $term_id, string $taxonomy ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $taxonomy retained for API consistency.
		$meta_key = $this->get_term_meta_key();
		$value    = '';

		if ( '' !== $meta_key ) {
			$raw   = get_term_meta( $term_id, $meta_key, true );
			$value = is_string( $raw ) ? $raw : '';
		}

		return $value;
	}

	/**
	 * Reads the existing meta description for a post.
	 *
	 * Returns an empty string when no meta description is set or when
	 * no SEO plugin is active (no known meta key to read).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string Existing meta description, or empty string if none.
	 */
	public function get_post_meta_description( int $post_id ): string {
		$meta_key = $this->get_post_meta_key();
		$value    = '';

		if ( '' !== $meta_key ) {
			$raw   = get_post_meta( $post_id, $meta_key, true );
			$value = is_string( $raw ) ? $raw : '';
		}

		return $value;
	}

	/**
	 * Writes a meta description for a taxonomy term.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy slug (unused; included for API consistency).
	 * @param string $description Meta description to store.
	 *
	 * @return bool|\WP_Error True on success, WP_Error when no SEO plugin is active.
	 */
	public function set_term_meta( int $term_id, string $taxonomy, string $description ): bool|\WP_Error { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- $taxonomy retained for API consistency.
		$meta_key = $this->get_term_meta_key();
		$result   = null;

		if ( '' === $meta_key ) {
			$result = new \WP_Error(
				'amm_no_seo_plugin',
				__( 'No supported SEO plugin detected. Cannot write term meta description.', 'auto-multi-meta' )
			);
		}

		if ( is_null( $result ) ) {
			$updated = update_term_meta( $term_id, $meta_key, sanitize_text_field( $description ) );
			$result  = ( false !== $updated );
		}

		return $result;
	}

	/**
	 * Writes a meta description for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $description Meta description to store.
	 *
	 * @return bool|\WP_Error True on success, WP_Error when no SEO plugin is active.
	 */
	public function set_post_meta( int $post_id, string $description ): bool|\WP_Error {
		$meta_key = $this->get_post_meta_key();
		$result   = null;

		if ( '' === $meta_key ) {
			$result = new \WP_Error(
				'amm_no_seo_plugin',
				__( 'No supported SEO plugin detected. Cannot write post meta description.', 'auto-multi-meta' )
			);
		}

		if ( is_null( $result ) ) {
			$updated = update_post_meta( $post_id, $meta_key, sanitize_text_field( $description ) );
			$result  = ( false !== $updated );
		}

		return $result;
	}

	/**
	 * Returns the term meta key for the active SEO plugin.
	 *
	 * Returns an empty string when no supported SEO plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @return string Meta key, or empty string if no SEO plugin is detected.
	 */
	private function get_term_meta_key(): string {
		$key = '';

		if ( 'yoast' === $this->seo_plugin ) {
			$key = AMM_YOAST_TERM_META_KEY;
		} elseif ( 'rankmath' === $this->seo_plugin ) {
			$key = AMM_RANKMATH_TERM_META_KEY;
		}

		return $key;
	}

	/**
	 * Returns the post meta key for the active SEO plugin.
	 *
	 * Returns an empty string when no supported SEO plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @return string Meta key, or empty string if no SEO plugin is detected.
	 */
	private function get_post_meta_key(): string {
		$key = '';

		if ( 'yoast' === $this->seo_plugin ) {
			$key = AMM_YOAST_POST_META_KEY;
		} elseif ( 'rankmath' === $this->seo_plugin ) {
			$key = AMM_RANKMATH_POST_META_KEY;
		}

		return $key;
	}
}
