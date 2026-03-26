<?php
/**
 * WP-CLI commands for Auto Multi-Meta.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Manage AI-generated SEO meta descriptions.
 *
 * ## EXAMPLES
 *
 *     # Show plugin configuration.
 *     $ wp amm status
 *
 *     # List terms missing meta descriptions.
 *     $ wp amm list terms --status=missing
 *
 *     # Generate meta description for a single post.
 *     $ wp amm generate post 42
 *
 *     # Bulk generate for all terms in a taxonomy.
 *     $ wp amm generate terms --taxonomy=category
 */
class CLI {

	/**
	 * Shows the current plugin configuration.
	 *
	 * Displays provider, model, detected SEO plugin, enabled taxonomies,
	 * enabled post types, and site language setting.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp amm status
	 *
	 * @subcommand status
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Named arguments (unused).
	 *
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP-CLI signature.
		$plugin      = auto_multi_meta_get_plugin();
		$provider    = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
		$model       = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
		$api_key     = (string) get_option( AMM_OPT_API_KEY, '' );
		$seo_plugin  = $plugin->detect_seo_plugin();
		$taxonomies  = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, array() );
		$post_types  = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, array() );
		$overwrite   = (bool) get_option( AMM_OPT_OVERWRITE_EXISTING, AMM_DEFAULT_OVERWRITE_EXISTING );
		$use_lang    = (bool) get_option( AMM_OPT_USE_SITE_LANGUAGE, AMM_DEFAULT_USE_SITE_LANGUAGE );
		$batch_delay = (int) get_option( AMM_OPT_BATCH_DELAY, AMM_DEFAULT_BATCH_DELAY );
		$locale      = get_locale();
		$has_key     = '' !== trim( $api_key ) ? 'Yes' : 'No';

		$rows = array(
			array(
				'Setting' => 'Provider',
				'Value'   => $provider,
			),
			array(
				'Setting' => 'Model',
				'Value'   => $model,
			),
			array(
				'Setting' => 'API Key Set',
				'Value'   => $has_key,
			),
			array(
				'Setting' => 'SEO Plugin',
				'Value'   => $seo_plugin,
			),
			array(
				'Setting' => 'Overwrite Existing',
				'Value'   => $overwrite ? 'Yes' : 'No',
			),
			array(
				'Setting' => 'Site Language',
				'Value'   => $locale,
			),
			array(
				'Setting' => 'Use Site Language',
				'Value'   => $use_lang ? 'Yes' : 'No',
			),
			array(
				'Setting' => 'Batch Delay',
				'Value'   => $batch_delay . 's',
			),
			array(
				'Setting' => 'Enabled Taxonomies',
				'Value'   => empty( $taxonomies ) ? '(none)' : implode( ', ', $taxonomies ),
			),
			array(
				'Setting' => 'Enabled Post Types',
				'Value'   => empty( $post_types ) ? '(none)' : implode( ', ', $post_types ),
			),
		);

		\WP_CLI\Utils\format_items( 'table', $rows, array( 'Setting', 'Value' ) );
	}

	/**
	 * Lists terms or posts with their meta description status.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : Item type to list. Must be 'terms' or 'posts'.
	 *
	 * [--taxonomy=<slug>]
	 * : Filter terms to a single taxonomy (terms only).
	 *
	 * [--post-type=<slug>]
	 * : Filter posts to a single post type (posts only).
	 *
	 * [--status=<status>]
	 * : Filter by meta description status: 'all', 'missing', or 'has'.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - missing
	 *   - has
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp amm list terms --status=missing
	 *     $ wp amm list posts --post-type=page --format=csv
	 *
	 * @subcommand list
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	public function list_items( array $args, array $assoc_args ): void {
		$type = $args[0] ?? '';

		if ( 'terms' === $type ) {
			$this->list_terms( $assoc_args );
		} elseif ( 'posts' === $type ) {
			$this->list_posts( $assoc_args );
		} else {
			\WP_CLI::error( 'Type must be "terms" or "posts". Usage: wp amm list terms|posts' );
		}
	}

	/**
	 * Generates meta descriptions for terms or posts.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : What to generate for. One of: 'term', 'post', 'terms', 'posts'.
	 *
	 * [<id>]
	 * : Term ID or Post ID (required for singular 'term' or 'post').
	 *
	 * [--taxonomy=<slug>]
	 * : Taxonomy slug (required for 'term', optional filter for 'terms').
	 *
	 * [--post-type=<slug>]
	 * : Post type slug (optional filter for 'posts').
	 *
	 * [--force]
	 * : Overwrite existing descriptions.
	 *
	 * [--dry-run]
	 * : Preview the generated description without saving.
	 *
	 * [--delay=<seconds>]
	 * : Seconds between API calls for bulk generation. Default: saved batch delay setting.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp amm generate term 15 --taxonomy=category
	 *     $ wp amm generate post 42 --force
	 *     $ wp amm generate terms --taxonomy=product_cat
	 *     $ wp amm generate posts --post-type=page --dry-run
	 *
	 * @subcommand generate
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		$type = $args[0] ?? '';

		if ( 'term' === $type ) {
			$this->generate_single_term( $args, $assoc_args );
		} elseif ( 'post' === $type ) {
			$this->generate_single_post( $args, $assoc_args );
		} elseif ( 'terms' === $type ) {
			$this->generate_bulk_terms( $assoc_args );
		} elseif ( 'posts' === $type ) {
			$this->generate_bulk_posts( $assoc_args );
		} else {
			\WP_CLI::error( 'Type must be "term", "post", "terms", or "posts". Usage: wp amm generate term|post|terms|posts' );
		}
	}

	// -----------------------------------------------------------------------
	// List helpers.
	// -----------------------------------------------------------------------

	/**
	 * Lists terms with meta description status.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function list_terms( array $assoc_args ): void {
		$meta_handler = auto_multi_meta_get_plugin()->get_meta_handler();
		$taxonomies   = $this->resolve_taxonomies( $assoc_args );
		$status       = $assoc_args['status'] ?? 'all';
		$format       = $assoc_args['format'] ?? 'table';
		$rows         = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'all',
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$desc   = $meta_handler->get_term_meta( $term->term_id, $taxonomy );
				$has    = '' !== $desc;
				$length = $has ? mb_strlen( $desc ) : 0;

				if ( 'missing' === $status && $has ) {
					continue;
				}

				if ( 'has' === $status && ! $has ) {
					continue;
				}

				$rows[] = array(
					'ID'          => $term->term_id,
					'Name'        => $term->name,
					'Taxonomy'    => $taxonomy,
					'Status'      => $has ? 'has' : 'missing',
					'Chars'       => $length,
					'Description' => $has ? mb_substr( $desc, 0, 80 ) : '',
				);
			}
		}

		if ( empty( $rows ) ) {
			\WP_CLI::success( 'No terms found matching the criteria.' );
			return;
		}

		\WP_CLI\Utils\format_items( $format, $rows, array( 'ID', 'Name', 'Taxonomy', 'Status', 'Chars', 'Description' ) );
	}

	/**
	 * Lists posts with meta description status.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function list_posts( array $assoc_args ): void {
		$meta_handler = auto_multi_meta_get_plugin()->get_meta_handler();
		$post_types   = $this->resolve_post_types( $assoc_args );
		$status       = $assoc_args['status'] ?? 'all';
		$format       = $assoc_args['format'] ?? 'table';
		$rows         = array();

		foreach ( $post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- CLI listing needs all items.
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			foreach ( $posts as $post ) {
				$desc   = $meta_handler->get_post_meta_description( $post->ID );
				$has    = '' !== $desc;
				$length = $has ? mb_strlen( $desc ) : 0;

				if ( 'missing' === $status && $has ) {
					continue;
				}

				if ( 'has' === $status && ! $has ) {
					continue;
				}

				$rows[] = array(
					'ID'          => $post->ID,
					'Title'       => $post->post_title,
					'Type'        => $post_type,
					'Status'      => $has ? 'has' : 'missing',
					'Chars'       => $length,
					'Description' => $has ? mb_substr( $desc, 0, 80 ) : '',
				);
			}
		}

		if ( empty( $rows ) ) {
			\WP_CLI::success( 'No posts found matching the criteria.' );
			return;
		}

		\WP_CLI\Utils\format_items( $format, $rows, array( 'ID', 'Title', 'Type', 'Status', 'Chars', 'Description' ) );
	}

	// -----------------------------------------------------------------------
	// Single generate helpers.
	// -----------------------------------------------------------------------

	/**
	 * Generates a meta description for a single term.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function generate_single_term( array $args, array $assoc_args ): void {
		$term_id  = (int) ( $args[1] ?? 0 );
		$taxonomy = $assoc_args['taxonomy'] ?? '';
		$force    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$dry_run  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! $term_id ) {
			\WP_CLI::error( 'Term ID is required. Usage: wp amm generate term <id> --taxonomy=<slug>' );
		}

		if ( '' === $taxonomy ) {
			// Attempt to detect taxonomy from the term.
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				$taxonomy = $term->taxonomy;
			} else {
				\WP_CLI::error( 'Could not detect taxonomy for term ' . $term_id . '. Use --taxonomy=<slug>.' );
			}
		}

		$generator = auto_multi_meta_get_plugin()->get_generator();

		if ( $dry_run ) {
			$result = $generator->preview_for_term( $term_id, $taxonomy );
		} else {
			$result = $generator->generate_for_term( $term_id, $taxonomy, $force );
		}

		$this->output_single_result( $result, $dry_run );
	}

	/**
	 * Generates a meta description for a single post.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function generate_single_post( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[1] ?? 0 );
		$force   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! $post_id ) {
			\WP_CLI::error( 'Post ID is required. Usage: wp amm generate post <id>' );
		}

		$generator = auto_multi_meta_get_plugin()->get_generator();

		if ( $dry_run ) {
			$result = $generator->preview_for_post( $post_id );
		} else {
			$result = $generator->generate_for_post( $post_id, $force );
		}

		$this->output_single_result( $result, $dry_run );
	}

	// -----------------------------------------------------------------------
	// Bulk generate helpers.
	// -----------------------------------------------------------------------

	/**
	 * Bulk generates meta descriptions for terms.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function generate_bulk_terms( array $assoc_args ): void {
		$meta_handler = auto_multi_meta_get_plugin()->get_meta_handler();
		$generator    = auto_multi_meta_get_plugin()->get_generator();
		$taxonomies   = $this->resolve_taxonomies( $assoc_args );
		$force        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$dry_run      = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$delay        = $this->resolve_delay( $assoc_args );

		// Collect items.
		$items = array();
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( (array) $terms as $term_id ) {
				$tid = (int) $term_id;

				if ( ! $force && '' !== $meta_handler->get_term_meta( $tid, $taxonomy ) ) {
					continue;
				}

				$items[] = array(
					'id'       => $tid,
					'taxonomy' => $taxonomy,
				);
			}
		}

		if ( empty( $items ) ) {
			\WP_CLI::success( 'No terms need meta descriptions.' );
			return;
		}

		$label    = $dry_run ? 'Previewing terms' : 'Generating terms';
		$progress = \WP_CLI\Utils\make_progress_bar( $label, count( $items ) );

		$generated = 0;
		$skipped   = 0;
		$errors    = 0;

		foreach ( $items as $index => $item ) {
			if ( $dry_run ) {
				$result = $generator->preview_for_term( $item['id'], $item['taxonomy'] );
			} else {
				$result = $generator->generate_for_term( $item['id'], $item['taxonomy'], $force );
			}

			if ( 'generated' === $result['status'] ) {
				++$generated;
			} elseif ( 'skipped' === $result['status'] ) {
				++$skipped;
			} else {
				++$errors;
				\WP_CLI::warning( 'Term ' . $item['id'] . ': ' . $result['message'] );
			}

			$progress->tick();

			if ( $delay > 0 && $index < count( $items ) - 1 ) {
				sleep( $delay );
			}
		}

		$progress->finish();
		$this->output_bulk_summary( $generated, $skipped, $errors, $dry_run );
	}

	/**
	 * Bulk generates meta descriptions for posts.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	private function generate_bulk_posts( array $assoc_args ): void {
		$meta_handler = auto_multi_meta_get_plugin()->get_meta_handler();
		$generator    = auto_multi_meta_get_plugin()->get_generator();
		$post_types   = $this->resolve_post_types( $assoc_args );
		$force        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$dry_run      = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$delay        = $this->resolve_delay( $assoc_args );

		// Collect items.
		$items = array();
		foreach ( $post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- CLI bulk needs all items.
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( (array) $posts as $post_id ) {
				$pid = (int) $post_id;

				if ( ! $force && '' !== $meta_handler->get_post_meta_description( $pid ) ) {
					continue;
				}

				$items[] = $pid;
			}
		}

		if ( empty( $items ) ) {
			\WP_CLI::success( 'No posts need meta descriptions.' );
			return;
		}

		$label    = $dry_run ? 'Previewing posts' : 'Generating posts';
		$progress = \WP_CLI\Utils\make_progress_bar( $label, count( $items ) );

		$generated = 0;
		$skipped   = 0;
		$errors    = 0;

		foreach ( $items as $index => $post_id ) {
			if ( $dry_run ) {
				$result = $generator->preview_for_post( $post_id );
			} else {
				$result = $generator->generate_for_post( $post_id, $force );
			}

			if ( 'generated' === $result['status'] ) {
				++$generated;
			} elseif ( 'skipped' === $result['status'] ) {
				++$skipped;
			} else {
				++$errors;
				\WP_CLI::warning( 'Post ' . $post_id . ': ' . $result['message'] );
			}

			$progress->tick();

			if ( $delay > 0 && $index < count( $items ) - 1 ) {
				sleep( $delay );
			}
		}

		$progress->finish();
		$this->output_bulk_summary( $generated, $skipped, $errors, $dry_run );
	}

	// -----------------------------------------------------------------------
	// Shared helpers.
	// -----------------------------------------------------------------------

	/**
	 * Resolves which taxonomies to operate on.
	 *
	 * Uses --taxonomy if provided, otherwise falls back to all enabled taxonomies.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return array<int, string> Taxonomy slugs.
	 */
	private function resolve_taxonomies( array $assoc_args ): array {
		$filter     = $assoc_args['taxonomy'] ?? '';
		$taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, array() );
		$result     = $taxonomies;

