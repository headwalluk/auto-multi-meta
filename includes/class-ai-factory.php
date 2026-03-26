<?php
/**
 * AI provider factory.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class AI_Factory
 *
 * Reads plugin settings and instantiates the correct AI provider.
 */
class AI_Factory {

	/**
	 * Builds an AI provider instance from the current plugin settings.
	 *
	 * Returns a WP_Error if no API key is configured or if the selected
	 * provider slug is not recognised.
	 *
	 * @since 1.0.0
	 *
	 * @return AI_Provider|\WP_Error Provider instance or WP_Error on failure.
	 */
	public static function make(): AI_Provider|\WP_Error {
		$provider   = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
		$api_key    = (string) get_option( AMM_OPT_API_KEY, '' );
		$model      = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
		$max_tokens = (int) get_option( AMM_OPT_MAX_TOKENS, AMM_DEFAULT_MAX_TOKENS );

		$result = null;

		if ( '' === trim( $api_key ) ) {
			$result = new \WP_Error(
				'ai_no_key',
				__( 'No API key configured. Please add your API key in the plugin settings.', 'auto-multi-meta' )
			);
		}

		if ( is_null( $result ) ) {
			if ( 'openai' === $provider ) {
				$result = new AI_OpenAI( $api_key, $model, $max_tokens );
			} elseif ( 'anthropic' === $provider ) {
				$result = new AI_Anthropic( $api_key, $model, $max_tokens );
			} elseif ( 'openrouter' === $provider ) {
				$result = new AI_OpenRouter( $api_key, $model, $max_tokens );
			} else {
				$result = new \WP_Error(
					'ai_unknown_provider',
					sprintf(
						/* translators: %s: Provider slug. */
						__( 'Unknown AI provider: %s', 'auto-multi-meta' ),
						$provider
					)
				);
			}
		}

		return $result;
	}
}
