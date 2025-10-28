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

$frontend_asset_path = CHATKIT_WP_PLUGIN_DIR . 'build/frontend.asset.php';

if ( file_exists( $frontend_asset_path ) ) {
		$asset      = include $frontend_asset_path;
		$deps       = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];
		$version    = isset( $asset['version'] ) ? $asset['version'] : CHATKIT_WP_VERSION;

		\wp_enqueue_script(
			'chatkit-embed',
			CHATKIT_WP_PLUGIN_URL . 'build/frontend.js',
			$deps,
			$version,
			true
		);
	} else {
		\wp_enqueue_script(
			'chatkit-embed',
			CHATKIT_WP_PLUGIN_URL . 'src/js/legacy/chatkit-embed-legacy.js',
			[],
			CHATKIT_WP_VERSION,
			true
		);
	}

	\wp_enqueue_style(
		'chatkit-embed',
		CHATKIT_WP_PLUGIN_URL . 'src/sass/chatkit-embed.css',
		[],
		CHATKIT_WP_VERSION
	);

	$loader_url = file_exists( CHATKIT_WP_PLUGIN_DIR . 'build/chatkit-loader.js' )
		? CHATKIT_WP_PLUGIN_URL . 'build/chatkit-loader.js'
		: CHATKIT_WP_PLUGIN_URL . 'src/js/frontend/chatkit.js';

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
						'bodyAttributes'         => [
							'themeMode'     => $options['theme_mode'],
							'buttonSize'     => $options['button_size'],
							'buttonPosition' => $options['button_position'],
							'borderRadius'   => $options['border_radius'],
							'shadowStyle'    => $options['shadow_style'],
						],
			'nudge'                  => [
				'enabled'       => (bool) ( $options['nudge_enabled'] ?? true ),
				'initialDelay'  => (int) ( $options['nudge_initial_delay'] ?? 12 ),
				'repeatDelay'   => (int) ( $options['nudge_repeat_delay'] ?? 36 ),
				'message'       => $options['nudge_message'] ?? '',
			],
			'loaderUrl'             => $loader_url,
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

		$email_address = \sanitize_email( \get_option( 'admin_email', '' ) );

		\ob_start();
		?>
		<button id="chatToggleBtn"
			type="button"
			aria-label="<?php echo \esc_attr__( 'Toggle chat window', 'chatkit-wp' ); ?>"
			aria-expanded="false"
			style="background-color: <?php echo \esc_attr( $atts['accent_color'] ); ?>;">
			<span class="chatkit-toggle-icon" aria-hidden="true">
				<?php
				echo file_get_contents( CHATKIT_WP_PLUGIN_DIR . 'assets/icons/message-square.svg' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.file_get_contents
				?>
			</span>
		</button>
		<?php if ( ! empty( $options['nudge_enabled'] ) ) : ?>
			<div id="chatkitNudge" class="chatkit-nudge" role="status" aria-live="polite" hidden>
				<div class="chatkit-nudge__bubble">
					<strong><?php echo \esc_html__( 'Need a hand?', 'chatkit-wp' ); ?></strong>
					<span><?php echo esc_html( $options['nudge_message'] ?? '' ); ?></span>
				</div>
				<button type="button" class="chatkit-nudge__dismiss" aria-label="<?php echo \esc_attr__( 'Dismiss message', 'chatkit-wp' ); ?>">
					×
				</button>
			</div>
		<?php endif; ?>
		<div id="chatkitChannelPicker" class="chatkit-channel-picker" hidden>
			<div class="chatkit-channel-header">
				<span class="chatkit-channel-title"><?php echo \esc_html__( 'Start a conversation', 'chatkit-wp' ); ?></span>
				<p class="chatkit-channel-subtitle"><?php echo \esc_html__( 'What channel do you prefer?', 'chatkit-wp' ); ?></p>
			</div>
			<div class="chatkit-channel-options">
				<button class="chatkit-channel-card" type="button" data-channel="email">
					<span class="chatkit-card-icon chatkit-card-icon--email" aria-hidden="true">
						<?php
						echo file_get_contents( CHATKIT_WP_PLUGIN_DIR . 'assets/icons/mail.svg' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.file_get_contents
						?>
					</span>
					<span class="chatkit-card-content">
						<strong><?php echo \esc_html__( 'Email', 'chatkit-wp' ); ?></strong>
						<small><?php echo \esc_html__( 'No time to wait around? We usually respond within a few hours.', 'chatkit-wp' ); ?></small>
					</span>
				</button>

				<button class="chatkit-channel-card" type="button" data-channel="chat">
					<span class="chatkit-card-icon chatkit-card-icon--chat" aria-hidden="true">
						<?php
						echo file_get_contents( CHATKIT_WP_PLUGIN_DIR . 'assets/icons/bot.svg' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.file_get_contents
						?>
					</span>
					<span class="chatkit-card-content">
						<strong><?php echo \esc_html__( 'Chat', 'chatkit-wp' ); ?></strong>
						<small><?php echo \esc_html__( 'We\'re online right now—talk with our team in real-time.', 'chatkit-wp' ); ?></small>
					</span>
				</button>
			</div>
		</div>

		<div id="chatkitEmailPanel" class="chatkit-email-panel" data-contact-email="<?php echo \esc_attr( $email_address ); ?>" hidden>
			<button type="button" class="chatkit-back-button" data-target="picker">
				<span aria-hidden="true">←</span>
				<?php echo \esc_html__( 'Back', 'chatkit-wp' ); ?>
			</button>
			<h3 class="chatkit-email-title"><?php echo \esc_html__( 'Send us an email', 'chatkit-wp' ); ?></h3>
			<p class="chatkit-email-subtitle">
				<?php echo \esc_html__( 'Share a few details and we\'ll get back to you shortly.', 'chatkit-wp' ); ?>
			</p>
			<form id="chatkitEmailForm" class="chatkit-email-form">
				<label>
					<?php echo \esc_html__( 'Your email address', 'chatkit-wp' ); ?>
					<input type="email" name="email" required placeholder="<?php echo \esc_attr__( 'you@example.com', 'chatkit-wp' ); ?>">
				</label>
				<label>
					<?php echo \esc_html__( 'How can we help?', 'chatkit-wp' ); ?>
					<textarea name="message" rows="4" required placeholder="<?php echo \esc_attr__( 'Tell us a bit about what you need…', 'chatkit-wp' ); ?>"></textarea>
				</label>
				<button type="submit" class="chatkit-email-submit">
					<?php echo \esc_html__( 'Send email', 'chatkit-wp' ); ?>
				</button>
			</form>
			<?php if ( ! empty( $email_address ) ) : ?>
				<p class="chatkit-email-alt">
					<?php
					printf(
						/* translators: %s: email address */
						\esc_html__( 'Prefer your own email client? Write to %s.', 'chatkit-wp' ),
						sprintf(
							'<a href="%1$s">%2$s</a>',
							\esc_url( 'mailto:' . rawurlencode( $email_address ) ),
							\esc_html( $email_address )
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<openai-chatkit id="myChatkit"
			role="dialog"
			aria-modal="false"
			hidden
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
		 * Legacy hook preserved for backwards compatibility. The front-end script now applies body attributes.
		 */
		public function conditional_body_attributes(): void {
			// Intentionally left blank.
		}

		/**
		 * Legacy hook preserved for backwards compatibility. Body attributes are applied via assets/chatkit-embed.js.
		 */
		public function add_body_attributes_script(): void {
			// Intentionally left blank.
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
