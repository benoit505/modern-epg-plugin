<?php
/**
 * Plugin Name: Modern EPG Plugin
 * Description: An MVC-based Electronic Program Guide plugin for WordPress
 * Version: 2.0
 * Author: Your Name
 * Text Domain: modern-epg-plugin
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MODERN_EPG_VERSION', '2.0');
define('MODERN_EPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MODERN_EPG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core plugin files
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-modern-epg-loader.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-modern-epg-i18n.php';

// Include MVC components
require_once MODERN_EPG_PLUGIN_DIR . 'includes/models/class-epg-data-model.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/views/class-epg-view.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/controllers/class-epg-controller.php';

// Include admin-specific files (if needed)
if (is_admin()) {
    require_once MODERN_EPG_PLUGIN_DIR . 'admin/class-modern-epg-admin.php';
}

// Include public-facing files
require_once MODERN_EPG_PLUGIN_DIR . 'public/class-modern-epg-public.php';

// Core plugin class
class Modern_EPG_Plugin {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $controller;

    public function __construct() {
        $this->version = MODERN_EPG_VERSION;
        $this->plugin_name = 'modern-epg-plugin';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Initialize Model, View, and Controller
        $model = new EPG_Data_Model();
        $view = new EPG_View();
        $this->controller = new EPG_Controller($model, $view);
    }

    private function define_admin_hooks() {
        // Define admin-specific hooks here, if needed
    }

    private function define_public_hooks() {
        // Enqueue public-facing styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Register the EPG shortcode
        add_shortcode('modern_epg', array($this->controller, 'render_epg'));

        // Register AJAX handlers
        add_action('wp_ajax_switch_channel', array($this->controller, 'switch_channel'));
        add_action('wp_ajax_nopriv_switch_channel', array($this->controller, 'switch_channel'));

        // Add this line
        add_action('wp_ajax_get_current_channel', array($this->controller, 'get_current_channel'));
        add_action('wp_ajax_nopriv_get_current_channel', array($this->controller, 'get_current_channel'));

        // Add new AJAX actions
        add_action('wp_ajax_check_kodi_availability', [$this->controller, 'check_kodi_availability']);
        add_action('wp_ajax_nopriv_check_kodi_availability', [$this->controller, 'check_kodi_availability']);

        // Add save settings action
        add_action('wp_ajax_save_epg_settings', [$this->controller, 'save_epg_settings']);
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, MODERN_EPG_PLUGIN_URL . 'public/css/epg-style.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('modern-epg-style', MODERN_EPG_PLUGIN_URL . 'public/css/epg-frontend.css', [], MODERN_EPG_VERSION);
        wp_enqueue_script('modern-epg-script', MODERN_EPG_PLUGIN_URL . 'public/js/epg-frontend.js', ['jquery'], MODERN_EPG_VERSION, true);
        wp_localize_script('modern-epg-script', 'modernEpgData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('modern_epg_nonce')
        ]);
    }

    public function run() {
        // Any code needed to run the plugin can be placed here
    }
}

// Begins execution of the plugin
function run_modern_epg() {
    $plugin = new Modern_EPG_Plugin();
    $plugin->run();
}
add_action('init', 'run_modern_epg');

// Debug: Add this to verify the plugin is loaded
add_action('wp_footer', function() {
    echo "<!-- Modern EPG Plugin is active -->";
});

// Activation hook to set default Kodi URL
register_activation_hook(__FILE__, 'modern_epg_activate');

function modern_epg_activate() {
    if (!get_option('modern_epg_kodi_url')) {
        update_option('modern_epg_kodi_url', 'http://192.168.0.3');
    }
    if (!get_option('modern_epg_kodi_port')) {
        update_option('modern_epg_kodi_port', '8080');
    }
    // Add similar checks for other options
}
