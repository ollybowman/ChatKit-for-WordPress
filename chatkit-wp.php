<?php
/**
 * Plugin Name: OpenAI ChatKit for WordPress
 * Plugin URI: https://github.com/francescogruner/openai-chatkit-wordpress
 * Description: Integrate OpenAI's ChatKit into your WordPress site with guided setup. Supports customizable text in any language.
 * Version: 1.0.3
 * Author: Francesco Grüner
 * Author URI: https://francescogruner.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chatkit-wp
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

declare( strict_types=1 );

namespace ChatkitWp;

use ChatkitWp\Admin\SettingsPage;
use ChatkitWp\Frontend\Widget;
use ChatkitWp\Rest\ApiController;
use ChatkitWp\Settings\Options;

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

if ( ! \defined( 'CHATKIT_WP_VERSION' ) ) {
        \define( 'CHATKIT_WP_VERSION', '1.0.3' );
}

if ( ! \defined( 'CHATKIT_WP_PLUGIN_DIR' ) ) {
        \define( 'CHATKIT_WP_PLUGIN_DIR', \plugin_dir_path( __FILE__ ) );
}

if ( ! \defined( 'CHATKIT_WP_PLUGIN_URL' ) ) {
        \define( 'CHATKIT_WP_PLUGIN_URL', \plugin_dir_url( __FILE__ ) );
}

require_once CHATKIT_WP_PLUGIN_DIR . 'includes/Settings/Options.php';
require_once CHATKIT_WP_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';
require_once CHATKIT_WP_PLUGIN_DIR . 'includes/Frontend/Widget.php';
require_once CHATKIT_WP_PLUGIN_DIR . 'includes/Rest/ApiController.php';

/**
 * Main plugin controller.
 */
final class Plugin {
        private static ?Plugin $instance = null;

        private Options $options;

        private SettingsPage $settings_page;

        private Widget $widget;

        private ApiController $api_controller;

        /**
         * Boot the plugin instance.
         */
        public static function instance(): Plugin {
                if ( null === self::$instance ) {
                        self::$instance = new self();
                }

                return self::$instance;
        }

        /**
         * Register WordPress hooks.
         */
        private function __construct() {
                $this->options        = new Options();
                $this->settings_page  = new SettingsPage( $this->options );
                $this->widget         = new Widget( $this->options );
                $this->api_controller = new ApiController( $this->options );

                \add_action( 'init', [ $this, 'load_textdomain' ] );
		\add_action( 'admin_menu', [ $this->settings_page, 'add_menu' ] );
		\add_action( 'admin_enqueue_scripts', [ $this->settings_page, 'enqueue_assets' ] );
                \add_action( 'admin_init', [ $this->settings_page, 'register_settings' ] );
                \add_action( 'rest_api_init', [ $this->api_controller, 'register_routes' ] );
                \add_action( 'wp_enqueue_scripts', [ $this->widget, 'enqueue_assets' ] );
                \add_action( 'wp_footer', [ $this->widget, 'maybe_auto_inject_widget' ], 999 );

                \add_shortcode( 'openai_chatkit', [ $this->widget, 'render_shortcode' ] );
                \add_shortcode( 'chatkit', [ $this->widget, 'render_shortcode' ] );

                if ( $this->options->should_show_everywhere() ) {
                        \add_action( 'wp_footer', [ $this->widget, 'add_body_attributes_script' ], 1 );
                } else {
                        \add_action( 'wp_footer', [ $this->widget, 'conditional_body_attributes' ], 1 );
                }
        }

        /**
         * Load plugin translations.
         */
        public function load_textdomain(): void {
                \load_plugin_textdomain( 'chatkit-wp', false, \dirname( \plugin_basename( __FILE__ ) ) . '/languages' );
        }
}

Plugin::instance();
