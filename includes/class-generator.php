<?php
/**
 * Meta description generator.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class Generator
 *
 * Orchestrates the full generation pipeline: checks overwrite protection,
 * builds context, calls the AI provider, validates the response length, and
 * stores the result via Meta_Handler.
 *
 * All public methods return a structured result array with keys:
 *   - status      (string) 'generated', 'skipped', or 'error'
 *   - description (string) The generated/existing description, or empty string
 *   - message     (string) Human-readable summary of the outcome
 *
 * Each attempt is recorded in the activity log option (AMM_OPT_GENERATION_LOG).
 */
class Generator {

	/**
	 * Context builder instance.
	 *
	 * @var Context_Builder
	 */
	private Context_Builder $context_builder;

	/**
	 * Meta handler instance.
	 *
	 * @var Meta_Handler
	 */
	private Meta_Handler $meta_handler;

	/**
	 * Constructor.
	 *
	 * @param Context_Builder $context_builder Context builder dependency.
	 * @param Meta_Handler    $meta_handler    Meta handler dependency.
	 */
	public function __construct( Context_Builder $context_builder, Meta_Handler $meta_handler ) {
		$this->context_builder = $context_builder;
		$this->meta_handler    = $meta_handler;
	}

	/**
	 * Generates and stores a meta description for a taxonomy term.
	 *
	 * Respects the overwrite protection setting and $force parameter.
	 * If $force is true, or the global overwrite setting is enabled,
	 * existing descriptions are replaced. Otherwise the term is skipped
	 * when a description already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $force    When true, overrides the global overwrite setting.
	 *
	 * @return array<string, string> Result with 'status', 'description', 'message' keys.
	 */
	public function generate_for_term( int $term_id, string $taxonomy, bool $force = false ): array {
		$provider    = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
		$model       = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
		$result      = null;
		$prompt      = '';
		$ai_output   = '';
		$description = '';

		// Determine whether to overwrite existing descriptions.
		$overwrite_setting = (bool) filter_var(
			get_option( AMM_OPT_OVERWRITE_EXISTING, AMM_DEFAULT_OVERWRITE_EXISTING ),
			FILTER_VALIDATE_BOOLEAN
		);
		$should_overwrite  = $force || $overwrite_setting;

		// Overwrite protection — skip if a description already exists.
		if ( ! $should_overwrite ) {
			$existing = $this->meta_handler->get_term_meta( $term_id, $taxonomy );
			if ( '' !== $existing ) {
				$result = array(
					'status'      => 'skipped',
					'description' => $existing,
					'message'     => __( 'Term already has a meta description. Use force to overwrite.', 'auto-multi-meta' ),
				);
			}
		}

		// Build the prompt from context.
		if ( is_null( $result ) ) {
			$prompt_or_error = $this->context_builder->build_term_prompt( $term_id, $taxonomy );
			if ( is_wp_error( $prompt_or_error ) ) {
				$result = $this->make_error_result( $prompt_or_error->get_error_message() );
			} else {
				$prompt = $prompt_or_error;
			}
		}

		// Call the AI provider.
		if ( is_null( $result ) ) {
			$ai_result = $this->call_ai( $prompt );
			if ( is_wp_error( $ai_result ) ) {
				$result = $this->make_error_result( $ai_result->get_error_message() );
			} else {
				$ai_output = $ai_result;
			}
		}

		// Validate the response length.
		if ( is_null( $result ) ) {
			$description = trim( $ai_output );
			$validation  = $this->validate_description( $description );
			if ( is_wp_error( $validation ) ) {
				$result = $this->make_error_result( $validation->get_error_message() );
			}
		}

		// Store the description via the meta handler.
		if ( is_null( $result ) ) {
			$stored = $this->meta_handler->set_term_meta( $term_id, $taxonomy, $description );
			if ( is_wp_error( $stored ) ) {
				$result = $this->make_error_result( $stored->get_error_message() );
			} else {
				$result = array(
					'status'      => 'generated',
					'description' => $description,
					'message'     => sprintf(
						/* translators: %d: Character count of the generated description. */
						__( 'Meta description generated and stored (%d characters).', 'auto-multi-meta' ),
						mb_strlen( $description )
					),
				);
			}
		}

		$final = (array) $result;
		$this->log_attempt( 'term', $term_id, $taxonomy, $provider, $model, $final );

		return $final;
	}

