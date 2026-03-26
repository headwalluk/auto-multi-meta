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
	 * Main plugin instance.
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

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
	 * Constructor.
	 *
	 * @param Plugin $plugin Main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Registers all admin-side WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this->plugin->get_settings(), 'register' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_amm_test_connection', [ $this, 'ajax_test_connection' ] );
		add_action( 'wp_ajax_amm_generate_single', [ $this, 'ajax_generate_single' ] );
		add_action( 'wp_ajax_amm_generate_bulk', [ $this, 'ajax_generate_bulk' ] );
		add_action( 'wp_ajax_amm_preview', [ $this, 'ajax_preview' ] );
	}

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
			[ $this, 'render_settings_page' ]
		);

		// Hidden submenu pages for the manager views (not shown in navigation).
		$this->term_manager_hook = (string) add_submenu_page(
			null,
			__( 'Auto Multi-Meta — Term Manager', 'auto-multi-meta' ),
			'',
			'manage_options',
			'amm-term-manager',
			[ $this, 'render_term_manager' ]
		);

		$this->post_manager_hook = (string) add_submenu_page(
			null,
			__( 'Auto Multi-Meta — Post Manager', 'auto-multi-meta' ),
			'',
			'manage_options',
			'amm-post-manager',
			[ $this, 'render_post_manager' ]
		);
	}

	/**
	 * Enqueues CSS and JS only on the plugin settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$is_plugin_page = in_array(
			$hook_suffix,
			[ $this->page_hook, $this->term_manager_hook, $this->post_manager_hook ],
			true
		);

		if ( ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'amm-admin',
			AMM_URL . 'assets/admin/admin.css',
			[],
			AMM_VERSION
		);

		wp_enqueue_script(
			'amm-admin',
			AMM_URL . 'assets/admin/admin.js',
			[],
			AMM_VERSION,
			true
		);

		wp_localize_script(
			'amm-admin',
			'ammAdmin',
			[
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'amm_admin' ),
				'defaultTab'     => 'settings',
				'termManagerUrl' => admin_url( 'tools.php?page=amm-term-manager' ),
				'postManagerUrl' => admin_url( 'tools.php?page=amm-post-manager' ),
				'i18n'           => [
					'generating'    => __( 'Generating…', 'auto-multi-meta' ),
					'previewing'    => __( 'Previewing…', 'auto-multi-meta' ),
					'generate'      => __( 'Generate', 'auto-multi-meta' ),
					'regenerate'    => __( 'Regenerate', 'auto-multi-meta' ),
					'preview'       => __( 'Preview', 'auto-multi-meta' ),
					/* translators: %1$d: current item number, %2$d: total items. */
					'bulkProgress'  => __( 'Generating %1$d of %2$d…', 'auto-multi-meta' ),
					/* translators: %1$d: generated count, %2$d: skipped count, %3$d: error count. */
					'bulkComplete'  => __( '%1$d generated, %2$d skipped, %3$d errors.', 'auto-multi-meta' ),
					'requestFailed' => __( 'Request failed. Please try again.', 'auto-multi-meta' ),
				],
			]
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

		$seo_plugin     = $this->plugin->detect_seo_plugin();
		$all_taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
		$all_post_types = get_post_types( [ 'public' => true ], 'objects' );

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

		$meta_handler       = $this->plugin->get_meta_handler();
		$term_manager       = new Term_Manager( $meta_handler );
		$enabled_taxonomies = (array) get_option( AMM_OPT_ENABLED_TAXONOMIES, [] );

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

		$meta_handler       = $this->plugin->get_meta_handler();
		$post_manager       = new Post_Manager( $meta_handler );
		$enabled_post_types = (array) get_option( AMM_OPT_ENABLED_POST_TYPES, [] );

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
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ] );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$item_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
		$force    = isset( $_POST['force'] ) && '1' === $_POST['force'];
		// phpcs:enable

		if ( ! $item_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid item ID.', 'auto-multi-meta' ) ] );
		}

		$generator = $this->plugin->get_generator();
		$result    = null;

		if ( 'term' === $type ) {
			if ( '' === $taxonomy ) {
				wp_send_json_error( [ 'message' => __( 'Taxonomy is required for term generation.', 'auto-multi-meta' ) ] );
			}

			$result = $generator->generate_for_term( $item_id, $taxonomy, $force );
		} elseif ( 'post' === $type ) {
			$result = $generator->generate_for_post( $item_id, $force );
		} else {
			wp_send_json_error( [ 'message' => __( 'Invalid type. Must be "term" or "post".', 'auto-multi-meta' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ] );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$raw_ids = isset( $_POST['item_ids'] ) && is_array( $_POST['item_ids'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['item_ids'] ) )
			: [];
		// phpcs:enable

		if ( empty( $raw_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No items selected.', 'auto-multi-meta' ) ] );
		}

		$generator = $this->plugin->get_generator();
		$results   = [
			'generated' => 0,
			'skipped'   => 0,
			'errors'    => 0,
			'items'     => [],
		];

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

				$results['items'][] = [
					'id'     => $raw_id,
					'status' => $item_result['status'],
					'desc'   => $item_result['description'],
					'msg'    => $item_result['message'],
				];
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
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ] );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer.
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$item_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
		// phpcs:enable

		if ( ! $item_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid item ID.', 'auto-multi-meta' ) ] );
		}

		$generator = $this->plugin->get_generator();
		$result    = null;

		if ( 'term' === $type ) {
			if ( '' === $taxonomy ) {
				wp_send_json_error( [ 'message' => __( 'Taxonomy is required for term preview.', 'auto-multi-meta' ) ] );
			}

			$result = $generator->preview_for_term( $item_id, $taxonomy );
		} elseif ( 'post' === $type ) {
			$result = $generator->preview_for_post( $item_id );
		} else {
			wp_send_json_error( [ 'message' => __( 'Invalid type. Must be "term" or "post".', 'auto-multi-meta' ) ] );
		}

		if ( 'error' === $result['status'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
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
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'auto-multi-meta' ) ] );
		}

		$provider = AI_Factory::make();

		if ( is_wp_error( $provider ) ) {
			wp_send_json_error( [ 'message' => $provider->get_error_message() ] );
		}

		$result = $provider->generate( 'Reply with the single word: OK' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success(
			[
				'message'  => __( 'Connection successful.', 'auto-multi-meta' ),
				'response' => $result,
			]
		);
	}
}
