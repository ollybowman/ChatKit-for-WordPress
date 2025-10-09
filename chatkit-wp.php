<?php
/**
 * Plugin Name: OpenAI ChatKit for WordPress
 * Plugin URI: https://github.com/francescogruner/openai-chatkit-wordpress
 * Description: Integrate OpenAI's ChatKit into your WordPress site with guided setup. Supports customizable text in any language.
 * Version: 1.0.0
 * Author: Francesco Grüner
 * Author URI: https://francescogruner.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chatkit-wp
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Original Author: Francesco Grüner (https://francescogruner.it)
 * This plugin is based on the original work by Francesco Grüner. Please retain attribution if modifying or redistributing.
 */

if (!defined('ABSPATH')) exit;

define('CHATKIT_WP_VERSION', '1.0.0');
define('CHATKIT_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHATKIT_WP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ChatKit_WordPress {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('openai_chatkit', [$this, 'render_chatkit_shortcode']);

        // Always load assets (fix)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Auto-inject if enabled
        if (get_option('chatkit_show_everywhere', false)) {
            add_action('wp_footer', [$this, 'auto_inject_widget'], 999);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('chatkit-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_options_page(
            __('ChatKit Settings', 'chatkit-wp'),
            __('ChatKit', 'chatkit-wp'),
            'manage_options',
            'chatkit-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('chatkit_wp_settings', 'chatkit_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_workflow_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_accent_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#FF4500'
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_button_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('Chat now', 'chatkit-wp')
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_theme_mode', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'dark'
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_enable_attachments', [
            'type' => 'boolean',
            'default' => false
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_persistent_sessions', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_show_everywhere', [
            'type' => 'boolean',
            'default' => false
        ]);

        // New settings for customizable strings
        register_setting('chatkit_wp_settings', 'chatkit_greeting_text', [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => __('How can I help you today?', 'chatkit-wp')
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_1', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('How can I assist you?', 'chatkit-wp')
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_1_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('Hi! How can I assist you today?', 'chatkit-wp')
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_2', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_2_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_3', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('chatkit_wp_settings', 'chatkit_default_prompt_3_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['chatkit_save_settings'])) {
            check_admin_referer('chatkit_settings_save');

            update_option('chatkit_api_key', sanitize_text_field($_POST['chatkit_api_key'] ?? ''));
            update_option('chatkit_workflow_id', sanitize_text_field($_POST['chatkit_workflow_id'] ?? ''));
            update_option('chatkit_accent_color', sanitize_hex_color($_POST['chatkit_accent_color'] ?? '#FF4500'));
            update_option('chatkit_button_text', sanitize_text_field($_POST['chatkit_button_text'] ?? __('Chat now', 'chatkit-wp')));
            update_option('chatkit_theme_mode', sanitize_text_field($_POST['chatkit_theme_mode'] ?? 'dark'));
            update_option('chatkit_enable_attachments', isset($_POST['chatkit_enable_attachments']));
            update_option('chatkit_persistent_sessions', isset($_POST['chatkit_persistent_sessions']));
            update_option('chatkit_show_everywhere', isset($_POST['chatkit_show_everywhere']));

            update_option('chatkit_greeting_text', wp_kses_post($_POST['chatkit_greeting_text'] ?? __('How can I help you today?', 'chatkit-wp')));
            update_option('chatkit_default_prompt_1', sanitize_text_field($_POST['chatkit_default_prompt_1'] ?? __('How can I assist you?', 'chatkit-wp')));
            update_option('chatkit_default_prompt_1_text', sanitize_text_field($_POST['chatkit_default_prompt_1_text'] ?? __('Hi! How can I assist you today?', 'chatkit-wp')));
            update_option('chatkit_default_prompt_2', sanitize_text_field($_POST['chatkit_default_prompt_2'] ?? ''));
            update_option('chatkit_default_prompt_2_text', sanitize_text_field($_POST['chatkit_default_prompt_2_text'] ?? ''));
            update_option('chatkit_default_prompt_3', sanitize_text_field($_POST['chatkit_default_prompt_3'] ?? ''));
            update_option('chatkit_default_prompt_3_text', sanitize_text_field($_POST['chatkit_default_prompt_3_text'] ?? ''));

            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'chatkit-wp') . '</p></div>';
        }

        $api_key = $this->get_api_key();
        $workflow_id = $this->get_workflow_id();
        $accent_color = get_option('chatkit_accent_color', '#FF4500');
        $button_text = get_option('chatkit_button_text', __('Chat now', 'chatkit-wp'));
        $theme_mode = get_option('chatkit_theme_mode', 'dark');
        $enable_attachments = get_option('chatkit_enable_attachments', false);
        $persistent_sessions = get_option('chatkit_persistent_sessions', true);
        $show_everywhere = get_option('chatkit_show_everywhere', false);

        $greeting_text = get_option('chatkit_greeting_text', __('How can I help you today?', 'chatkit-wp'));
        $default_prompt_1 = get_option('chatkit_default_prompt_1', __('How can I assist you?', 'chatkit-wp'));
        $default_prompt_1_text = get_option('chatkit_default_prompt_1_text', __('Hi! How can I assist you today?', 'chatkit-wp'));
        $default_prompt_2 = get_option('chatkit_default_prompt_2', '');
        $default_prompt_2_text = get_option('chatkit_default_prompt_2_text', '');
        $default_prompt_3 = get_option('chatkit_default_prompt_3', '');
        $default_prompt_3_text = get_option('chatkit_default_prompt_3_text', '');

        ?>
        <div class="wrap">
            <h1><?php _e('ChatKit Settings', 'chatkit-wp'); ?></h1>

            <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h3>📖 How to use this plugin</h3>
                <ol>
                    <li>Create a workflow on <a href="https://platform.openai.com/agent-builder" target="_blank">OpenAI Agent Builder</a> (requires login)</li>
                    <li>Copy the <strong>Workflow ID</strong> (starts with <code>wf_</code>)</li>
                    <li>Generate an <strong>API Key</strong> from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a> (requires login)</li>
                    <li>Enter credentials below</li>
                    <li>Add shortcode <code>[openai_chatkit]</code> to a page OR enable "Show on all pages"</li>
                </ol>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('chatkit_settings_save'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="chatkit_api_key"><?php _e('OpenAI API Key', 'chatkit-wp'); ?> *</label></th>
                        <td>
                            <input type="password" id="chatkit_api_key" name="chatkit_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text"
                                   placeholder="sk-proj-...">
                            <p class="description">
                                <?php _e('⚠️ IMPORTANT: For security, add this key to', 'chatkit-wp'); ?>
                                <code>wp-config.php</code>:<br>
                                <code>define('CHATKIT_OPENAI_API_KEY', 'sk-proj-...');</code>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="chatkit_workflow_id"><?php _e('Workflow ID', 'chatkit-wp'); ?> *</label></th>
                        <td>
                            <input type="text" id="chatkit_workflow_id" name="chatkit_workflow_id"
                                   value="<?php echo esc_attr($workflow_id); ?>"
                                   class="regular-text"
                                   placeholder="wf_...">
                            <p class="description">
                                <?php _e('ID of the workflow created in Agent Builder', 'chatkit-wp'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="chatkit_button_text"><?php _e('Button Text', 'chatkit-wp'); ?></label></th>
                        <td>
                            <input type="text" id="chatkit_button_text" name="chatkit_button_text"
                                   value="<?php echo esc_attr($button_text); ?>"
                                   class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="chatkit_accent_color"><?php _e('Accent Color', 'chatkit-wp'); ?></label></th>
                        <td>
                            <input type="color" id="chatkit_accent_color" name="chatkit_accent_color"
                                   value="<?php echo esc_attr($accent_color); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="chatkit_theme_mode"><?php _e('Theme', 'chatkit-wp'); ?></label></th>
                        <td>
                            <select id="chatkit_theme_mode" name="chatkit_theme_mode">
                                <option value="dark" <?php selected($theme_mode, 'dark'); ?>>Dark</option>
                                <option value="light" <?php selected($theme_mode, 'light'); ?>>Light</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Display Options', 'chatkit-wp'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chatkit_show_everywhere"
                                       <?php checked($show_everywhere, true); ?>>
                                <strong><?php _e('Show widget on ALL pages automatically', 'chatkit-wp'); ?></strong>
                            </label>
                            <p class="description">
                                <?php _e('⚠️ If enabled, the widget will appear on every page without needing the [openai_chatkit] shortcode. Useful for global chat.', 'chatkit-wp'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Advanced Features', 'chatkit-wp'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chatkit_enable_attachments"
                                       <?php checked($enable_attachments, true); ?>>
                                <?php _e('Enable file uploads', 'chatkit-wp'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="chatkit_persistent_sessions"
                                       <?php checked($persistent_sessions, true); ?>>
                                <?php _e('Keep conversation history (via cookie)', 'chatkit-wp'); ?>
                            </label>
                        </td>
                    </tr>

                    <!-- Customizable Strings Section -->
                    <tr>
                        <th scope="row"><?php _e('Customize Texts', 'chatkit-wp'); ?></th>
                        <td>
                            <table>
                                <tr>
                                    <td><label for="chatkit_greeting_text"><?php _e('Greeting Text', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_greeting_text" name="chatkit_greeting_text"
                                               value="<?php echo esc_attr($greeting_text); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('Text shown when chat opens.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_1"><?php _e('Default Prompt 1 (Label)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_1" name="chatkit_default_prompt_1"
                                               value="<?php echo esc_attr($default_prompt_1); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('Label for the first quick question.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_1_text"><?php _e('Default Prompt 1 (Sent Text)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_1_text" name="chatkit_default_prompt_1_text"
                                               value="<?php echo esc_attr($default_prompt_1_text); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('Actual text sent when clicking prompt 1.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_2"><?php _e('Default Prompt 2 (Label)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_2" name="chatkit_default_prompt_2"
                                               value="<?php echo esc_attr($default_prompt_2); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('(Optional) Label for the second quick question.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_2_text"><?php _e('Default Prompt 2 (Sent Text)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_2_text" name="chatkit_default_prompt_2_text"
                                               value="<?php echo esc_attr($default_prompt_2_text); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('(Optional) Text sent for prompt 2.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_3"><?php _e('Default Prompt 3 (Label)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_3" name="chatkit_default_prompt_3"
                                               value="<?php echo esc_attr($default_prompt_3); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('(Optional) Label for the third quick question.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="chatkit_default_prompt_3_text"><?php _e('Default Prompt 3 (Sent Text)', 'chatkit-wp'); ?></label></td>
                                    <td><input type="text" id="chatkit_default_prompt_3_text" name="chatkit_default_prompt_3_text"
                                               value="<?php echo esc_attr($default_prompt_3_text); ?>"
                                               class="regular-text">
                                        <p class="description"><?php _e('(Optional) Text sent for prompt 3.', 'chatkit-wp'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <?php submit_button(__('Save Settings', 'chatkit-wp'), 'primary', 'chatkit_save_settings'); ?>
            </form>

            <hr>

            <h2><?php _e('Test Configuration', 'chatkit-wp'); ?></h2>
            <div id="chatkit-test-result" style="margin-top: 15px;"></div>
            <button type="button" class="button button-secondary" id="chatkit-test-btn">
                <?php _e('🔍 Test API Connection', 'chatkit-wp'); ?>
            </button>

            <script>
            document.getElementById('chatkit-test-btn').addEventListener('click', async () => {
                const resultDiv = document.getElementById('chatkit-test-result');
                const btn = document.getElementById('chatkit-test-btn');
                btn.disabled = true;
                btn.textContent = '⏳ Testing...';

                try {
                    const response = await fetch('<?php echo rest_url('chatkit/v1/test'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        resultDiv.innerHTML = '<div class="notice notice-success inline"><p>✅ ' + data.message + '</p></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="notice notice-error inline"><p>❌ ' + (data.message || 'Unknown error') + '</p></div>';
                    }
                } catch (error) {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>❌ Error: ' + error.message + '</p></div>';
                } finally {
                    btn.disabled = false;
                    btn.textContent = '🔍 Test API Connection';
                }
            });
            </script>
        </div>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('chatkit/v1', '/session', [
            'methods' => 'POST',
            'callback' => [$this, 'create_session'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chatkit/v1', '/test', [
            'methods' => 'POST',
            'callback' => [$this, 'test_connection'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    public function create_session(\WP_REST_Request $request) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $transient_key = 'chatkit_ratelimit_' . md5($ip);
        $requests = get_transient($transient_key) ?: 0;

        if ($requests > 10) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please try again in a minute.', 'chatkit-wp'),
                ['status' => 429]
            );
        }

        set_transient($transient_key, $requests + 1, 60);

        $api_key = $this->get_api_key();
        $workflow_id = $this->get_workflow_id();

        if (empty($api_key) || empty($workflow_id)) {
            return new \WP_Error(
                'missing_config',
                __('Plugin not configured. Contact administrator.', 'chatkit-wp'),
                ['status' => 500]
            );
        }

        $user_id = $this->get_or_create_user_id();

        $response = wp_remote_post('https://api.openai.com/v1/chatkit/sessions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'chatkit_beta=v1'
            ],
            'body' => wp_json_encode([
                'workflow' => ['id' => $workflow_id],
                'user' => $user_id
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error(
                'api_error',
                $response->get_error_message(),
                ['status' => 502]
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 || empty($body['client_secret'])) {
            return new \WP_Error(
                'invalid_response',
                __('Error creating session', 'chatkit-wp'),
                ['status' => $status_code]
            );
        }

        return rest_ensure_response([
            'client_secret' => $body['client_secret']
        ]);
    }

    public function test_connection() {
        $api_key = $this->get_api_key();
        $workflow_id = $this->get_workflow_id();

        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', __('API Key not configured', 'chatkit-wp'), ['status' => 400]);
        }

        if (empty($workflow_id)) {
            return new \WP_Error('missing_workflow_id', __('Workflow ID not configured', 'chatkit-wp'), ['status' => 400]);
        }

        $response = wp_remote_post('https://api.openai.com/v1/chatkit/sessions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'chatkit_beta=v1'
            ],
            'body' => wp_json_encode([
                'workflow' => ['id' => $workflow_id],
                'user' => 'test_' . time()
            ]),
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('connection_failed', $response->get_error_message(), ['status' => 502]);
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return rest_ensure_response(['message' => __('✅ Connection successful! Plugin is correctly configured.', 'chatkit-wp')]);
        } else {
            $body = wp_remote_retrieve_body($response);
            return new \WP_Error('api_error', sprintf(__('API Error (status %d): %s', 'chatkit-wp'), $status_code, $body), ['status' => $status_code]);
        }
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script(
            'chatkit-embed',
            CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-embed.js',
            [],
            CHATKIT_WP_VERSION,
            true
        );

        wp_enqueue_style(
            'chatkit-embed',
            CHATKIT_WP_PLUGIN_URL . 'assets/chatkit-embed.css',
            [],
            CHATKIT_WP_VERSION
        );

        wp_localize_script('chatkit-embed', 'chatkitConfig', [
            'restUrl' => rest_url('chatkit/v1/session'),
            'accentColor' => get_option('chatkit_accent_color', '#FF4500'),
            'themeMode' => get_option('chatkit_theme_mode', 'dark'),
            'enableAttachments' => get_option('chatkit_enable_attachments', false),
            'buttonText' => get_option('chatkit_button_text', __('Chat now', 'chatkit-wp')),
            'greetingText' => get_option('chatkit_greeting_text', __('How can I help you today?', 'chatkit-wp')),
            'defaultPrompt1' => get_option('chatkit_default_prompt_1', __('How can I assist you?', 'chatkit-wp')),
            'defaultPrompt1Text' => get_option('chatkit_default_prompt_1_text', __('Hi! How can I assist you today?', 'chatkit-wp')),
            'defaultPrompt2' => get_option('chatkit_default_prompt_2', ''),
            'defaultPrompt2Text' => get_option('chatkit_default_prompt_2_text', ''),
            'defaultPrompt3' => get_option('chatkit_default_prompt_3', ''),
            'defaultPrompt3Text' => get_option('chatkit_default_prompt_3_text', ''),
        ]);
    }

    public function render_chatkit_shortcode($atts) {
        $atts = shortcode_atts([
            'button_text' => get_option('chatkit_button_text', __('Chat now', 'chatkit-wp')),
            'accent_color' => get_option('chatkit_accent_color', '#FF4500'),
        ], $atts, 'openai_chatkit');

        ob_start();
        ?>
        <button id="chatToggleBtn" type="button" style="background-color: <?php echo esc_attr($atts['accent_color']); ?>">
            <?php echo esc_html($atts['button_text']); ?>
        </button>
        <openai-chatkit id="myChatkit"></openai-chatkit>
        <?php
        return ob_get_clean();
    }

    public function auto_inject_widget() {
        echo $this->render_chatkit_shortcode([]);
    }

    private function get_api_key() {
        if (defined('CHATKIT_OPENAI_API_KEY') && !empty(CHATKIT_OPENAI_API_KEY)) {
            return CHATKIT_OPENAI_API_KEY;
        }
        return get_option('chatkit_api_key', '');
    }

    private function get_workflow_id() {
        if (defined('CHATKIT_WORKFLOW_ID') && !empty(CHATKIT_WORKFLOW_ID)) {
            return CHATKIT_WORKFLOW_ID;
        }
        return get_option('chatkit_workflow_id', '');
    }

    private function get_or_create_user_id() {
        $persistent = get_option('chatkit_persistent_sessions', true);

        if (!$persistent) {
            return 'guest_' . uniqid();
        }

        $cookie_name = 'chatkit_user_id';

        if (isset($_COOKIE[$cookie_name])) {
            return sanitize_text_field($_COOKIE[$cookie_name]);
        }

        $user_id = 'user_' . wp_generate_password(16, false);
        setcookie($cookie_name, $user_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        return $user_id;
    }
}

ChatKit_WordPress::get_instance();