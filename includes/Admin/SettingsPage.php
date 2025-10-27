<?php
/**
 * Admin settings controller for the ChatKit plugin.
 */
declare( strict_types=1 );

namespace ChatkitWp\Admin;

use ChatkitWp\Settings\Options;

/**
 * Handles registration and rendering of the plugin settings page.
 */
final class SettingsPage {
        private ?string $page_hook = null;

        public function __construct( private Options $options ) {}

        /**
         * Register the settings page in the WordPress admin.
         */
        public function add_menu(): void {
                $this->page_hook = \add_menu_page(
                        \__( 'ChatKit Studio', 'chatkit-wp' ),
                        \__( 'ChatKit', 'chatkit-wp' ),
                        'manage_options',
                        'chatkit-console',
                        [ $this, 'render' ],
                        'dashicons-format-chat',
                        56
                );
        }

        /**
         * Register all plugin settings with WordPress.
         */
        public function register_settings(): void {
                foreach ( $this->options->get_registry() as $option => $schema ) {
                        \register_setting(
                                'chatkit_wp_settings',
                                $option,
                                [
                                        'type'              => $schema['type'],
                                        'sanitize_callback' => $this->options->get_sanitize_callback( $schema['type'] ),
                                        'default'           => $schema['default'],
                                ]
                        );
                }
        }

        /**
         * Enqueue admin assets when viewing the ChatKit console.
         */
        public function enqueue_assets( string $hook ): void {
                if ( empty( $this->page_hook ) || $hook !== $this->page_hook ) {
                        return;
                }

                \wp_enqueue_style(
                        'chatkit-admin',
                        CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-admin.css',
                        [],
                        CHATKIT_WP_VERSION
                );

                \wp_enqueue_script(
                        'chatkit-admin',
                        CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-admin.js',
                        [],
                        CHATKIT_WP_VERSION,
                        true
                );

                \wp_localize_script(
                        'chatkit-admin',
                        'chatkitAdminConfig',
                        [
                                'testEndpoint' => \rest_url( 'chatkit/v1/test' ),
                                'restNonce'    => \wp_create_nonce( 'wp_rest' ),
                                'strings'      => [
                                        'testing' => \__( 'Testing…', 'chatkit-wp' ),
                                        'success' => \__( 'Connection successful! Plugin is correctly configured.', 'chatkit-wp' ),
                                        'error'   => \__( 'Something went wrong. Please review your credentials.', 'chatkit-wp' ),
                                ],
                        ]
                );
        }

        /**
         * Render the plugin settings page.
         */
        public function render(): void {
                if ( ! \current_user_can( 'manage_options' ) ) {
                        return;
                }

                if ( isset( $_POST['chatkit_save_settings'] ) ) {
                        $this->handle_form_submission();
                }

                \settings_errors( 'chatkit_wp_settings' );

                $options = $this->options->get_all();
                extract( $options, EXTR_SKIP );

                require CHATKIT_WP_PLUGIN_DIR . 'admin/settings-page.php';
        }

