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
 * Singleton entry point. Registers core hooks and provides lazy access
 * to Settings and Admin_Hooks instances.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

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
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the plugins_loaded hook to initialise the plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Initialises the plugin after all plugins are loaded.
	 *
	 * @return void
	 */
	public function init(): void {
		load_plugin_textdomain( 'auto-multi-meta', false, AMM_DIR . 'languages' );

		if ( is_admin() ) {
			$this->get_admin_hooks()->register();
		}
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
			$this->admin_hooks = new Admin_Hooks( $this );
		}

		return $this->admin_hooks;
	}

	/**
	 * Detects the active SEO plugin.
	 *
	 * Returns 'yoast', 'rankmath', or 'none'.
	 *
	 * @return string
	 */
	public function detect_seo_plugin(): string {
		$detected = 'none';

		if ( defined( 'WPSEO_VERSION' ) ) {
			$detected = 'yoast';
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$detected = 'rankmath';
		}

		return $detected;
	}
}
