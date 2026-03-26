<?php
/**
 * Context builder for taxonomy terms and posts.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Context_Builder
 *
 * Gathers context strings that are sent to the AI provider for generating
 * SEO meta descriptions. Supports both taxonomy terms and posts/pages.
 *
 * Context is gathered via WP_Query (default). If a loopback HTTP request to
 * the frontend succeeds, additional HTML context (existing meta description,
 * headings) is merged in to enrich the prompt.
 */
class Context_Builder {

	/**
	 * Builds context data for a taxonomy term.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array<string, string>|\WP_Error Context data array or WP_Error on failure.
	 */
	public function build_term_context( int $term_id, string $taxonomy ): array|\WP_Error {
		$result = null;

		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) ) {
			$result = $term;
		} elseif ( null === $term || false === $term ) {
			$result = new \WP_Error(
				'amm_invalid_term',
				__( 'Term not found.', 'auto-multi-meta' )
			);
		} else {
			$taxonomy_obj   = get_taxonomy( $taxonomy );
			$taxonomy_label = ( $taxonomy_obj && isset( $taxonomy_obj->labels->singular_name ) )
				? $taxonomy_obj->labels->singular_name
				: $taxonomy;

			$sample_titles = $this->get_term_sample_titles( $term_id, $taxonomy );

			$context = array(
				'term_name'      => $term->name,
				'term_slug'      => $term->slug,
				'taxonomy'       => $taxonomy,
				'taxonomy_label' => $taxonomy_label,
				'description'    => wp_strip_all_tags( $term->description ),
				// Provide a meaningful fallback when the term has no published items.
				'product_list'   => '' !== $sample_titles
					? $sample_titles
					: __( 'no items currently listed', 'auto-multi-meta' ),
			);

			// Attempt loopback fetch to augment context with frontend HTML.
			$term_url = get_term_link( $term );

			if ( ! is_wp_error( $term_url ) ) {
				$html_context = $this->try_loopback_fetch( $term_url );
				if ( false !== $html_context ) {
					$context = array_merge( $context, $html_context );
				}
			}

			$result = $context;
		}

		return $result;
	}

	/**
	 * Builds context data for a post, page, or custom post type.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<string, string>|\WP_Error Context data array or WP_Error on failure.
	 */
	public function build_post_context( int $post_id ): array|\WP_Error {
		$result = null;

		$post = get_post( $post_id );

		if ( null === $post ) {
			$result = new \WP_Error(
				'amm_invalid_post',
				__( 'Post not found.', 'auto-multi-meta' )
			);
		} else {
			$post_type_obj   = get_post_type_object( $post->post_type );
			$post_type_label = ( $post_type_obj && isset( $post_type_obj->labels->singular_name ) )
				? $post_type_obj->labels->singular_name
				: $post->post_type;

			// Plain-text excerpt (use post excerpt if set, otherwise empty).
			$excerpt = '';
			if ( ! empty( $post->post_excerpt ) ) {
				$excerpt = wp_strip_all_tags( $post->post_excerpt );
			}

			// Strip HTML from content and truncate to configured limit.
			// Use mb_substr to avoid splitting multibyte characters.
			$content = wp_strip_all_tags( $post->post_content );
			$content = mb_substr( $content, 0, AMM_CONTEXT_MAX_CONTENT_CHARS );

			$context = array(
				'post_title'      => $post->post_title,
				'post_type'       => $post->post_type,
				'post_type_label' => $post_type_label,
				'post_excerpt'    => $excerpt,
				'post_content'    => $content,
				'categories'      => $this->get_post_term_names( $post_id, 'category' ),
				'tags'            => $this->get_post_term_names( $post_id, 'post_tag' ),
			);

			// Attempt loopback fetch to augment context with frontend HTML.
			$post_url     = get_permalink( $post_id );
			$html_context = $this->try_loopback_fetch( $post_url );

			if ( false !== $html_context ) {
				$context = array_merge( $context, $html_context );
			}

			$result = $context;
		}

		return $result;
	}

	/**
	 * Builds a complete prompt string for a taxonomy term.
	 *
	 * Reads the configured prompt template, replaces all context tokens,
	 * and returns the final prompt ready to send to the AI provider.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string|\WP_Error Prompt string or WP_Error on failure.
	 */
	public function build_term_prompt( int $term_id, string $taxonomy ): string|\WP_Error {
		$result = null;

		$context = $this->build_term_context( $term_id, $taxonomy );

		if ( is_wp_error( $context ) ) {
			$result = $context;
		} else {
			$template = get_option( AMM_OPT_PROMPT_TEMPLATE_TERMS, AMM_DEFAULT_PROMPT_TEMPLATE_TERMS );

			if ( empty( $template ) ) {
				$template = AMM_DEFAULT_PROMPT_TEMPLATE_TERMS;
			}

			$result = $this->replace_tokens( $template, $context );
			$result = $this->maybe_append_language_instruction( $result );
		}

		return $result;
	}

	/**
	 * Builds a complete prompt string for a post, page, or custom post type.
	 *
	 * Reads the configured prompt template, replaces all context tokens,
	 * and returns the final prompt ready to send to the AI provider.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|\WP_Error Prompt string or WP_Error on failure.
	 */
	public function build_post_prompt( int $post_id ): string|\WP_Error {
		$result = null;

		$context = $this->build_post_context( $post_id );

		if ( is_wp_error( $context ) ) {
			$result = $context;
		} else {
			$template = get_option( AMM_OPT_PROMPT_TEMPLATE_POSTS, AMM_DEFAULT_PROMPT_TEMPLATE_POSTS );

			if ( empty( $template ) ) {
				$template = AMM_DEFAULT_PROMPT_TEMPLATE_POSTS;
			}

			$result = $this->replace_tokens( $template, $context );
			$result = $this->maybe_append_language_instruction( $result );
		}

		return $result;
	}

	/**
	 * Appends a language instruction to the prompt when the site language option is enabled.
	 *
	 * Uses the WordPress locale to build a human-readable language name
	 * (e.g. "British English", "Portuguese (Brazil)") via the intl extension.
	 *
	 * @since 0.3.0
	 *
	 * @param string $prompt The prompt to potentially append to.
	 *
	 * @return string The prompt, possibly with a language instruction appended.
	 */
	private function maybe_append_language_instruction( string $prompt ): string {
		$use_language = (bool) get_option( AMM_OPT_USE_SITE_LANGUAGE, AMM_DEFAULT_USE_SITE_LANGUAGE );
		$result       = $prompt;

		if ( $use_language ) {
			$locale        = get_locale();
			$language_name = locale_get_display_language( $locale, 'en' );
			$region_name   = locale_get_display_region( $locale, 'en' );
			$label         = $language_name;

			if ( '' !== $region_name ) {
				$label = $region_name . ' ' . $language_name;
			}

			$result = $prompt . ' Use ' . $label . ' spelling.';
		}

		return $result;
	}

	/**
	 * Replaces {token} placeholders in a template string with context values.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $template Template string containing {token} placeholders.
	 * @param array<string, string> $tokens   Associative array of token name => replacement value.
	 *
	 * @return string Template with all matching tokens replaced.
	 */
	private function replace_tokens( string $template, array $tokens ): string {
		$search  = array();
		$replace = array();

		foreach ( $tokens as $key => $value ) {
			$search[]  = '{' . $key . '}';
			$replace[] = (string) $value;
		}

		return str_replace( $search, $replace, $template );
	}

	/**
	 * Returns a comma-separated list of sample post/product titles for a taxonomy term.
	 *
	 * Uses a lightweight WP_Query (ids only, no term or meta cache) to minimise
	 * memory usage on large sites.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string Comma-separated post titles, or empty string if no posts found.
	 */
	private function get_term_sample_titles( int $term_id, string $taxonomy ): string {
		$query = new \WP_Query(
			array(
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
				),
				'posts_per_page'         => AMM_CONTEXT_MAX_SAMPLE_TITLES,
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		$titles = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$title = get_the_title( (int) $post_id );
				if ( ! empty( $title ) ) {
					$titles[] = $title;
				}
			}
		}

		wp_reset_postdata();

		return implode( ', ', $titles );
	}

	/**
	 * Returns a comma-separated list of term names for a post in a given taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string Comma-separated term names, or empty string if none found.
	 */
	private function get_post_term_names( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		$names = array();

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$names[] = $term->name;
			}
		}

		return implode( ', ', $names );
	}

	/**
	 * Attempts a loopback HTTP GET request to the given URL to fetch frontend HTML.
	 *
	 * Returns an array of parsed context values on success, or false if the request
	 * fails (connection refused, timeout, non-200 response, or WP_Error).
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to fetch.
	 *
	 * @return array<string, string>|false Parsed HTML context or false on failure.
	 */
	private function try_loopback_fetch( string $url ): array|false {
		$result = false;

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => AMM_CONTEXT_LOOPBACK_TIMEOUT,
				'user-agent' => 'Auto-Multi-Meta/' . AMM_VERSION . ' (context-builder)',
				'sslverify'  => false,
			)
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body   = wp_remote_retrieve_body( $response );
			$result = $this->extract_html_context( $body );
		}

		return $result;
	}

	/**
	 * Extracts SEO-relevant context from a page's raw HTML.
	 *
	 * Parses the page title, existing meta description, and the first five
	 * H1/H2 headings. Returns an associative array suitable for token replacement.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Raw HTML content of the page.
	 *
	 * @return array<string, string> Extracted context values.
	 */
	private function extract_html_context( string $html ): array {
		$page_title    = '';
		$existing_meta = '';
		$headings      = array();

		// Extract <title> tag content.
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/si', $html, $matches ) ) {
			$page_title = wp_strip_all_tags( $matches[1] );
		}

		// Extract meta description — handle both attribute orderings.
		if ( preg_match( '/<meta\s[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches ) ) {
			$existing_meta = $matches[1];
		} elseif ( preg_match( '/<meta\s[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']description["\'][^>]*>/i', $html, $matches ) ) {
			$existing_meta = $matches[1];
		}

		// Extract up to 5 H1 and H2 headings.
		if ( preg_match_all( '/<h[12][^>]*>(.*?)<\/h[12]>/si', $html, $matches ) ) {
			foreach ( array_slice( $matches[1], 0, 5 ) as $heading ) {
				$headings[] = wp_strip_all_tags( $heading );
			}
		}

		return array(
			'page_title'    => $page_title,
			'existing_meta' => $existing_meta,
			'headings'      => implode( ' | ', $headings ),
		);
	}
}
