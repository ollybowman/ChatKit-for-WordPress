<?php
/**
 * Front-end widget controller for the ChatKit plugin.
 */
declare( strict_types=1 );

namespace ChatkitWp\Frontend;

use ChatkitWp\Settings\Options;

/**
 * Manages asset loading and widget rendering on the front-end.
 */
final class Widget {
        private bool $widget_loaded = false;

        public function __construct( private Options $options ) {}

        /**
         * Conditionally enqueue the front-end assets used by the widget.
         */
        public function enqueue_assets(): void {
                $show_everywhere = $this->options->should_show_everywhere();

                global $post;

                $should_load = false;

                if ( $show_everywhere && $this->should_show_widget() ) {
                        $should_load = true;
                } elseif ( $post && ( \has_shortcode( (string) $post->post_content, 'openai_chatkit' ) || \has_shortcode( (string) $post->post_content, 'chatkit' ) ) ) {
                        $should_load = true;
                }

                if ( ! $should_load ) {
                        return;
                }

                \wp_enqueue_script(
                        'chatkit-embed',
                        CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-embed.js',
                        [],
                        CHATKIT_WP_VERSION,
                        true
                );

                \wp_enqueue_style(
                        'chatkit-embed',
                        CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-embed.css',
                        [],
                        CHATKIT_WP_VERSION
                );

                $options        = $this->options->get_all();
                $prompts_config = $this->options->build_default_prompts( $options );

                \wp_localize_script(
                        'chatkit-embed',
                        'chatkitConfig',
                        [
                                'restUrl'                => \rest_url( 'chatkit/v1/session' ),
                                'accentColor'            => $options['accent_color'],
                                'accentLevel'            => (int) $options['accent_level'],
                                'themeMode'              => $options['theme_mode'],
                                'enableAttachments'      => (bool) $options['enable_attachments'],
                                'attachmentMaxSize'      => (int) $options['attachment_max_size'],
                                'attachmentMaxCount'     => (int) $options['attachment_max_count'],
                                'buttonText'             => $options['button_text'],
                                'closeText'              => $options['close_text'],
                                'greetingText'           => $options['greeting_text'],
                                'placeholderText'        => $options['placeholder_text'],
                                'density'                => $options['density'],
                                'borderRadius'           => $options['border_radius'],
                                'locale'                 => $options['locale'],
                                'prompts'                => $prompts_config,
                                'customFont'             => $options['enable_custom_font'] && ! empty( $options['font_family'] )
                                        ? [
                                                'fontFamily' => $options['font_family'],
                                                'baseSize'   => (int) $options['font_size'],
                                        ]
                                        : null,
                                'showHeader'             => (bool) $options['show_header'],
                                'headerTitleText'        => $options['header_title_text'],
                                'headerLeftIcon'         => $options['header_left_icon'],
                                'headerLeftUrl'          => $options['header_left_url'],
                                'headerRightIcon'        => $options['header_right_icon'],
                                'headerRightUrl'         => $options['header_right_url'],
                                'historyEnabled'         => (bool) $options['show_history'],
                                'disclaimerText'         => $options['disclaimer_text'],
                                'disclaimerHighContrast' => (bool) $options['disclaimer_high_contrast'],
                                'initialThreadId'        => $options['initial_thread_id'],
                                'i18n'                   => [
                                        'unableToStart' => \__( 'Unable to start chat. Please try again later.', 'chatkit-wp' ),
                                        'configError'   => \__( 'Chat configuration error. Please contact support.', 'chatkit-wp' ),
                                        'loadFailed'    => \__( 'Chat widget failed to load. Please refresh the page.', 'chatkit-wp' ),
                                ],
                        ]
                );
        }

