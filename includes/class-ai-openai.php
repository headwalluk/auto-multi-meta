<?php
/**
 * OpenAI AI provider.
 *
 * @package Auto_Multi_Meta
 */

namespace Auto_Multi_Meta;

defined( 'ABSPATH' ) || die();

/**
 * Class AI_OpenAI
 *
 * Sends prompts to the OpenAI chat completions API.
 *
 * Endpoint: https://api.openai.com/v1/chat/completions
 * Auth:     Authorization: Bearer {api_key}
 */
class AI_OpenAI extends AI_Provider {

	/**
	 * OpenAI API endpoint URL.
	 *
	 * @var string
	 */
	private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Human-readable provider name used in error messages.
	 *
	 * @var string
	 */
	private const PROVIDER_NAME = 'OpenAI';

	/**
	 * Sends a prompt to OpenAI and returns the generated text.
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
				__( 'Failed to encode OpenAI request body.', 'auto-multi-meta' )
			);
		} else {
			$response = wp_remote_post(
				self::ENDPOINT,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $this->api_key,
						'Content-Type'  => 'application/json',
					],
					'body'    => $body,
					'timeout' => AMM_HTTP_TIMEOUT,
				]
			);

			$http_error = $this->check_response_error( $response, self::PROVIDER_NAME );

			if ( ! is_null( $http_error ) ) {
				$output = $http_error;
			} else {
				$output = $this->parse_openai_response(
					wp_remote_retrieve_body( $response ),
					self::PROVIDER_NAME
				);
			}
		}

		return $output;
	}
}
