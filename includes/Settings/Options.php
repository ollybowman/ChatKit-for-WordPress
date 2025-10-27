<?php
/**
 * Options repository for the ChatKit plugin.
 */
declare( strict_types=1 );

namespace ChatkitWp\Settings;

use function array_map;
use function explode;
use function headers_sent;
use function in_array;
use function is_array;
use function json_decode;
use function md5;
use function preg_match;
use function setcookie;
use function sprintf;
use function time;
use function uniqid;
use function wp_generate_password;
use function wp_json_encode;
use function wp_rand;

/**
 * Provides access to plugin option values and helper utilities.
 */
final class Options {
        /**
         * Cached option values used across the plugin.
         *
         * @var array<string, mixed>|null
         */
        private ?array $cache = null;

        /**
         * Retrieve the registry describing all plugin settings.
         *
         * @return array<string, array<string, mixed>>
         */
        public function get_registry(): array {
                return [
                        'chatkit_api_key'                => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_workflow_id'            => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_accent_color'           => [ 'type' => 'string', 'default' => '#FF4500' ],
                        'chatkit_accent_level'           => [ 'type' => 'string', 'default' => '2' ],
                        'chatkit_button_text'            => [ 'type' => 'string', 'default' => \__( 'Chat now', 'chatkit-wp' ) ],
                        'chatkit_close_text'             => [ 'type' => 'string', 'default' => '✕' ],
                        'chatkit_theme_mode'             => [ 'type' => 'string', 'default' => 'dark' ],
                        'chatkit_enable_attachments'     => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_persistent_sessions'    => [ 'type' => 'boolean', 'default' => true ],
                        'chatkit_show_everywhere'        => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_greeting_text'          => [ 'type' => 'string', 'default' => \__( 'How can I help you today?', 'chatkit-wp' ) ],
                        'chatkit_placeholder_text'       => [ 'type' => 'string', 'default' => \__( 'Send a message...', 'chatkit-wp' ) ],
                        'chatkit_button_size'            => [ 'type' => 'string', 'default' => 'medium' ],
                        'chatkit_button_position'        => [ 'type' => 'string', 'default' => 'bottom-right' ],
                        'chatkit_border_radius'          => [ 'type' => 'string', 'default' => 'round' ],
                        'chatkit_shadow_style'           => [ 'type' => 'string', 'default' => 'normal' ],
                        'chatkit_density'                => [ 'type' => 'string', 'default' => 'normal' ],
                        'chatkit_locale'                 => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_1'       => [ 'type' => 'string', 'default' => \__( 'How can I assist you?', 'chatkit-wp' ) ],
                        'chatkit_default_prompt_1_text'  => [ 'type' => 'string', 'default' => \__( 'Hi! How can I assist you today?', 'chatkit-wp' ) ],
                        'chatkit_default_prompt_1_icon'  => [ 'type' => 'string', 'default' => 'circle-question' ],
                        'chatkit_default_prompt_2'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_2_text'  => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_2_icon'  => [ 'type' => 'string', 'default' => 'circle-question' ],
                        'chatkit_default_prompt_3'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_3_text'  => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_3_icon'  => [ 'type' => 'string', 'default' => 'circle-question' ],
                        'chatkit_default_prompt_4'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_4_text'  => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_4_icon'  => [ 'type' => 'string', 'default' => 'circle-question' ],
                        'chatkit_default_prompt_5'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_5_text'  => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_default_prompt_5_icon'  => [ 'type' => 'string', 'default' => 'circle-question' ],
                        'chatkit_exclude_ids'            => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_exclude_home'           => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_exclude_archive'        => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_exclude_search'         => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_exclude_404'            => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_attachment_max_size'    => [ 'type' => 'string', 'default' => '20' ],
                        'chatkit_attachment_max_count'   => [ 'type' => 'string', 'default' => '3' ],
                        'chatkit_enable_model_picker'    => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_enable_tools'           => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_enable_entity_tags'     => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_enable_custom_font'     => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_font_family'            => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_font_size'              => [ 'type' => 'string', 'default' => '16' ],
                        'chatkit_show_header'            => [ 'type' => 'boolean', 'default' => true ],
                        'chatkit_show_history'           => [ 'type' => 'boolean', 'default' => true ],
                        'chatkit_header_title_text'      => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_header_left_icon'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_header_left_url'        => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_header_right_icon'      => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_header_right_url'       => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_disclaimer_text'        => [ 'type' => 'string', 'default' => '' ],
                        'chatkit_disclaimer_high_contrast' => [ 'type' => 'boolean', 'default' => false ],
                        'chatkit_initial_thread_id'      => [ 'type' => 'string', 'default' => '' ],
                ];
        }