        /**
         * Render the ChatKit shortcode output.
         *
         * @param array<string, mixed> $atts Shortcode attributes.
         */
        public function render_shortcode( array $atts ): string {
                $this->widget_loaded = true;

                $options = $this->options->get_all();

                $atts = \shortcode_atts(
                        [
                                'button_text'  => $options['button_text'],
                                'accent_color' => $options['accent_color'],
                        ],
                        $atts,
                        'openai_chatkit'
                );

                $atts['button_text']  = \sanitize_text_field( $atts['button_text'] );
                $atts['accent_color'] = \sanitize_hex_color( $atts['accent_color'] ) ?: '#FF4500';

                \ob_start();
                ?>
                <button id="chatToggleBtn"
                        type="button"
                        aria-label="<?php echo \esc_attr__( 'Toggle chat window', 'chatkit-wp' ); ?>"
                        aria-expanded="false"
                        style="background-color: <?php echo \esc_attr( $atts['accent_color'] ); ?>;">
                        <?php echo \esc_html( $atts['button_text'] ); ?>
                </button>
                <openai-chatkit id="myChatkit"
                        role="dialog"
                        aria-modal="false"
                        aria-label="<?php echo \esc_attr__( 'Chat assistant', 'chatkit-wp' ); ?>"></openai-chatkit>
                <?php

                return (string) \ob_get_clean();
        }

        /**
         * Automatically inject the widget if configured to display everywhere.
         */
        public function maybe_auto_inject_widget(): void {
                if ( $this->widget_loaded ) {
                        return;
                }

                if ( $this->should_show_widget() ) {
                        echo $this->render_shortcode( [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
        }

        /**
         * Add the body attributes when a ChatKit shortcode is present.
         */
        public function conditional_body_attributes(): void {
                global $post;

                if ( ! $post ) {
                        return;
                }

                $content = (string) $post->post_content;

                if ( \has_shortcode( $content, 'openai_chatkit' ) || \has_shortcode( $content, 'chatkit' ) ) {
                        $this->add_body_attributes_script();
                }
        }

        /**
         * Print the inline script responsible for setting body data attributes.
         */
        public function add_body_attributes_script(): void {
                $options = $this->options->get_all();

                $attributes = [
                        'button_size'     => $options['button_size'],
                        'button_position' => $options['button_position'],
                        'border_radius'   => $options['border_radius'],
                        'shadow_style'    => $options['shadow_style'],
                ];

                $script_lines = [
                        '( function() {',
                        '\tvar applyAttributes = function() {',
                        '\t\tvar body = document.body;',
                        '\t\tif ( ! body ) {',
                        "\t\t\treturn;",
                        '\t\t}',
                ];

                foreach ( $attributes as $data_key => $value ) {
                        $script_lines[] = sprintf(
                                "\t\tbody.setAttribute( 'data-chatkit-%s', %s );",
                                str_replace( '_', '-', $data_key ),
                                \wp_json_encode( (string) $value )
                        );
                }

                $script_lines[] = '\t};';
                $script_lines[] = "\tif ( document.readyState === 'loading' ) {";
                $script_lines[] = "\t\tdocument.addEventListener( 'DOMContentLoaded', applyAttributes );";
                $script_lines[] = '\t} else {';
                $script_lines[] = '\t\tapplyAttributes();';
                $script_lines[] = '\t}';
                $script_lines[] = '} )();';

                \wp_print_inline_script_tag( implode( "\n", $script_lines ) );
        }

        /**
         * Determine whether the widget should render automatically on the current page.
         */
        private function should_show_widget(): bool {
                $basic_settings = $this->options->get_basic_settings();

                if ( empty( $basic_settings['show_everywhere'] ) ) {
                        return false;
                }

                if ( \is_front_page() && ! empty( $basic_settings['exclude_home'] ) ) {
                        return false;
                }

                if ( \is_archive() && ! empty( $basic_settings['exclude_archive'] ) ) {
                        return false;
                }

                if ( \is_search() && ! empty( $basic_settings['exclude_search'] ) ) {
                        return false;
                }

                if ( \is_404() && ! empty( $basic_settings['exclude_404'] ) ) {
                        return false;
                }

                $exclude_ids = (string) ( $basic_settings['exclude_ids'] ?? '' );

                if ( ! empty( $exclude_ids ) ) {
                        $excluded_array = array_map( 'trim', explode( ',', $exclude_ids ) );
                        $current_id     = \get_queried_object_id();

                        if ( in_array( $current_id, $excluded_array, true ) ) {
                                return false;
                        }
                }

                return true;
        }
}