	/**
	 * Generates and stores a meta description for a post, page, or custom post type.
	 *
	 * Respects the overwrite protection setting and $force parameter.
	 * If $force is true, or the global overwrite setting is enabled,
	 * existing descriptions are replaced. Otherwise the post is skipped
	 * when a description already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $force   When true, overrides the global overwrite setting.
	 *
	 * @return array<string, string> Result with 'status', 'description', 'message' keys.
	 */
	public function generate_for_post( int $post_id, bool $force = false ): array {
		$provider    = (string) get_option( AMM_OPT_API_PROVIDER, AMM_DEFAULT_API_PROVIDER );
		$model       = (string) get_option( AMM_OPT_MODEL, AMM_DEFAULT_MODEL );
		$result      = null;
		$prompt      = '';
		$ai_output   = '';
		$description = '';

		// Determine whether to overwrite existing descriptions.
		$overwrite_setting = (bool) filter_var(
			get_option( AMM_OPT_OVERWRITE_EXISTING, AMM_DEFAULT_OVERWRITE_EXISTING ),
			FILTER_VALIDATE_BOOLEAN
		);
		$should_overwrite  = $force || $overwrite_setting;

		// Overwrite protection — skip if a description already exists.
		if ( ! $should_overwrite ) {
			$existing = $this->meta_handler->get_post_meta_description( $post_id );
			if ( '' !== $existing ) {
				$result = array(
					'status'      => 'skipped',
					'description' => $existing,
					'message'     => __( 'Post already has a meta description. Use force to overwrite.', 'auto-multi-meta' ),
				);
			}
		}

		// Build the prompt from context.
		if ( is_null( $result ) ) {
			$prompt_or_error = $this->context_builder->build_post_prompt( $post_id );
			if ( is_wp_error( $prompt_or_error ) ) {
				$result = $this->make_error_result( $prompt_or_error->get_error_message() );
			} else {
				$prompt = $prompt_or_error;
			}
		}

		// Call the AI provider.
		if ( is_null( $result ) ) {
			$ai_result = $this->call_ai( $prompt );
			if ( is_wp_error( $ai_result ) ) {
				$result = $this->make_error_result( $ai_result->get_error_message() );
			} else {
				$ai_output = $ai_result;
			}
		}

		// Validate the response length.
		if ( is_null( $result ) ) {
			$description = trim( $ai_output );
			$validation  = $this->validate_description( $description );
			if ( is_wp_error( $validation ) ) {
				$result = $this->make_error_result( $validation->get_error_message() );
			}
		}

		// Store the description via the meta handler.
		if ( is_null( $result ) ) {
			$stored = $this->meta_handler->set_post_meta( $post_id, $description );
			if ( is_wp_error( $stored ) ) {
				$result = $this->make_error_result( $stored->get_error_message() );
			} else {
				$result = array(
					'status'      => 'generated',
					'description' => $description,
					'message'     => sprintf(
						/* translators: %d: Character count of the generated description. */
						__( 'Meta description generated and stored (%d characters).', 'auto-multi-meta' ),
						mb_strlen( $description )
					),
				);
			}
		}

		$final = (array) $result;
		$this->log_attempt( 'post', $post_id, '', $provider, $model, $final );

		return $final;
	}

	/**
	 * Calls the configured AI provider with the given prompt.
	 *
	 * Delegates to AI_Factory to instantiate the correct provider from settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt The prompt to send.
	 *
	 * @return string|\WP_Error Generated text on success, WP_Error on failure.
	 */
	private function call_ai( string $prompt ): string|\WP_Error {
		$provider = AI_Factory::make();
		$result   = null;

		if ( is_wp_error( $provider ) ) {
			$result = $provider;
		} else {
			$result = $provider->generate( $prompt );
		}

		return $result;
	}

	/**
	 * Validates that a generated description is within acceptable length limits.
	 *
	 * Target length is AMM_META_DESC_TARGET_MIN–AMM_META_DESC_TARGET_MAX characters.
	 * Hard limits are AMM_META_DESC_MIN_LENGTH (minimum) and AMM_META_DESC_ABSOLUTE_MAX (maximum).
	 * Returns WP_Error if outside the hard limits.
	 *
	 * @since 1.0.0
	 *
	 * @param string $description Generated meta description.
	 *
	 * @return true|\WP_Error True if valid, WP_Error if outside hard limits.
	 */
	private function validate_description( string $description ): true|\WP_Error {
		$length = mb_strlen( $description );
		$result = null;

		if ( $length < AMM_META_DESC_MIN_LENGTH ) {
			$result = new \WP_Error(
				'amm_desc_too_short',
				sprintf(
					/* translators: 1: Character count. 2: Minimum length. */
					__( 'Generated description is too short (%1$d characters, minimum %2$d).', 'auto-multi-meta' ),
					$length,
					AMM_META_DESC_MIN_LENGTH
				)
			);
		}

		if ( is_null( $result ) && $length > AMM_META_DESC_ABSOLUTE_MAX ) {
			$result = new \WP_Error(
				'amm_desc_too_long',
				sprintf(
					/* translators: 1: Character count. 2: Maximum length. */
					__( 'Generated description is too long (%1$d characters, maximum %2$d).', 'auto-multi-meta' ),
					$length,
					AMM_META_DESC_ABSOLUTE_MAX
				)
			);
		}

		if ( is_null( $result ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Builds a standardised error result array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Error message.
	 *
	 * @return array<string, string> Error result.
	 */
	private function make_error_result( string $message ): array {
		return array(
			'status'      => 'error',
			'description' => '',
			'message'     => $message,
		);
	}

	/**
	 * Appends a generation attempt to the activity log.
	 *
	 * Entries are prepended (newest first) and the log is trimmed to
	 * AMM_GENERATION_LOG_MAX_ENTRIES after each write.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $type     'term' or 'post'.
	 * @param int                   $id       Term ID or post ID.
	 * @param string                $taxonomy Taxonomy slug (empty string for posts).
	 * @param string                $provider AI provider slug.
	 * @param string                $model    AI model name.
	 * @param array<string, string> $result  Generation result array.
	 *
	 * @return void
	 */
	private function log_attempt( string $type, int $id, string $taxonomy, string $provider, string $model, array $result ): void {
		$log = get_option( AMM_OPT_GENERATION_LOG, array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$now   = new \DateTime( 'now', wp_timezone() );
		$entry = array(
			'type'      => $type,
			'id'        => $id,
			'taxonomy'  => $taxonomy,
			'provider'  => $provider,
			'model'     => $model,
			'status'    => $result['status'],
			'message'   => $result['message'],
			'timestamp' => $now->format( 'Y-m-d H:i:s T' ),
		);

		array_unshift( $log, $entry );

		if ( count( $log ) > AMM_GENERATION_LOG_MAX_ENTRIES ) {
			$log = array_slice( $log, 0, AMM_GENERATION_LOG_MAX_ENTRIES );
		}

		update_option( AMM_OPT_GENERATION_LOG, $log, false );
	}
}
