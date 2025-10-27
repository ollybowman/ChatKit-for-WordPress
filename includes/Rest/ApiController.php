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
                        'workflow'              => [ 'id' => $workflow_id ],
                        'user'                  => $session_id,
                        'chatkit_configuration' => [
                                'appearance'    => [
                                        'accent_color' => $config['accent_color'],
                                        'accent_level' => (int) $config['accent_level'],
                                        'mode'         => $config['theme_mode'],
                                        'locale'       => $config['locale'],
                                ],
                                'interactions'  => [
                                        'default_prompts' => $this->options->build_default_prompts( $config ),
                                        'greeting'         => $config['greeting_text'],
                                        'placeholder'      => $config['placeholder_text'],
                                ],
                                'file_upload'   => [
                                        'enabled'     => (bool) $config['enable_attachments'],
                                        'max_file_mb' => (int) $config['attachment_max_size'],
                                        'max_files'   => (int) $config['attachment_max_count'],
                                ],
                                'preferences'   => [
                                        'persist_session' => (bool) $config['persistent_sessions'],
                                        'initial_thread'  => $config['initial_thread_id'],
                                ],
                                'header'        => [
                                        'visible'    => (bool) $config['show_header'],
                                        'title'      => $config['header_title_text'],
                                        'left_icon'  => $config['header_left_icon'],
                                        'left_url'   => $config['header_left_url'],
                                        'right_icon' => $config['header_right_icon'],
                                        'right_url'  => $config['header_right_url'],
                                ],
                                'history'       => [ 'enabled' => (bool) $config['show_history'] ],
                                'layout'        => [
                                        'density'       => $config['density'],
                                        'border_radius' => $config['border_radius'],
                                        'shadow'        => $config['shadow_style'],
                                ],
                                'disclaimer'    => [
                                        'content'       => $config['disclaimer_text'],
                                        'high_contrast' => (bool) $config['disclaimer_high_contrast'],
                                ],
                                'custom_font'   => $config['enable_custom_font'] && ! empty( $config['font_family'] )
                                        ? [
                                                'family'    => $config['font_family'],
                                                'base_size' => (int) $config['font_size'],
                                        ]
                                        : null,
                                'feature_flags' => [
                                        'entity_tags'  => (bool) $config['enable_entity_tags'],
                                        'model_picker' => (bool) $config['enable_model_picker'],
                                        'toolbox'      => (bool) $config['enable_tools'],
                                ],
                        ],
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
                $body        = json_decode( \wp_remote_retrieve_body( $response ), true );

                if ( 200 !== $status_code || ! isset( $body['client_secret'] ) ) {
                        return new WP_Error(
                                'invalid_response',
                                \__( 'Error creating session', 'chatkit-wp' ),
                                [ 'status' => $status_code ]
                        );
                }

                if ( ! empty( $body['chatkit_configuration']['file_upload']['enabled'] ) ) {
                        \error_log( 'ChatKit: Session created with file upload enabled ✅' );
                } else {
                        \error_log( 'ChatKit: Session created WITHOUT file upload ❌' );
                }

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
