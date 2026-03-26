<?php
/**
 * Plugin uninstall handler.
 *
 * Runs when the plugin is deleted via the WordPress admin. Removes all
 * options and transients created by Auto Multi-Meta.
 *
 * @package Auto_Multi_Meta
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || die();

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Reason: Variables in uninstall.php are template-scoped locals in global scope, not
// true reusable globals. Both use the amm_ prefix; PHPCS flags 3-char prefixes incorrectly.

// Option keys — must match constants.php (cannot require it here as the plugin is not loaded).
$amm_options = array(
	'amm_api_provider',
	'amm_api_key',
	'amm_model',
	'amm_enabled_taxonomies',
	'amm_enabled_post_types',
	'amm_prompt_template_terms',
	'amm_prompt_template_posts',
	'amm_max_tokens',
	'amm_overwrite_existing',
	'amm_use_site_language',
	'amm_generation_log',
	'amm_batch_delay',
	'amm_batch_notice',
);

foreach ( $amm_options as $amm_option_key ) {
	delete_option( $amm_option_key );
}

// Delete the active batch transient.
delete_transient( 'amm_active_batch' );

// If Action Scheduler is available, cancel any pending AMM batch jobs.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'amm_process_batch_term', array(), 'auto-multi-meta' );
	as_unschedule_all_actions( 'amm_process_batch_post', array(), 'auto-multi-meta' );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
