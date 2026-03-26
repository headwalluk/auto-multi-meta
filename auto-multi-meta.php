<?php
/**
 * Plugin Name: Auto Multi-Meta
 * Plugin URI: https://headwall-hosting.com/
 * Description: Automatically generates SEO meta descriptions for taxonomy term archives and post types using AI (OpenAI, Anthropic, OpenRouter).
 * Version: 0.3.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Paul Faulkner
 * Author URI: https://headwall-hositng.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-multi-meta
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions-private.php';

require_once AMM_DIR . 'includes/class-settings.php';
require_once AMM_DIR . 'includes/class-ai-provider.php';
require_once AMM_DIR . 'includes/class-ai-openai.php';
require_once AMM_DIR . 'includes/class-ai-anthropic.php';
require_once AMM_DIR . 'includes/class-ai-openrouter.php';
require_once AMM_DIR . 'includes/class-ai-factory.php';
require_once AMM_DIR . 'includes/class-context-builder.php';
require_once AMM_DIR . 'includes/class-meta-handler.php';
require_once AMM_DIR . 'includes/class-generator.php';
require_once AMM_DIR . 'includes/class-term-manager.php';
require_once AMM_DIR . 'includes/class-post-manager.php';
require_once AMM_DIR . 'includes/class-batch-processor.php';
require_once AMM_DIR . 'includes/class-admin-hooks.php';
require_once AMM_DIR . 'includes/class-plugin.php';

/**
 * Boots the plugin.
 *
 * @return void
 */
function auto_multi_meta_plugin_run(): void {
	auto_multi_meta_get_plugin()->run();
}
auto_multi_meta_plugin_run();