        /**
         * Provide a sanitize callback for the given option type.
         */
        public function get_sanitize_callback( string $type ): ?callable {
                if ( 'boolean' === $type ) {
                        return null;
                }

                return 'sanitize_text_field';
        }

        /**
         * Clear the cached options.
         */
        public function clear_cache(): void {
                $this->cache = null;
        }

        /**
         * Retrieve all option values used on the front-end.
         *
         * @return array<string, mixed>
         */
        public function get_all(): array {
                if ( null === $this->cache ) {
                        $this->cache = [
                                'api_key'                 => $this->get_api_key(),
                                'workflow_id'             => $this->get_workflow_id(),
                                'accent_color'            => (string) \get_option( 'chatkit_accent_color', '#FF4500' ),
                                'accent_level'            => (string) \get_option( 'chatkit_accent_level', '2' ),
                                'button_text'             => (string) \get_option( 'chatkit_button_text', \__( 'Chat now', 'chatkit-wp' ) ),
                                'close_text'              => (string) \get_option( 'chatkit_close_text', '✕' ),
                                'theme_mode'              => (string) \get_option( 'chatkit_theme_mode', 'dark' ),
                                'enable_attachments'      => (bool) \get_option( 'chatkit_enable_attachments', false ),
                                'persistent_sessions'     => (bool) \get_option( 'chatkit_persistent_sessions', true ),
                                'show_everywhere'         => (bool) \get_option( 'chatkit_show_everywhere', false ),
                                'greeting_text'           => (string) \get_option( 'chatkit_greeting_text', \__( 'How can I help you today?', 'chatkit-wp' ) ),
                                'placeholder_text'        => (string) \get_option( 'chatkit_placeholder_text', \__( 'Send a message...', 'chatkit-wp' ) ),
                                'button_size'             => (string) \get_option( 'chatkit_button_size', 'medium' ),
                                'button_position'         => (string) \get_option( 'chatkit_button_position', 'bottom-right' ),
                                'border_radius'           => (string) \get_option( 'chatkit_border_radius', 'round' ),
                                'shadow_style'            => (string) \get_option( 'chatkit_shadow_style', 'normal' ),
                                'density'                 => (string) \get_option( 'chatkit_density', 'normal' ),
                                'locale'                  => (string) \get_option( 'chatkit_locale', '' ),
                                'default_prompt_1'        => (string) \get_option( 'chatkit_default_prompt_1', \__( 'How can I assist you?', 'chatkit-wp' ) ),
                                'default_prompt_1_text'   => (string) \get_option( 'chatkit_default_prompt_1_text', \__( 'Hi! How can I assist you today?', 'chatkit-wp' ) ),
                                'default_prompt_1_icon'   => (string) \get_option( 'chatkit_default_prompt_1_icon', 'circle-question' ),
                                'default_prompt_2'        => (string) \get_option( 'chatkit_default_prompt_2', '' ),
                                'default_prompt_2_text'   => (string) \get_option( 'chatkit_default_prompt_2_text', '' ),
                                'default_prompt_2_icon'   => (string) \get_option( 'chatkit_default_prompt_2_icon', 'circle-question' ),
                                'default_prompt_3'        => (string) \get_option( 'chatkit_default_prompt_3', '' ),
                                'default_prompt_3_text'   => (string) \get_option( 'chatkit_default_prompt_3_text', '' ),
                                'default_prompt_3_icon'   => (string) \get_option( 'chatkit_default_prompt_3_icon', 'circle-question' ),
                                'default_prompt_4'        => (string) \get_option( 'chatkit_default_prompt_4', '' ),
                                'default_prompt_4_text'   => (string) \get_option( 'chatkit_default_prompt_4_text', '' ),
                                'default_prompt_4_icon'   => (string) \get_option( 'chatkit_default_prompt_4_icon', 'circle-question' ),
                                'default_prompt_5'        => (string) \get_option( 'chatkit_default_prompt_5', '' ),
                                'default_prompt_5_text'   => (string) \get_option( 'chatkit_default_prompt_5_text', '' ),
                                'default_prompt_5_icon'   => (string) \get_option( 'chatkit_default_prompt_5_icon', 'circle-question' ),
                                'attachment_max_size'     => (string) \get_option( 'chatkit_attachment_max_size', '20' ),
                                'attachment_max_count'    => (string) \get_option( 'chatkit_attachment_max_count', '3' ),
                                'enable_model_picker'     => (bool) \get_option( 'chatkit_enable_model_picker', false ),
                                'enable_tools'            => (bool) \get_option( 'chatkit_enable_tools', false ),
                                'enable_entity_tags'      => (bool) \get_option( 'chatkit_enable_entity_tags', false ),
                                'enable_custom_font'      => (bool) \get_option( 'chatkit_enable_custom_font', false ),
                                'font_family'             => (string) \get_option( 'chatkit_font_family', '' ),
                                'font_size'               => (string) \get_option( 'chatkit_font_size', '16' ),
                                'show_header'             => (bool) \get_option( 'chatkit_show_header', true ),
                                'show_history'            => (bool) \get_option( 'chatkit_show_history', true ),
                                'header_title_text'       => (string) \get_option( 'chatkit_header_title_text', '' ),
                                'header_left_icon'        => (string) \get_option( 'chatkit_header_left_icon', '' ),
                                'header_left_url'         => (string) \get_option( 'chatkit_header_left_url', '' ),
                                'header_right_icon'       => (string) \get_option( 'chatkit_header_right_icon', '' ),
                                'header_right_url'        => (string) \get_option( 'chatkit_header_right_url', '' ),
                                'disclaimer_text'         => (string) \get_option( 'chatkit_disclaimer_text', '' ),
                                'disclaimer_high_contrast' => (bool) \get_option( 'chatkit_disclaimer_high_contrast', false ),
                                'initial_thread_id'       => (string) \get_option( 'chatkit_initial_thread_id', '' ),
                        ];
                }

                return $this->cache;
        }

