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
	}

	/**
	 * Enqueues CSS and JS only on the plugin settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $this->page_hook !== $hook_suffix ) {
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
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'amm_admin' ),
				'defaultTab' => 'settings',
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
}
