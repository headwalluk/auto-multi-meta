<?php
/**
 * Abstract AI provider base class.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class AI_Provider
 *
 * Abstract base for AI provider implementations. Defines the common
 * contract and provides shared HTTP error handling.
 */
abstract class AI_Provider {

	/**
	 * API key for the provider.
	 *
	 * @var string
	 */
	protected string $api_key;

	/**
	 * Model name to use for generation.
	 *
	 * @var string
	 */
	protected string $model;

	/**
	 * Maximum tokens for AI responses.
	 *
	 * @var int
	 */
	protected int $max_tokens;

	/**
	 * Constructor.
	 *
	 * @param string $api_key    API key for this provider.
	 * @param string $model      Model name to use.
	 * @param int    $max_tokens Maximum response tokens.
	 */
	public function __construct( string $api_key, string $model, int $max_tokens ) {
		$this->api_key    = $api_key;
		$this->model      = $model;
		$this->max_tokens = $max_tokens;
	}

	/**
	 * Sends a prompt to the AI provider and returns generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $prompt  The prompt to send.
	 * @param array<string, mixed> $options Optional overrides (model, max_tokens).
	 *
	 * @return string|\WP_Error Generated text on success, WP_Error on failure.
	 */
	abstract public function generate( string $prompt, array $options = [] ): string|\WP_Error;

	/**
	 * Checks an HTTP response for common API errors.
	 *
	 * Handles WP_Error (connection failure / timeout), HTTP 401 (bad key),
	 * HTTP 429 (rate limited), and any other non-2xx status.
	 * Returns null when the response looks healthy.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|\WP_Error $response WP HTTP API response.
	 * @param string                         $provider Human-readable provider name.
	 *
	 * @return \WP_Error|null WP_Error on failure, null when response is healthy.
	 */
	protected function check_response_error( array|\WP_Error $response, string $provider ): ?\WP_Error {
		$error = null;

		if ( is_wp_error( $response ) ) {
			$error = new \WP_Error(
				'ai_http_error',
				sprintf(
					/* translators: 1: Provider name. 2: Error message. */
					__( '%1$s request failed: %2$s', 'auto-multi-meta' ),
					$provider,
					$response->get_error_message()
				)
			);
		}

		if ( is_null( $error ) ) {
			$status_code = (int) wp_remote_retrieve_response_code( $response );

			if ( 401 === $status_code ) {
				$error = new \WP_Error(
					'ai_invalid_key',
					sprintf(
						/* translators: %s: Provider name. */
						__( '%s API key is invalid or missing.', 'auto-multi-meta' ),
						$provider
					)
				);
			} elseif ( 429 === $status_code ) {
				$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
				$error       = new \WP_Error(
					'ai_rate_limited',
					sprintf(
						/* translators: 1: Provider name. 2: Retry-After value. */
						__( '%1$s rate limit exceeded. Retry after: %2$s seconds.', 'auto-multi-meta' ),
						$provider,
						$retry_after ? $retry_after : __( 'unknown', 'auto-multi-meta' )
					)
				);
			} elseif ( $status_code < 200 || $status_code >= 300 ) {
				$body         = wp_remote_retrieve_body( $response );
				$decoded      = json_decode( $body, true );
				$api_message  = isset( $decoded['error']['message'] ) ? (string) $decoded['error']['message'] : '';
				$error_detail = $api_message ? $api_message : sprintf( 'HTTP %d', $status_code );

				$error = new \WP_Error(
					'ai_api_error',
					sprintf(
						/* translators: 1: Provider name. 2: Error detail. */
						__( '%1$s API error: %2$s', 'auto-multi-meta' ),
						$provider,
						$error_detail
					)
				);
			}
		}

		return $error;
	}

	/**
	 * Parses an OpenAI-compatible chat completion response body.
	 *
	 * Handles OpenAI and OpenRouter responses which share the same format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body     Raw JSON response body.
	 * @param string $provider Human-readable provider name.
	 *
	 * @return string|\WP_Error Extracted text content or WP_Error on failure.
	 */
	protected function parse_openai_response( string $body, string $provider ): string|\WP_Error {
		$output  = '';
		$decoded = json_decode( $body, true );
		$text    = isset( $decoded['choices'][0]['message']['content'] ) ? (string) $decoded['choices'][0]['message']['content'] : '';

		if ( '' === trim( $text ) ) {
			$output = new \WP_Error(
				'ai_malformed_response',
				sprintf(
					/* translators: %s: Provider name. */
					__( '%s returned an empty or malformed response.', 'auto-multi-meta' ),
					$provider
				)
			);
		} else {
			$output = trim( $text );
		}

		return $output;
	}
}