		if ( '' !== $filter ) {
			if ( ! in_array( $filter, $taxonomies, true ) ) {
				\WP_CLI::error( 'Taxonomy "' . $filter . '" is not enabled. Enabled: ' . implode( ', ', $taxonomies ) );
			}
			$result = array( $filter );
		}

		if ( empty( $result ) ) {
			\WP_CLI::error( 'No taxonomies are enabled. Enable taxonomies in the plugin settings first.' );
		}

		return $result;
	}

	/**
	 * Resolves which post types to operate on.
	 *
	 * Uses --post-type if provided, otherwise falls back to all enabled post types.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return array<int, string> Post type slugs.
	 */
	private function resolve_post_types( array $assoc_args ): array {
		$filter     = $assoc_args['post-type'] ?? '';
		$post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, array() );
		$result     = $post_types;

		if ( '' !== $filter ) {
			if ( ! in_array( $filter, $post_types, true ) ) {
				\WP_CLI::error( 'Post type "' . $filter . '" is not enabled. Enabled: ' . implode( ', ', $post_types ) );
			}
			$result = array( $filter );
		}

		if ( empty( $result ) ) {
			\WP_CLI::error( 'No post types are enabled. Enable post types in the plugin settings first.' );
		}

		return $result;
	}

	/**
	 * Resolves the delay between API calls for bulk operations.
	 *
	 * Uses --delay if provided, otherwise the saved batch delay setting.
	 *
	 * @param array<string, string> $assoc_args Named arguments.
	 *
	 * @return int Delay in seconds.
	 */
	private function resolve_delay( array $assoc_args ): int {
		$delay = $assoc_args['delay'] ?? null;

		if ( null !== $delay ) {
			return max( 0, (int) $delay );
		}

		return (int) get_option( AMM_OPT_BATCH_DELAY, AMM_DEFAULT_BATCH_DELAY );
	}

	/**
	 * Outputs the result of a single generate/preview operation.
	 *
	 * @param array<string, string> $result  Generator result array.
	 * @param bool                  $dry_run Whether this was a dry run.
	 *
	 * @return void
	 */
	private function output_single_result( array $result, bool $dry_run ): void {
		$prefix = $dry_run ? '[dry-run] ' : '';

		if ( 'error' === $result['status'] ) {
			\WP_CLI::error( $prefix . $result['message'] );
		} elseif ( 'skipped' === $result['status'] ) {
			\WP_CLI::warning( $prefix . $result['message'] );
		} else {
			\WP_CLI::success( $prefix . $result['message'] );
			\WP_CLI::log( $result['description'] );
		}
	}

	/**
	 * Outputs a summary after bulk generation.
	 *
	 * @param int  $generated Number of items generated.
	 * @param int  $skipped   Number of items skipped.
	 * @param int  $errors    Number of errors.
	 * @param bool $dry_run   Whether this was a dry run.
	 *
	 * @return void
	 */
	private function output_bulk_summary( int $generated, int $skipped, int $errors, bool $dry_run ): void {
		$prefix = $dry_run ? '[dry-run] ' : '';
		$total  = $generated + $skipped + $errors;

		\WP_CLI::log( '' );
		\WP_CLI::log( $prefix . 'Done. ' . $total . ' items processed: ' . $generated . ' generated, ' . $skipped . ' skipped, ' . $errors . ' errors.' );

		if ( $errors > 0 ) {
			\WP_CLI::warning( $errors . ' error(s) occurred. See warnings above for details.' );
		} else {
			\WP_CLI::success( 'All items processed successfully.' );
		}
	}
}

\WP_CLI::add_command( 'amm', __NAMESPACE__ . '\\CLI' );
