<?php
/**
 * Plugin Name: Auto Multi-Meta
 * Plugin URI: https://headwall.co.uk/
 * Description: Automatically generates SEO meta descriptions for taxonomy term archives and post types using AI (OpenAI, Anthropic, OpenRouter).
 * Version: 1.0.0-dev
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Headwall
 * Author URI: https://headwall.co.uk/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-multi-meta
 * Domain Path: /languages
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/constants.php';
require_once AMM_DIR . 'includes/class-settings.php';
require_once AMM_DIR . 'includes/class-admin-hooks.php';
require_once AMM_DIR . 'includes/class-plugin.php';

/**
 * Returns the main plugin instance.
 *
 * @return Plugin
 */
function amm(): Plugin {
	return Plugin::get_instance();
}

amm()->run();