        /**
         * Persist submitted option values when the settings form is posted.
         */
        private function handle_form_submission(): void {
                \check_admin_referer( 'chatkit_settings_save' );

                $basic_settings      = $this->options->get_basic_settings();
                $appearance_settings = $this->options->get_appearance_settings();
                $messages_settings   = $this->options->get_messages_settings();
                $advanced_settings   = $this->options->get_advanced_settings();

                if ( isset( $_POST['chatkit_api_key'] ) ) {
                        $basic_settings['api_key'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_api_key'] ) );
                }

                if ( isset( $_POST['chatkit_workflow_id'] ) ) {
                        $basic_settings['workflow_id'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_workflow_id'] ) );
                }

                $basic_settings['show_everywhere'] = isset( $_POST['chatkit_show_everywhere'] );
                $basic_settings['exclude_home']    = isset( $_POST['chatkit_exclude_home'] );
                $basic_settings['exclude_archive'] = isset( $_POST['chatkit_exclude_archive'] );
                $basic_settings['exclude_search']  = isset( $_POST['chatkit_exclude_search'] );
                $basic_settings['exclude_404']     = isset( $_POST['chatkit_exclude_404'] );

                if ( isset( $_POST['chatkit_exclude_ids'] ) ) {
                        $basic_settings['exclude_ids'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_exclude_ids'] ) );
                }

                if ( isset( $_POST['chatkit_initial_thread_id'] ) ) {
                        $basic_settings['initial_thread_id'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_initial_thread_id'] ) );
                }

                if ( isset( $_POST['chatkit_button_text'] ) ) {
                        $appearance_settings['button_text'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_button_text'] ) );
                }

                if ( isset( $_POST['chatkit_close_text'] ) ) {
                        $appearance_settings['close_text'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_close_text'] ) );
                }

                if ( isset( $_POST['chatkit_theme_mode'] ) ) {
                        $appearance_settings['theme_mode'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_theme_mode'] ) );
                }

                if ( isset( $_POST['chatkit_button_size'] ) ) {
                        $appearance_settings['button_size'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_button_size'] ) );
                }

                if ( isset( $_POST['chatkit_button_position'] ) ) {
                        $appearance_settings['button_position'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_button_position'] ) );
                }

                if ( isset( $_POST['chatkit_border_radius'] ) ) {
                        $appearance_settings['border_radius'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_border_radius'] ) );
                }

                if ( isset( $_POST['chatkit_shadow_style'] ) ) {
                        $appearance_settings['shadow_style'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_shadow_style'] ) );
                }

                if ( isset( $_POST['chatkit_density'] ) ) {
                        $appearance_settings['density'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_density'] ) );
                }

                if ( isset( $_POST['chatkit_locale'] ) ) {
                        $appearance_settings['locale'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_locale'] ) );
                }

                if ( isset( $_POST['chatkit_accent_level'] ) ) {
                        $appearance_settings['accent_level'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_accent_level'] ) );
                }

                if ( isset( $_POST['chatkit_accent_color'] ) ) {
                        $color = \sanitize_hex_color( \wp_unslash( $_POST['chatkit_accent_color'] ) );
                        $appearance_settings['accent_color'] = $color ?: (string) $appearance_settings['accent_color'];
                }

                $appearance_settings['enable_custom_font'] = isset( $_POST['chatkit_enable_custom_font'] );

                if ( isset( $_POST['chatkit_font_family'] ) ) {
                        $appearance_settings['font_family'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_font_family'] ) );
                }

                if ( isset( $_POST['chatkit_font_size'] ) ) {
                        $font_size = (int) \absint( $_POST['chatkit_font_size'] );
                        $font_size = max( 12, min( 24, $font_size ) );
                        $appearance_settings['font_size'] = (string) $font_size;
                }

                $appearance_settings['show_header']  = isset( $_POST['chatkit_show_header'] );
                $appearance_settings['show_history'] = isset( $_POST['chatkit_show_history'] );

                if ( isset( $_POST['chatkit_header_title_text'] ) ) {
                        $appearance_settings['header_title_text'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_header_title_text'] ) );
                }

                if ( isset( $_POST['chatkit_header_left_icon'] ) ) {
                        $appearance_settings['header_left_icon'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_header_left_icon'] ) );
                }

                if ( isset( $_POST['chatkit_header_left_url'] ) ) {
                        $appearance_settings['header_left_url'] = \esc_url_raw( \wp_unslash( $_POST['chatkit_header_left_url'] ) );
                }

                if ( isset( $_POST['chatkit_header_right_icon'] ) ) {
                        $appearance_settings['header_right_icon'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_header_right_icon'] ) );
                }

                if ( isset( $_POST['chatkit_header_right_url'] ) ) {
                        $appearance_settings['header_right_url'] = \esc_url_raw( \wp_unslash( $_POST['chatkit_header_right_url'] ) );
                }

                if ( isset( $_POST['chatkit_greeting_text'] ) ) {
                        $messages_settings['greeting_text'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_greeting_text'] ) );
                }

                if ( isset( $_POST['chatkit_placeholder_text'] ) ) {
                        $messages_settings['placeholder_text'] = \sanitize_text_field( \wp_unslash( $_POST['chatkit_placeholder_text'] ) );
                }

                for ( $i = 1; $i <= 5; $i++ ) {
                        $label_key = "chatkit_default_prompt_{$i}";
                        $text_key  = "chatkit_default_prompt_{$i}_text";
                        $icon_key  = "chatkit_default_prompt_{$i}_icon";

                        if ( isset( $_POST[ $label_key ] ) ) {
                                $messages_settings[ "default_prompt_{$i}" ] = \sanitize_text_field( \wp_unslash( $_POST[ $label_key ] ) );
                        }

                        if ( isset( $_POST[ $text_key ] ) ) {
                                $messages_settings[ "default_prompt_{$i}_text" ] = \sanitize_text_field( \wp_unslash( $_POST[ $text_key ] ) );
                        }

                        if ( isset( $_POST[ $icon_key ] ) ) {
                                $messages_settings[ "default_prompt_{$i}_icon" ] = \sanitize_text_field( \wp_unslash( $_POST[ $icon_key ] ) );
                        }
                }

                $advanced_settings['enable_attachments'] = isset( $_POST['chatkit_enable_attachments'] );

                if ( isset( $_POST['chatkit_attachment_max_size'] ) ) {
                        $max_size = (int) \absint( $_POST['chatkit_attachment_max_size'] );
                        $max_size = max( 1, min( 100, $max_size ) );
                        $advanced_settings['attachment_max_size'] = (string) $max_size;
                }

                if ( isset( $_POST['chatkit_attachment_max_count'] ) ) {
                        $max_count = (int) \absint( $_POST['chatkit_attachment_max_count'] );
                        $max_count = max( 1, min( 10, $max_count ) );
                        $advanced_settings['attachment_max_count'] = (string) $max_count;
                }

                $advanced_settings['persistent_sessions'] = isset( $_POST['chatkit_persistent_sessions'] );
                $advanced_settings['enable_model_picker'] = isset( $_POST['chatkit_enable_model_picker'] );
                $advanced_settings['enable_tools']        = isset( $_POST['chatkit_enable_tools'] );
                $advanced_settings['enable_entity_tags']  = isset( $_POST['chatkit_enable_entity_tags'] );
                $advanced_settings['disclaimer_high_contrast'] = isset( $_POST['chatkit_disclaimer_high_contrast'] );

                if ( isset( $_POST['chatkit_disclaimer_text'] ) ) {
                        $advanced_settings['disclaimer_text'] = \sanitize_textarea_field( \wp_unslash( $_POST['chatkit_disclaimer_text'] ) );
                }

                \update_option( 'chatkit_basic_settings', $basic_settings );
                \update_option( 'chatkit_appearance_settings', $appearance_settings );
                \update_option( 'chatkit_messages_settings', $messages_settings );
                \update_option( 'chatkit_advanced_settings', $advanced_settings );

                $this->options->clear_cache();

                \add_settings_error(
                        'chatkit_wp_settings',
                        'chatkit_wp_settings_saved',
                        \esc_html__( 'Settings saved successfully!', 'chatkit-wp' ),
                        'updated'
                );
        }
}
