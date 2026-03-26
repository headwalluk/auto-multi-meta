<?php
/**
 * Anthropic AI provider.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class AI_Anthropic
 *
 * Sends prompts to the Anthropic Messages API.
 *
 * Endpoint: https://api.anthropic.com/v1/messages
 * Auth:     x-api-key: {api_key}, anthropic-version: 2023-06-01
 */
class AI_Anthropic extends AI_Provider {

	/**
	 * Anthropic API endpoint URL.
	 *
	 * @var string
	 */
	private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

	/**
	 * Anthropic API version header value.
	 *
	 * @var string
	 */
	private const API_VERSION = '2023-06-01';

	/**
	 * Human-readable provider name used in error messages.
	 *
	 * @var string
	 */
	private const PROVIDER_NAME = 'Anthropic';

	/**
	 * Sends a prompt to Anthropic and returns the generated text.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $prompt  The prompt to send.
	 * @param array<string, mixed> $options Optional overrides: model, max_tokens.
	 *
	 * @return string|\WP_Error Generated text on success, WP_Error on failure.
	 */
	public function generate( string $prompt, array $options = [] ): string|\WP_Error {
		$model      = isset( $options['model'] ) ? (string) $options['model'] : $this->model;
		$max_tokens = isset( $options['max_tokens'] ) ? (int) $options['max_tokens'] : $this->max_tokens;

		$request_body = [
			'model'      => $model,
			'messages'   => [
				[
					'role'    => 'user',
					'content' => $prompt,
				],
			],
			'max_tokens' => $max_tokens,
		];

		$body = wp_json_encode( $request_body );

		if ( false === $body ) {
			$output = new \WP_Error(
				'ai_encode_error',
				__( 'Failed to encode Anthropic request body.', 'auto-multi-meta' )
			);
		} else {
			$response = wp_remote_post(
				self::ENDPOINT,
				[
					'headers' => [
						'x-api-key'         => $this->api_key,
						'anthropic-version' => self::API_VERSION,
						'Content-Type'      => 'application/json',
					],
					'body'    => $body,
					'timeout' => AMM_HTTP_TIMEOUT,
				]
			);

			$http_error = $this->check_response_error( $response, self::PROVIDER_NAME );

			if ( ! is_null( $http_error ) ) {
				$output = $http_error;
			} else {
				$output = $this->parse_anthropic_response( wp_remote_retrieve_body( $response ) );
			}
		}

		return $output;
	}

	/**
	 * Parses an Anthropic Messages API response body.
	 *
	 * Extracts the first text block from the content array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body Raw JSON response body.
	 *
	 * @return string|\WP_Error Extracted text content or WP_Error on failure.
	 */
	private function parse_anthropic_response( string $body ): string|\WP_Error {
		$output  = '';
		$decoded = json_decode( $body, true );
		$text    = '';

		if ( isset( $decoded['content'] ) && is_array( $decoded['content'] ) ) {
			foreach ( $decoded['content'] as $block ) {
				if ( isset( $block['type'] ) && 'text' === $block['type'] && isset( $block['text'] ) ) {
					$text = (string) $block['text'];
					break;
				}
			}
		}

		if ( '' === trim( $text ) ) {
			$output = new \WP_Error(
				'ai_malformed_response',
				__( 'Anthropic returned an empty or malformed response.', 'auto-multi-meta' )
			);
		} else {
			$output = trim( $text );
		}

		return $output;
	}
}