        /**
         * Retrieve the configured API key.
         */
        public function get_api_key(): string {
                if ( \defined( 'CHATKIT_OPENAI_API_KEY' ) && ! empty( \CHATKIT_OPENAI_API_KEY ) ) {
                        return (string) \CHATKIT_OPENAI_API_KEY;
                }

                return (string) \get_option( 'chatkit_api_key', '' );
        }

        /**
         * Retrieve the configured workflow ID.
         */
        public function get_workflow_id(): string {
                if ( \defined( 'CHATKIT_WORKFLOW_ID' ) && ! empty( \CHATKIT_WORKFLOW_ID ) ) {
                        return (string) \CHATKIT_WORKFLOW_ID;
                }

                return (string) \get_option( 'chatkit_workflow_id', '' );
        }

        /**
         * Create or retrieve the user identifier used for chat sessions.
         */
        public function get_or_create_user_id(): string {
                $persistent = (bool) \get_option( 'chatkit_persistent_sessions', true );

                if ( ! $persistent ) {
                        return 'guest_' . wp_generate_password( 12, false );
                }

                $cookie_name = 'chatkit_user_id';

                if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
                        $user_id = \sanitize_text_field( \wp_unslash( $_COOKIE[ $cookie_name ] ) );

                        if ( preg_match( '/^user_[a-f0-9]{32}$/', $user_id ) ) {
                                return $user_id;
                        }
                }

                $user_id = 'user_' . md5( uniqid( 'chatkit_', true ) . wp_rand() );

                if ( ! headers_sent() ) {
                        $this->set_user_cookie( $cookie_name, $user_id );
                }

                return $user_id;
        }

        /**
         * Persist the user identifier in a cookie.
         */
        private function set_user_cookie( string $name, string $value ): void {
                $expire = time() + ( DAY_IN_SECONDS * 30 );

                if ( PHP_VERSION_ID >= 70300 ) {
                        setcookie(
                                $name,
                                $value,
                                [
                                        'expires'  => $expire,
                                        'path'     => COOKIEPATH,
                                        'domain'   => COOKIE_DOMAIN,
                                        'secure'   => \is_ssl(),
                                        'httponly' => true,
                                        'samesite' => 'Strict',
                                ]
                        );
                } else {
                        setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, \is_ssl(), true );
                }
        }

        /**
         * Build the default prompts configuration array.
         *
         * @param array<string, mixed> $config Option cache from {@see Options::get_all()}.
         *
         * @return array<int, array<string, string>>
         */
        public function build_default_prompts( array $config ): array {
                $prompts = [];

                for ( $i = 1; $i <= 5; $i++ ) {
                        $label = $config[ "default_prompt_{$i}" ] ?? '';
                        $text  = $config[ "default_prompt_{$i}_text" ] ?? '';
                        $icon  = $config[ "default_prompt_{$i}_icon" ] ?? '';

                        if ( empty( $label ) || empty( $text ) ) {
                                continue;
                        }

                        $prompts[] = [
                                'label' => $label,
                                'text'  => $text,
                                'icon'  => $icon,
                        ];
                }

                return $prompts;
        }
}
