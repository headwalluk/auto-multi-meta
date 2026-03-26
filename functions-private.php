<?php
/**
 * Private helper functions.
 *
 * @package Auto_Multi_Meta
 */

defined( 'ABSPATH' ) || die();

/**
 * Returns the main plugin instance (singleton via global).
 *
 * @return Auto_Multi_Meta\Plugin
 */
function auto_multi_meta_get_plugin(): Auto_Multi_Meta\Plugin {
	global $auto_multi_meta_plugin;

	if ( is_null( $auto_multi_meta_plugin ) ) {
		$auto_multi_meta_plugin = new Auto_Multi_Meta\Plugin();
	}

	return $auto_multi_meta_plugin;
}
