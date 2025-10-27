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
        public function __construct( private Options $options ) {}

        /**
         * Register the settings page in the WordPress admin.
         */
        public function add_menu(): void {
                \add_options_page(
                        \__( 'ChatKit Settings', 'chatkit-wp' ),
                        \__( 'ChatKit', 'chatkit-wp' ),
                        'manage_options',
                        'chatkit-settings',
                        [ $this, 'render' ]
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

                $text_fields = [
                        'chatkit_api_key',
                        'chatkit_workflow_id',
                        'chatkit_button_text',
                        'chatkit_close_text',
                        'chatkit_theme_mode',
                        'chatkit_greeting_text',
                        'chatkit_placeholder_text',
                        'chatkit_button_size',
                        'chatkit_button_position',
                        'chatkit_border_radius',
                        'chatkit_shadow_style',
                        'chatkit_density',
                        'chatkit_locale',
                        'chatkit_exclude_ids',
                        'chatkit_accent_level',
                        'chatkit_attachment_max_size',
                        'chatkit_attachment_max_count',
                        'chatkit_font_family',
                        'chatkit_font_size',
                        'chatkit_header_title_text',
                        'chatkit_header_left_icon',
                        'chatkit_header_left_url',
                        'chatkit_header_right_icon',
                        'chatkit_header_right_url',
                        'chatkit_initial_thread_id',
                        'chatkit_default_prompt_1',
                        'chatkit_default_prompt_1_text',
                        'chatkit_default_prompt_1_icon',
                        'chatkit_default_prompt_2',
                        'chatkit_default_prompt_2_text',
                        'chatkit_default_prompt_2_icon',
                        'chatkit_default_prompt_3',
                        'chatkit_default_prompt_3_text',
                        'chatkit_default_prompt_3_icon',
                        'chatkit_default_prompt_4',
                        'chatkit_default_prompt_4_text',
                        'chatkit_default_prompt_4_icon',
                        'chatkit_default_prompt_5',
                        'chatkit_default_prompt_5_text',
                        'chatkit_default_prompt_5_icon',
                ];

                foreach ( $text_fields as $field ) {
                        if ( isset( $_POST[ $field ] ) ) {
                                \update_option( $field, \sanitize_text_field( \wp_unslash( $_POST[ $field ] ) ) );
                        }
                }

                if ( isset( $_POST['chatkit_disclaimer_text'] ) ) {
                        \update_option( 'chatkit_disclaimer_text', \sanitize_textarea_field( \wp_unslash( $_POST['chatkit_disclaimer_text'] ) ) );
                }

                if ( isset( $_POST['chatkit_accent_color'] ) ) {
                        \update_option( 'chatkit_accent_color', \sanitize_hex_color( \wp_unslash( $_POST['chatkit_accent_color'] ) ) );
                }

                $boolean_fields = [
                        'chatkit_enable_attachments',
                        'chatkit_persistent_sessions',
                        'chatkit_show_everywhere',
                        'chatkit_exclude_home',
                        'chatkit_exclude_archive',
                        'chatkit_exclude_search',
                        'chatkit_exclude_404',
                        'chatkit_enable_model_picker',
                        'chatkit_enable_tools',
                        'chatkit_enable_entity_tags',
                        'chatkit_enable_custom_font',
                        'chatkit_show_header',
                        'chatkit_show_history',
                        'chatkit_disclaimer_high_contrast',
                ];

                foreach ( $boolean_fields as $field ) {
                        \update_option( $field, isset( $_POST[ $field ] ) );
                }

                $this->options->clear_cache();

                \add_settings_error(
                        'chatkit_wp_settings',
                        'chatkit_wp_settings_saved',
                        \esc_html__( 'Settings saved successfully!', 'chatkit-wp' ),
                        'updated'
                );
        }
}
