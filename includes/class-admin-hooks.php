<?php
/**
 * Admin hooks: menu registration and asset enqueuing.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Admin_Hooks
 *
 * Registers the plugin admin menu page, enqueues assets, and renders
 * the settings page template.
 */
class Admin_Hooks {

	/**
	 * Admin page hook suffix returned by add_management_page().
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Term manager page hook suffix.
	 *
	 * @var string
	 */
	private string $term_manager_hook = '';

	/**
	 * Post manager page hook suffix.
	 *
	 * @var string
	 */
	private string $post_manager_hook = '';

	/**
	 * Adds the plugin page to the Tools menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->page_hook = (string) add_management_page(
			__( 'Auto Multi-Meta', 'auto-multi-meta' ),
			__( 'Auto Multi-Meta', 'auto-multi-meta' ),
			'manage_options',
			'auto-multi-meta',
			array(
				$this,
				'render_settings_page',
			)
		);

		// Hidden submenu pages for the manager views (not shown in navigation).
		$this->term_manager_hook = (string) add_submenu_page(
			null,
			__( 'Auto Multi-Meta — Term Manager', 'auto-multi-meta' ),
			'',
			'manage_options',
			'amm-term-manager',
			array(
				$this,
				'render_term_manager',
			)
		);

		$this->post_manager_hook = (string) add_submenu_page(
			null,
			__( 'Auto Multi-Meta — Post Manager', 'auto-multi-meta' ),
			'',
			'manage_options',
			'amm-post-manager',
			array(
				$this,
				'render_post_manager',
			)
		);
	}

	/**
	 * Enqueues CSS and JS only on the plugin settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$is_plugin_page = in_array( $hook_suffix, array( $this->page_hook, $this->term_manager_hook, $this->post_manager_hook ), true );

		if ( ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style( 'amm-admin', AMM_URL . 'assets/admin/admin.css', array(), AMM_VERSION );

		wp_enqueue_script( 'amm-admin', AMM_URL . 'assets/admin/admin.js', array(), AMM_VERSION, true );

		wp_localize_script(
			'amm-admin',
			'ammAdmin',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'amm_admin' ),
				'defaultTab'     => 'settings',
				'termManagerUrl' => admin_url( 'tools.php?page=amm-term-manager' ),
				'postManagerUrl' => admin_url( 'tools.php?page=amm-post-manager' ),
				'i18n'           => array(
					'generating'      => __( 'Generating…', 'auto-multi-meta' ),
					'previewing'      => __( 'Previewing…', 'auto-multi-meta' ),
					'generate'        => __( 'Generate', 'auto-multi-meta' ),
					'regenerate'      => __( 'Regenerate', 'auto-multi-meta' ),
					'preview'         => __( 'Preview', 'auto-multi-meta' ),
					/* translators: %1$d: current item number, %2$d: total items. */
					'bulkProgress'    => __( 'Generating %1$d of %2$d…', 'auto-multi-meta' ),
					/* translators: %1$d: generated count, %2$d: skipped count, %3$d: error count. */
					'bulkComplete'    => __( '%1$d generated, %2$d skipped, %3$d errors.', 'auto-multi-meta' ),
					'requestFailed'   => __( 'Request failed. Please try again.', 'auto-multi-meta' ),
					'batchStarting'   => __( 'Starting…', 'auto-multi-meta' ),
					/* translators: %1$d: completed count, %2$d: total, %3$d: failed count. */
					'batchRunning'    => __( 'Processing %1$d of %2$d… (%3$d failed)', 'auto-multi-meta' ),
					/* translators: %1$d: generated count, %2$d: total, %3$d: failed count. */
					'batchComplete'   => __( 'Complete: %1$d of %2$d generated (%3$d failed).', 'auto-multi-meta' ),
					/* translators: %1$d: completed count, %2$d: total items. */
					'batchCancelled'  => __( 'Cancelled after processing %1$d of %2$d items.', 'auto-multi-meta' ),
					'batchFailed'     => __( 'Failed to start batch.', 'auto-multi-meta' ),
					'batchCancelling' => __( 'Cancelling…', 'auto-multi-meta' ),
				),
			)
		);
	}

	/**
	 * Renders the plugin settings page template.
	 *
	 * Passes data to the template via local variables available in the included file's scope.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'auto-multi-meta' ) );
		}

		$auto_multi_meta_seo_plugin     = auto_multi_meta_get_plugin()->detect_seo_plugin();
		$auto_multi_meta_all_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$auto_multi_meta_all_post_types = get_post_types( array( 'public' => true ), 'objects' );

		include AMM_DIR . 'admin-templates/settings-page.php';
	}

	/**
	 * Renders the term manager page.
	 *
	 * @return void
	 */
	public function render_term_manager(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'auto-multi-meta' ) );
		}

		$auto_multi_meta_meta_handler       = auto_multi_meta_get_plugin()->get_meta_handler();
		$auto_multi_meta_term_manager       = new Term_Manager( $auto_multi_meta_meta_handler );
		$auto_multi_meta_enabled_taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, array() );

		include AMM_DIR . 'admin-templates/term-manager.php';
	}

	/**
	 * Renders the post manager page.
	 *
	 * @return void
	 */
	public function render_post_manager(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'auto-multi-meta' ) );
		}

		$auto_multi_meta_meta_handler       = auto_multi_meta_get_plugin()->get_meta_handler();
		$auto_multi_meta_post_manager       = new Post_Manager( $auto_multi_meta_meta_handler );
		$auto_multi_meta_enabled_post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, array() );

		include AMM_DIR . 'admin-templates/post-manager.php';
	}

	/**
	 * AJAX handler: generate a meta description for a single term or post.
	 *
	 * Expected POST fields: nonce, type ('term'|'post'), id (int),
	 * taxonomy (string, required for terms), force ('0'|'1').
	 *
	 * @return void
	 */
	public function ajax_generate_single(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$item_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
		$force    = isset( $_POST['force'] ) && '1' === $_POST['force'];
        // phpcs:enable

		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'auto-multi-meta' ) ) );
		}

		$generator = auto_multi_meta_get_plugin()->get_generator();
		$result    = null;

		if ( 'term' === $type ) {
			if ( '' === $taxonomy ) {
				wp_send_json_error( array( 'message' => __( 'Taxonomy is required for term generation.', 'auto-multi-meta' ) ) );
			}

			$result = $generator->generate_for_term( $item_id, $taxonomy, $force );
		} elseif ( 'post' === $type ) {
			$result = $generator->generate_for_post( $item_id, $force );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid type. Must be "term" or "post".', 'auto-multi-meta' ) ) );
		}

		if ( 'error' === $result['status'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: generate meta descriptions for multiple terms or posts.
	 *
	 * Expected POST fields: nonce, type ('term'|'post'), item_ids (array).
	 * For terms: item_ids are in "termId|taxonomy" format.
	 * For posts: item_ids are plain post IDs.
	 *
	 * @return void
	 */
	public function ajax_generate_bulk(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$raw_ids = isset( $_POST['item_ids'] ) && is_array( $_POST['item_ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['item_ids'] ) ) : array();
        // phpcs:enable

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No items selected.', 'auto-multi-meta' ) ) );
		}

		$generator = auto_multi_meta_get_plugin()->get_generator();
		$results   = array(
			'generated' => 0,
			'skipped'   => 0,
			'errors'    => 0,
			'items'     => array(),
		);

		foreach ( $raw_ids as $raw_id ) {
			$item_result = null;

			if ( 'term' === $type ) {
				// Format: "termId|taxonomy".
				$parts    = explode( '|', $raw_id, 2 );
				$term_id  = absint( $parts[0] );
				$taxonomy = isset( $parts[1] ) ? sanitize_key( $parts[1] ) : '';

				if ( ! $term_id || '' === $taxonomy ) {
					++$results['errors'];
					continue;
				}

				$item_result = $generator->generate_for_term( $term_id, $taxonomy );
			} elseif ( 'post' === $type ) {
				$post_id     = absint( $raw_id );
				$item_result = $generator->generate_for_post( $post_id );
			}

			if ( is_array( $item_result ) ) {
				if ( 'generated' === $item_result['status'] ) {
					++$results['generated'];
				} elseif ( 'skipped' === $item_result['status'] ) {
					++$results['skipped'];
				} else {
					++$results['errors'];
				}

				$results['items'][] = array(
					'id'     => $raw_id,
					'status' => $item_result['status'],
					'desc'   => $item_result['description'],
					'msg'    => $item_result['message'],
				);
			}
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler: preview a generated meta description without saving.
	 *
	 * Expected POST fields: nonce, type ('term'|'post'), id (int),
	 * taxonomy (string, required for terms).
	 *
	 * @return void
	 */
	public function ajax_preview(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$item_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
        // phpcs:enable

		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'auto-multi-meta' ) ) );
		}

		$generator = auto_multi_meta_get_plugin()->get_generator();
		$result    = null;

		if ( 'term' === $type ) {
			if ( '' === $taxonomy ) {
				wp_send_json_error( array( 'message' => __( 'Taxonomy is required for term preview.', 'auto-multi-meta' ) ) );
			}

			$result = $generator->preview_for_term( $item_id, $taxonomy );
		} elseif ( 'post' === $type ) {
			$result = $generator->preview_for_post( $item_id );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid type. Must be "term" or "post".', 'auto-multi-meta' ) ) );
		}

		if ( 'error' === $result['status'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: start a background batch generation job.
	 *
	 * Expected POST fields: nonce, type ('term'|'post'|'all'), force ('0'|'1').
	 *
	 * @return void
	 */
	public function ajax_start_batch(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$raw_type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'all';
		$force    = isset( $_POST['force'] ) && '1' === $_POST['force'];
        // phpcs:enable

		$valid_types = array( 'term', 'post', 'all' );
		$type        = in_array( $raw_type, $valid_types, true ) ? $raw_type : 'all';

		$result = auto_multi_meta_get_plugin()->get_batch_processor()->start_batch( $type, $force );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$progress = auto_multi_meta_get_plugin()->get_batch_processor()->get_progress();
		wp_send_json_success( $progress );
	}

	/**
	 * AJAX handler: return the current batch progress summary.
	 *
	 * Expected POST fields: nonce.
	 *
	 * @return void
	 */
	public function ajax_batch_progress(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

		$progress = auto_multi_meta_get_plugin()->get_batch_processor()->get_progress();
		wp_send_json_success( $progress );
	}

	/**
	 * AJAX handler: cancel a running batch job.
	 *
	 * Expected POST fields: nonce.
	 *
	 * @return void
	 */
	public function ajax_cancel_batch(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

		auto_multi_meta_get_plugin()->get_batch_processor()->cancel_batch();
		$progress = auto_multi_meta_get_plugin()->get_batch_processor()->get_progress();
		wp_send_json_success( $progress );
	}

	/**
	 * Admin notice: displays a one-time completion notice after a batch finishes.
	 *
	 * Reads the AMM_OPT_BATCH_NOTICE option, renders a dismissible notice, then
	 * deletes the option so it only shows once.
	 *
	 * @return void
	 */
	public function display_batch_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_option( AMM_OPT_BATCH_NOTICE );

		if ( ! is_array( $notice ) ) {
			return;
		}

		delete_option( AMM_OPT_BATCH_NOTICE );

		$generated = (int) ( $notice['generated'] ?? 0 );
		$failed    = (int) ( $notice['failed'] ?? 0 );
		$total     = (int) ( $notice['total'] ?? 0 );
		$type      = isset( $notice['type'] ) ? sanitize_key( $notice['type'] ) : 'all';

		if ( 'term' === $type ) {
			$type_label = __( 'terms', 'auto-multi-meta' );
		} elseif ( 'post' === $type ) {
			$type_label = __( 'posts', 'auto-multi-meta' );
		} else {
			$type_label = __( 'items', 'auto-multi-meta' );
		}

		$notice_class = $failed > 0 ? 'notice-warning' : 'notice-success';

		printf(
			'<div class="notice %s is-dismissible"><p>%s</p></div>',
			esc_attr( $notice_class ),
			sprintf(
				/* translators: 1: generated count, 2: total count, 3: item type label, 4: failed count. */
				esc_html__( 'Auto Multi-Meta batch complete: %1$d of %2$d %3$s generated. %4$d failed.', 'auto-multi-meta' ),
				(int) $generated,
				(int) $total,
				esc_html( $type_label ),
				(int) $failed
			)
		);
	}

	/**
	 * Admin notice: shows setup warnings on plugin pages when configuration is incomplete.
	 *
	 * Checks for missing API key and no active SEO plugin. Notices are only shown
	 * on the plugin's own admin pages so they are not shown site-wide.
	 *
	 * @return void
	 */
	public function display_setup_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only display on the plugin's own admin pages.
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$plugin_screens = array( $this->page_hook, $this->term_manager_hook, $this->post_manager_hook );

		if ( ! in_array( $screen->id, $plugin_screens, true ) ) {
			return;
		}

		$settings_url = admin_url( 'tools.php?page=auto-multi-meta#settings' );
		$api_key      = (string) get_option( AMM_OPT_API_KEY, '' );

		if ( '' === $api_key ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				sprintf(
					/* translators: 1: Opening anchor tag. 2: Closing anchor tag. */
					esc_html__( 'Auto Multi-Meta: No API key is configured. %1$sAdd your API key in Settings%2$s to start generating meta descriptions.', 'auto-multi-meta' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				)
			);
		}

		$seo_plugin = auto_multi_meta_get_plugin()->detect_seo_plugin();

		if ( 'none' === $seo_plugin ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__(
					'Auto Multi-Meta: No supported SEO plugin detected (Yoast SEO, RankMath, or The SEO Framework). Meta descriptions cannot be stored without an active SEO plugin.',
					'auto-multi-meta'
				)
			);
		}
	}

	/**
	 * AJAX handler: test the configured AI provider connection.
	 *
	 * Sends a minimal prompt and returns success or a descriptive error message.
	 *
	 * @return void
	 */
	public function ajax_test_connection(): void {
		check_ajax_referer( 'amm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ) );
		}

		$provider = AI_Factory::make();

		if ( is_wp_error( $provider ) ) {
			wp_send_json_error( array( 'message' => $provider->get_error_message() ) );
		}

		$result = $provider->generate( 'Reply with the single word: OK' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$auto_multi_meta_provider_name = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
		$auto_multi_meta_model_name    = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );

		wp_send_json_success(
			array(
				'message'  => sprintf(
					/* translators: 1: provider name, 2: model name, 3: AI response text. */
					__( 'Connection successful. Provider: %1$s | Model: %2$s | Response: %3$s', 'auto-multi-meta' ),
					$auto_multi_meta_provider_name,
					$auto_multi_meta_model_name,
					$result
				),
				'response' => $result,
				'provider' => $auto_multi_meta_provider_name,
				'model'    => $auto_multi_meta_model_name,
			)
		);
	}
}
