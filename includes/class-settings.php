<?php
/**
 * Settings registration and sanitisation.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Settings
 *
 * Registers all plugin options with the WordPress Settings API
 * and provides sanitisation callbacks for each.
 */
class Settings {

	/**
	 * Option group used for settings_fields() in the admin form.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'amm_settings_group';

	/**
	 * Allowed AI provider values.
	 *
	 * @var string[]
	 */
	const ALLOWED_PROVIDERS = [ 'openai', 'anthropic', 'openrouter' ];

	/**
	 * Registers all plugin settings with the WordPress Settings API.
	 *
	 * Hooked on admin_init.
	 *
	 * @return void
	 */
	public function register(): void {
		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_API_PROVIDER,
			[
				'sanitize_callback' => [ $this, 'sanitize_api_provider' ],
				'default'           => AMM_DEFAULT_API_PROVIDER,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_API_KEY,
			[
				'sanitize_callback' => [ $this, 'sanitize_api_key' ],
				'default'           => '',
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_MODEL,
			[
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => AMM_DEFAULT_MODEL,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_MAX_TOKENS,
			[
				'sanitize_callback' => [ $this, 'sanitize_max_tokens' ],
				'default'           => AMM_DEFAULT_MAX_TOKENS,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_PROMPT_TEMPLATE_TERMS,
			[
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => AMM_DEFAULT_PROMPT_TEMPLATE_TERMS,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_PROMPT_TEMPLATE_POSTS,
			[
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => AMM_DEFAULT_PROMPT_TEMPLATE_POSTS,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_OVERWRITE_EXISTING,
			[
				'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
				'default'           => AMM_DEFAULT_OVERWRITE_EXISTING,
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_ENABLED_TAXONOMIES,
			[
				'sanitize_callback' => [ $this, 'sanitize_enabled_taxonomies' ],
				'default'           => [],
			]
		);

		register_setting(
			self::OPTION_GROUP,
			AMM_OPT_ENABLED_POST_TYPES,
			[
				'sanitize_callback' => [ $this, 'sanitize_enabled_post_types' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitises the API provider value.
	 *
	 * @param mixed $input Raw input value.
	 * @return string
	 */
	public function sanitize_api_provider( $input ): string {
		$provider = sanitize_text_field( wp_unslash( (string) $input ) );

		if ( ! in_array( $provider, self::ALLOWED_PROVIDERS, true ) ) {
			$provider = AMM_DEFAULT_API_PROVIDER;
		}

		return $provider;
	}

	/**
	 * Sanitises the API key.
	 *
	 * Trims whitespace; preserves the existing key if an empty value is submitted
	 * to avoid accidentally clearing a saved key.
	 *
	 * @param mixed $input Raw input value.
	 * @return string
	 */
	public function sanitize_api_key( $input ): string {
		$key = sanitize_text_field( wp_unslash( (string) $input ) );

		if ( '' === $key ) {
			$key = (string) get_option( AMM_OPT_API_KEY, '' );
		}

		return $key;
	}

	/**
	 * Sanitises the max tokens value.
	 *
	 * Clamps to a sensible range (50–4096).
	 *
	 * @param mixed $input Raw input value.
	 * @return int
	 */
	public function sanitize_max_tokens( $input ): int {
		$tokens = absint( $input );

		if ( $tokens < 50 ) {
			$tokens = 50;
		} elseif ( $tokens > 4096 ) {
			$tokens = 4096;
		}

		return $tokens;
	}

	/**
	 * Sanitises a checkbox value (returns true/false).
	 *
	 * @param mixed $input Raw input value.
	 * @return bool
	 */
	public function sanitize_checkbox( $input ): bool {
		return '1' === (string) $input || true === $input;
	}

	/**
	 * Sanitises the enabled taxonomies array.
	 *
	 * Returns an empty array if the field group was submitted but no items were selected.
	 * Returns the existing value if the field group was not part of the submission at all.
	 *
	 * @param mixed $input Raw input value (array of taxonomy slugs or null).
	 * @return string[]
	 */
	public function sanitize_enabled_taxonomies( $input ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by options.php.
		$field_submitted = isset( $_POST['amm_taxonomies_submitted'] );
		// phpcs:enable

		$result = get_option( AMM_OPT_ENABLED_TAXONOMIES, [] );

		if ( $field_submitted ) {
			if ( is_array( $input ) ) {
				$result = array_values(
					array_filter(
						array_map( 'sanitize_text_field', array_map( 'wp_unslash', $input ) )
					)
				);
			} else {
				$result = [];
			}
		}

		return $result;
	}

	/**
	 * Sanitises the enabled post types array.
	 *
	 * Returns an empty array if the field group was submitted but no items were selected.
	 * Returns the existing value if the field group was not part of the submission at all.
	 *
	 * @param mixed $input Raw input value (array of post type slugs or null).
	 * @return string[]
	 */
	public function sanitize_enabled_post_types( $input ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by options.php.
		$field_submitted = isset( $_POST['amm_post_types_submitted'] );
		// phpcs:enable

		$result = get_option( AMM_OPT_ENABLED_POST_TYPES, [] );

		if ( $field_submitted ) {
			if ( is_array( $input ) ) {
				$result = array_values(
					array_filter(
						array_map( 'sanitize_text_field', array_map( 'wp_unslash', $input ) )
					)
				);
			} else {
				$result = [];
			}
		}

		return $result;
	}
}
