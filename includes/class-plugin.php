<?php
/**
 * Main plugin class.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Plugin
 *
 * Entry point. Registers core hooks and provides lazy access
 * to subsystem instances.
 */
class Plugin {

	/**
	 * Settings instance (lazy-loaded).
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Admin_Hooks instance (lazy-loaded).
	 *
	 * @var Admin_Hooks|null
	 */
	private ?Admin_Hooks $admin_hooks = null;

	/**
	 * Meta_Handler instance (lazy-loaded).
	 *
	 * @var Meta_Handler|null
	 */
	private ?Meta_Handler $meta_handler = null;

	/**
	 * Context_Builder instance (lazy-loaded).
	 *
	 * @var Context_Builder|null
	 */
	private ?Context_Builder $context_builder = null;

	/**
	 * Generator instance (lazy-loaded).
	 *
	 * @var Generator|null
	 */
	private ?Generator $generator = null;

	/**
	 * Batch_Processor instance (lazy-loaded).
	 *
	 * @var Batch_Processor|null
	 */
	private ?Batch_Processor $batch_processor = null;

	/**
	 * Registers top-level WordPress hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this->get_admin_hooks(), 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->get_admin_hooks(), 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this->get_admin_hooks(), 'display_batch_notice' ) );
		add_action( 'admin_notices', array( $this->get_admin_hooks(), 'display_setup_notices' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_amm_test_connection', array( $this->get_admin_hooks(), 'ajax_test_connection' ) );
		add_action( 'wp_ajax_amm_generate_single', array( $this->get_admin_hooks(), 'ajax_generate_single' ) );
		add_action( 'wp_ajax_amm_generate_bulk', array( $this->get_admin_hooks(), 'ajax_generate_bulk' ) );
		add_action( 'wp_ajax_amm_preview', array( $this->get_admin_hooks(), 'ajax_preview' ) );
		add_action( 'wp_ajax_amm_start_batch', array( $this->get_admin_hooks(), 'ajax_start_batch' ) );
		add_action( 'wp_ajax_amm_batch_progress', array( $this->get_admin_hooks(), 'ajax_batch_progress' ) );
		add_action( 'wp_ajax_amm_cancel_batch', array( $this->get_admin_hooks(), 'ajax_cancel_batch' ) );
		add_action( 'wp_ajax_amm_clear_log', array( $this->get_admin_hooks(), 'ajax_clear_log' ) );

		// Batch processor action callbacks must be registered on every request
		// because Action Scheduler dispatches on any incoming WordPress request.
		add_action( AMM_BATCH_ACTION_TERM, array( $this->get_batch_processor(), 'process_term_item' ), 10, 3 );
		add_action( AMM_BATCH_ACTION_POST, array( $this->get_batch_processor(), 'process_post_item' ), 10, 2 );
	}

	/**
	 * Initialises the plugin after all plugins are loaded.
	 *
	 * @return void
	 */
	public function init(): void {
		// Intentionally empty. WordPress auto-loads translations for
		// plugins hosted on wordpress.org since WP 4.6.
	}

	/**
	 * Runs on admin_init — registers settings with the Settings API.
	 *
	 * @return void
	 */
	public function admin_init(): void {
		$this->get_settings()->register();
	}

	/**
	 * Returns the Settings instance, creating it if needed.
	 *
	 * @return Settings
	 */
	public function get_settings(): Settings {
		if ( null === $this->settings ) {
			$this->settings = new Settings();
		}

		return $this->settings;
	}

	/**
	 * Returns the Admin_Hooks instance, creating it if needed.
	 *
	 * @return Admin_Hooks
	 */
	public function get_admin_hooks(): Admin_Hooks {
		if ( null === $this->admin_hooks ) {
			$this->admin_hooks = new Admin_Hooks();
		}

		return $this->admin_hooks;
	}

	/**
	 * Returns the Meta_Handler instance, creating it if needed.
	 *
	 * @return Meta_Handler
	 */
	public function get_meta_handler(): Meta_Handler {
		if ( null === $this->meta_handler ) {
			$this->meta_handler = new Meta_Handler( $this->detect_seo_plugin() );
		}

		return $this->meta_handler;
	}

	/**
	 * Returns the Context_Builder instance, creating it if needed.
	 *
	 * @return Context_Builder
	 */
	public function get_context_builder(): Context_Builder {
		if ( null === $this->context_builder ) {
			$this->context_builder = new Context_Builder();
		}

		return $this->context_builder;
	}

	/**
	 * Returns the Generator instance, creating it if needed.
	 *
	 * @return Generator
	 */
	public function get_generator(): Generator {
		if ( null === $this->generator ) {
			$this->generator = new Generator( $this->get_context_builder(), $this->get_meta_handler() );
		}

		return $this->generator;
	}

	/**
	 * Returns the Batch_Processor instance, creating it if needed.
	 *
	 * @return Batch_Processor
	 */
	public function get_batch_processor(): Batch_Processor {
		if ( null === $this->batch_processor ) {
			$this->batch_processor = new Batch_Processor();
		}

		return $this->batch_processor;
	}

	/**
	 * Detects the active SEO plugin.
	 *
	 * Returns 'yoast', 'rankmath', 'tsf', or 'none'.
	 *
	 * @return string
	 */
	public function detect_seo_plugin(): string {
		$detected = 'none';

		if ( defined( 'WPSEO_VERSION' ) ) {
			$detected = 'yoast';
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$detected = 'rankmath';
		} elseif ( defined( 'THE_SEO_FRAMEWORK_PRESENT' ) ) {
			$detected = 'tsf';
		}

		return $detected;
	}
}
