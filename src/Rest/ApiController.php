<?php
/**
 * REST API controller for the ChatKit plugin.
 */
declare( strict_types=1 );

namespace ChatkitWp\Rest;

use ChatkitWp\Settings\Options;
use WP_Error;
use WP_REST_Request;

/**
 * Registers routes that proxy requests to the OpenAI ChatKit API.
 */
final class ApiController {
        public function __construct( private Options $options ) {}

        /**
         * Register REST API routes used by the plugin.
         */
        public function register_routes(): void {
                \register_rest_route(
                        'chatkit/v1',
                        '/session',
                        [
                                'callback'            => [ $this, 'create_session' ],
                                'methods'             => 'POST',
                                'permission_callback' => function ( WP_REST_Request $request ): bool {
                                        unset( $request );

                                        $referer  = \wp_get_referer();
                                        $home_url = \home_url();

                                        if ( $referer && 0 === strpos( $referer, $home_url ) ) {
                                                return true;
                                        }

                                        if ( empty( $referer ) && ! empty( $_SERVER['HTTP_HOST'] ) ) {
                                                $current_host = parse_url( $home_url, PHP_URL_HOST );
                                                $request_host = \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_HOST'] ) );

                                                if ( $current_host === $request_host ) {
                                                        return true;
                                                }
                                        }

                                        return true;
                                },
                        ]
                );

                \register_rest_route(
                        'chatkit/v1',
                        '/test',
                        [
                                'callback'            => [ $this, 'test_connection' ],
                                'methods'             => 'POST',
                                'permission_callback' => static function (): bool {
                                        return \current_user_can( 'manage_options' );
                                },
                        ]
                );
        }

        /**
         * Create a session with the OpenAI API.
         *
         * @return array|WP_Error
         */
        public function create_session( WP_REST_Request $request ) {
                unset( $request );

                $ip          = filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP ) ?: 'unknown';
                $user_agent  = \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
                $fingerprint = md5( $ip . $user_agent );

                $transient_key = 'chatkit_ratelimit_' . $fingerprint;
                $requests      = (int) \get_transient( $transient_key );
                $limit         = \current_user_can( 'manage_options' ) ? 100 : 10;

                if ( $requests >= $limit ) {
                        \error_log( sprintf( 'ChatKit rate limit exceeded for IP: %s', $ip ) );

                        return new WP_Error(
                                'rate_limit_exceeded',
                                \__( 'Too many requests. Please try again in a minute.', 'chatkit-wp' ),
                                [ 'status' => 429 ]
                        );
                }

                \set_transient( $transient_key, $requests + 1, 60 );

                $api_key     = $this->options->get_api_key();
                $workflow_id = $this->options->get_workflow_id();

                if ( empty( $api_key ) || empty( $workflow_id ) ) {
                        return new WP_Error(
                                'missing_config',
                                \__( 'Plugin not configured. Contact administrator.', 'chatkit-wp' ),
                                [ 'status' => 500 ]
                        );
                }

                $session_id = $this->options->get_or_create_user_id();
		$config     = $this->options->get_all();

		$payload = [
			'workflow' => [ 'id' => $workflow_id ],
			'user'     => $session_id,
		];

                $response = \wp_remote_post(
                        'https://api.openai.com/v1/chatkit/sessions',
                        [
                                'headers' => [
                                        'Authorization' => 'Bearer ' . $api_key,
                                        'Content-Type'  => 'application/json',
                                        'OpenAI-Beta'   => 'chatkit_beta=v1',
                                ],
                                'body'      => \wp_json_encode( $payload ),
                                'timeout'   => 10,
                                'sslverify' => true,
                        ]
                );

                if ( \is_wp_error( $response ) ) {
                        return new WP_Error( 'request_failed', $response->get_error_message(), [ 'status' => 502 ] );
                }

		$status_code = (int) \wp_remote_retrieve_response_code( $response );
		$body_raw    = \wp_remote_retrieve_body( $response );
		$body        = json_decode( $body_raw, true );

		if ( 200 !== $status_code || ! isset( $body['client_secret'] ) ) {
			$error_message = '';

			if ( is_array( $body ) ) {
				if ( isset( $body['error']['message'] ) ) {
					$error_message = (string) $body['error']['message'];
				} elseif ( isset( $body['message'] ) ) {
					$error_message = (string) $body['message'];
				}
			}

			if ( '' === $error_message ) {
				$error_message = \__( 'Unknown error returned by OpenAI.', 'chatkit-wp' );
			}

			\error_log(
				sprintf(
					'ChatKit session create failed (%d): %s',
					$status_code,
					$error_message
				)
			);

			return new WP_Error(
				'invalid_response',
				sprintf(
					/* translators: %s: Upstream error message returned by OpenAI */
					\__( 'Error creating session: %s', 'chatkit-wp' ),
					$error_message
				),
				[
					'status'         => $status_code,
					'response_body'  => $body_raw,
					'response_error' => is_array( $body ) ? $body : null,
				]
			);
		}

		\error_log(
			sprintf(
				'ChatKit: Session created (attachments %s)',
				$config['enable_attachments'] ? 'enabled ✅' : 'disabled ❌'
			)
		);

                return \rest_ensure_response([
                        'client_secret' => $body['client_secret'],
                ]);
        }

        /**
         * Test the connection to OpenAI.
         *
         * @return array|WP_Error
         */
        public function test_connection() {
                $api_key     = $this->options->get_api_key();
                $workflow_id = $this->options->get_workflow_id();

                if ( empty( $api_key ) ) {
                        return new WP_Error( 'missing_api_key', \__( 'API Key not configured', 'chatkit-wp' ), [ 'status' => 400 ] );
                }

                if ( empty( $workflow_id ) ) {
                        return new WP_Error( 'missing_workflow_id', \__( 'Workflow ID not configured', 'chatkit-wp' ), [ 'status' => 400 ] );
                }

                $response = \wp_remote_post(
                        'https://api.openai.com/v1/chatkit/sessions',
                        [
                                'headers' => [
                                        'Authorization' => 'Bearer ' . $api_key,
                                        'Content-Type'  => 'application/json',
                                        'OpenAI-Beta'   => 'chatkit_beta=v1',
                                ],
                                'body'      => \wp_json_encode(
                                        [
                                                'workflow' => [ 'id' => $workflow_id ],
                                                'user'     => 'test_' . time(),
                                        ]
                                ),
                                'timeout'   => 10,
                                'sslverify' => true,
                        ]
                );

                if ( \is_wp_error( $response ) ) {
                        return new WP_Error( 'connection_failed', $response->get_error_message(), [ 'status' => 502 ] );
                }

                $status_code = (int) \wp_remote_retrieve_response_code( $response );

                if ( 200 === $status_code ) {
                        return \rest_ensure_response([
                                'message' => \__( 'Connection successful! Plugin is correctly configured.', 'chatkit-wp' ),
                        ]);
                }

                $body = \wp_remote_retrieve_body( $response );

                return new WP_Error(
                        'api_error',
                        sprintf( \__( 'API Error (status %d): %s', 'chatkit-wp' ), $status_code, $body ),
                        [ 'status' => $status_code ]
                );
        }
}
