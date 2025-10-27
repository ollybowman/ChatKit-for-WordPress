<?php
/**
 * Options repository for the ChatKit plugin.
 */
declare( strict_types=1 );

namespace ChatkitWp\Settings;

use function array_key_exists;
use function array_map;
use function array_merge;
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
         * Retrieve the grouped option schema and defaults.
         *
         * @return array<string, array<string, array<string, mixed>>>
         */
        private function get_option_schema(): array {
                return [
                        'chatkit_basic_settings'      => [
                                'defaults' => [
                                        'api_key'          => '',
                                        'workflow_id'      => '',
                                        'show_everywhere'  => false,
                                        'exclude_ids'      => '',
                                        'exclude_home'     => false,
                                        'exclude_archive'  => false,
                                        'exclude_search'   => false,
                                        'exclude_404'      => false,
                                        'initial_thread_id'=> '',
                                ],
                        ],
                        'chatkit_appearance_settings' => [
                                'defaults' => [
                                        'accent_color'      => '#FF4500',
                                        'accent_level'      => '2',
                                        'button_text'       => \__( 'Chat now', 'chatkit-wp' ),
                                        'close_text'        => '✕',
                                        'theme_mode'        => 'dark',
                                        'button_size'       => 'medium',
                                        'button_position'   => 'bottom-right',
                                        'border_radius'     => 'round',
                                        'shadow_style'      => 'normal',
                                        'density'           => 'normal',
                                        'locale'            => '',
                                        'enable_custom_font'=> false,
                                        'font_family'       => '',
                                        'font_size'         => '16',
                                        'show_header'       => true,
                                        'show_history'      => true,
                                        'header_title_text' => '',
                                        'header_left_icon'  => '',
                                        'header_left_url'   => '',
                                        'header_right_icon' => '',
                                        'header_right_url'  => '',
                                ],
                        ],
                        'chatkit_messages_settings'   => [
                                'defaults' => [
                                        'greeting_text'           => \__( 'How can I help you today?', 'chatkit-wp' ),
                                        'placeholder_text'        => \__( 'Send a message...', 'chatkit-wp' ),
                                        'default_prompt_1'        => \__( 'How can I assist you?', 'chatkit-wp' ),
                                        'default_prompt_1_text'   => \__( 'Hi! How can I assist you today?', 'chatkit-wp' ),
                                        'default_prompt_1_icon'   => 'circle-question',
                                        'default_prompt_2'        => '',
                                        'default_prompt_2_text'   => '',
                                        'default_prompt_2_icon'   => 'circle-question',
                                        'default_prompt_3'        => '',
                                        'default_prompt_3_text'   => '',
                                        'default_prompt_3_icon'   => 'circle-question',
                                        'default_prompt_4'        => '',
                                        'default_prompt_4_text'   => '',
                                        'default_prompt_4_icon'   => 'circle-question',
                                        'default_prompt_5'        => '',
                                        'default_prompt_5_text'   => '',
                                        'default_prompt_5_icon'   => 'circle-question',
                                ],
                        ],
                        'chatkit_advanced_settings'   => [
                                'defaults' => [
                                        'enable_attachments'      => false,
                                        'attachment_max_size'      => '20',
                                        'attachment_max_count'     => '3',
                                        'persistent_sessions'      => true,
                                        'enable_model_picker'      => false,
                                        'enable_tools'             => false,
                                        'enable_entity_tags'       => false,
                                        'disclaimer_text'          => '',
                                        'disclaimer_high_contrast' => false,
                                ],
                        ],
                ];
        }

        /**
         * Mapping of grouped options to their legacy individual option names.
         *
         * @var array<string, array<string, string>>
         */
        private const LEGACY_OPTION_MAP = [
                'chatkit_basic_settings'      => [
                        'api_key'          => 'chatkit_api_key',
                        'workflow_id'      => 'chatkit_workflow_id',
                        'show_everywhere'  => 'chatkit_show_everywhere',
                        'exclude_ids'      => 'chatkit_exclude_ids',
                        'exclude_home'     => 'chatkit_exclude_home',
                        'exclude_archive'  => 'chatkit_exclude_archive',
                        'exclude_search'   => 'chatkit_exclude_search',
                        'exclude_404'      => 'chatkit_exclude_404',
                        'initial_thread_id'=> 'chatkit_initial_thread_id',
                ],
                'chatkit_appearance_settings' => [
                        'accent_color'      => 'chatkit_accent_color',
                        'accent_level'      => 'chatkit_accent_level',
                        'button_text'       => 'chatkit_button_text',
                        'close_text'        => 'chatkit_close_text',
                        'theme_mode'        => 'chatkit_theme_mode',
                        'button_size'       => 'chatkit_button_size',
                        'button_position'   => 'chatkit_button_position',
                        'border_radius'     => 'chatkit_border_radius',
                        'shadow_style'      => 'chatkit_shadow_style',
                        'density'           => 'chatkit_density',
                        'locale'            => 'chatkit_locale',
                        'enable_custom_font'=> 'chatkit_enable_custom_font',
                        'font_family'       => 'chatkit_font_family',
                        'font_size'         => 'chatkit_font_size',
                        'show_header'       => 'chatkit_show_header',
                        'show_history'      => 'chatkit_show_history',
                        'header_title_text' => 'chatkit_header_title_text',
                        'header_left_icon'  => 'chatkit_header_left_icon',
                        'header_left_url'   => 'chatkit_header_left_url',
                        'header_right_icon' => 'chatkit_header_right_icon',
                        'header_right_url'  => 'chatkit_header_right_url',
                ],
                'chatkit_messages_settings'   => [
                        'greeting_text'         => 'chatkit_greeting_text',
                        'placeholder_text'      => 'chatkit_placeholder_text',
                        'default_prompt_1'      => 'chatkit_default_prompt_1',
                        'default_prompt_1_text' => 'chatkit_default_prompt_1_text',
                        'default_prompt_1_icon' => 'chatkit_default_prompt_1_icon',
                        'default_prompt_2'      => 'chatkit_default_prompt_2',
                        'default_prompt_2_text' => 'chatkit_default_prompt_2_text',
                        'default_prompt_2_icon' => 'chatkit_default_prompt_2_icon',
                        'default_prompt_3'      => 'chatkit_default_prompt_3',
                        'default_prompt_3_text' => 'chatkit_default_prompt_3_text',
                        'default_prompt_3_icon' => 'chatkit_default_prompt_3_icon',
                        'default_prompt_4'      => 'chatkit_default_prompt_4',
                        'default_prompt_4_text' => 'chatkit_default_prompt_4_text',
                        'default_prompt_4_icon' => 'chatkit_default_prompt_4_icon',
                        'default_prompt_5'      => 'chatkit_default_prompt_5',
                        'default_prompt_5_text' => 'chatkit_default_prompt_5_text',
                        'default_prompt_5_icon' => 'chatkit_default_prompt_5_icon',
                ],
                'chatkit_advanced_settings'   => [
                        'enable_attachments'      => 'chatkit_enable_attachments',
                        'attachment_max_size'      => 'chatkit_attachment_max_size',
                        'attachment_max_count'     => 'chatkit_attachment_max_count',
                        'persistent_sessions'      => 'chatkit_persistent_sessions',
                        'enable_model_picker'      => 'chatkit_enable_model_picker',
                        'enable_tools'             => 'chatkit_enable_tools',
                        'enable_entity_tags'       => 'chatkit_enable_entity_tags',
                        'disclaimer_text'          => 'chatkit_disclaimer_text',
                        'disclaimer_high_contrast' => 'chatkit_disclaimer_high_contrast',
                ],
        ];

        /**
         * Cached option groups to reduce database lookups.
         *
         * @var array<string, array<string, mixed>>
         */
        private array $group_cache = [];

        /**
         * Retrieve the registry describing all plugin settings.
         *
         * @return array<string, array<string, mixed>>
         */
        public function get_registry(): array {
                $registry = [];
                $schema   = $this->get_option_schema();

                foreach ( $schema as $option => $definition ) {
                        $registry[ $option ] = [
                                'type'    => 'array',
                                'default' => $definition['defaults'],
                        ];
                }

                return $registry;
        }

        /**
         * Provide a sanitize callback for the given option type.
         */
        public function get_sanitize_callback( string $type ): ?callable {
                if ( in_array( $type, [ 'boolean', 'array' ], true ) ) {
                        return null;
                }

                return 'sanitize_text_field';
        }

        /**
         * Clear the cached options.
         */
        public function clear_cache(): void {
                $this->cache       = null;
                $this->group_cache = [];
        }

        /**
         * Retrieve all option values used on the front-end.
         *
         * @return array<string, mixed>
         */
        public function get_all(): array {
                if ( null === $this->cache ) {
                        $basic      = $this->get_basic_settings();
                        $appearance = $this->get_appearance_settings();
                        $messages   = $this->get_messages_settings();
                        $advanced   = $this->get_advanced_settings();

                        $this->cache = array_merge( $basic, $appearance, $messages, $advanced );

                        $this->cache['api_key']     = $this->get_api_key();
                        $this->cache['workflow_id'] = $this->get_workflow_id();
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

                $basic = $this->get_basic_settings();

                return (string) ( $basic['api_key'] ?? '' );
        }

        /**
         * Retrieve the configured workflow ID.
         */
        public function get_workflow_id(): string {
                if ( \defined( 'CHATKIT_WORKFLOW_ID' ) && ! empty( \CHATKIT_WORKFLOW_ID ) ) {
                        return (string) \CHATKIT_WORKFLOW_ID;
                }

                $basic = $this->get_basic_settings();

                return (string) ( $basic['workflow_id'] ?? '' );
        }

        /**
         * Create or retrieve the user identifier used for chat sessions.
         */
        public function get_or_create_user_id(): string {
                $advanced   = $this->get_advanced_settings();
                $persistent = (bool) ( $advanced['persistent_sessions'] ?? true );

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

        /**
         * Retrieve the "Basic" option group.
         *
         * @return array<string, mixed>
         */
        public function get_basic_settings(): array {
                return $this->get_option_group( 'chatkit_basic_settings' );
        }

        /**
         * Retrieve the "Appearance" option group.
         *
         * @return array<string, mixed>
         */
        public function get_appearance_settings(): array {
                return $this->get_option_group( 'chatkit_appearance_settings' );
        }

        /**
         * Retrieve the "Messages" option group.
         *
         * @return array<string, mixed>
         */
        public function get_messages_settings(): array {
                return $this->get_option_group( 'chatkit_messages_settings' );
        }

        /**
         * Retrieve the "Advanced" option group.
         *
         * @return array<string, mixed>
         */
        public function get_advanced_settings(): array {
                return $this->get_option_group( 'chatkit_advanced_settings' );
        }

        /**
         * Whether the widget is configured to appear on every page.
         */
        public function should_show_everywhere(): bool {
                $basic = $this->get_basic_settings();

                return ! empty( $basic['show_everywhere'] );
        }

        /**
         * Fetch a grouped option set with defaults and legacy fallbacks.
         *
         * @return array<string, mixed>
         */
        private function get_option_group( string $option ): array {
                if ( isset( $this->group_cache[ $option ] ) ) {
                        return $this->group_cache[ $option ];
                }

                $schema   = $this->get_option_schema();
                $defaults = $schema[ $option ]['defaults'] ?? [];
                $stored   = \get_option( $option, [] );

                if ( ! is_array( $stored ) ) {
                        $stored = [];
                }

                $values = array_merge( $defaults, $stored );
                $values = $this->merge_legacy_values( $option, $values, $defaults );

                $this->group_cache[ $option ] = $values;

                return $values;
        }

        /**
         * Merge legacy single options into the grouped configuration for backwards compatibility.
         *
         * @param array<string, mixed> $values
         * @param array<string, mixed> $defaults
         *
         * @return array<string, mixed>
         */
        private function merge_legacy_values( string $option, array $values, array $defaults ): array {
                if ( ! isset( self::LEGACY_OPTION_MAP[ $option ] ) ) {
                        return $values;
                }

                foreach ( self::LEGACY_OPTION_MAP[ $option ] as $key => $legacy_name ) {
                        if ( ! array_key_exists( $key, $values ) ) {
                                continue;
                        }

                        $default = $defaults[ $key ] ?? null;
                        $current = $values[ $key ];

                        if ( $current !== $default ) {
                                continue;
                        }

                        $legacy_value = \get_option( $legacy_name, $default );

                        if ( $legacy_value === $default ) {
                                continue;
                        }

                        $values[ $key ] = $this->cast_value( $legacy_value, $default );
                }

                return $values;
        }

        /**
         * Cast a legacy option value to match its grouped default type.
         */
        private function cast_value( mixed $value, mixed $default ): mixed {
                if ( is_bool( $default ) ) {
                        return (bool) $value;
                }

                return is_string( $default ) ? (string) $value : $value;
        }
}
