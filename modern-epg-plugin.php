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

// Include necessary files
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-kodi-connection.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-m3u-parser.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-channel-merge.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-channel-switcher.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/models/class-epg-data-model.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/views/class-epg-view.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/controllers/class-epg-controller.php';
require_once MODERN_EPG_PLUGIN_DIR . 'includes/class-ajax-handler.php';

// Initialize logging
function modern_epg_log($message, $level = 'INFO') {
    error_log("Modern EPG [$level]: $message");
}

// Main plugin initialization
function initialize_modern_epg_plugin() {
    modern_epg_log("Initializing Modern EPG Plugin", 'INFO');

    // Get plugin options
    $options = get_option('modern_epg_options', array());
    $kodi_url = isset($options['kodi_url']) ? $options['kodi_url'] : '';
    $kodi_port = isset($options['kodi_port']) ? $options['kodi_port'] : '8080';
    $kodi_username = isset($options['kodi_username']) ? $options['kodi_username'] : '';
    $kodi_password = isset($options['kodi_password']) ? $options['kodi_password'] : '';
    $m3u_url = isset($options['m3u_url']) ? $options['m3u_url'] : '';
    $xml_url = isset($options['xml_url']) ? $options['xml_url'] : '';

    modern_epg_log("M3U URL: $m3u_url", 'DEBUG');
    modern_epg_log("XML URL: $xml_url", 'DEBUG');

    // Initialize components
    $kodi_connection = new Kodi_Connection($kodi_url, $kodi_port, $kodi_username, $kodi_password);
    modern_epg_log("Kodi Connection initialized. Is online: " . ($kodi_connection->is_online() ? 'Yes' : 'No'), 'DEBUG');

    $m3u_parser = new M3U_Parser();
    modern_epg_log("M3U Parser initialized", 'DEBUG');

    $channel_merge = new Channel_Merge($kodi_connection, $m3u_parser);
    modern_epg_log("Channel Merge initialized", 'DEBUG');

    $channel_switcher = new Channel_Switcher($kodi_connection);
    modern_epg_log("Channel Switcher initialized", 'DEBUG');

    $epg_data_model = new EPG_Data_Model($kodi_connection, $m3u_parser, $channel_merge, $m3u_url, $xml_url);
    modern_epg_log("EPG Data Model initialized", 'DEBUG');

    $epg_view = new EPG_View();
    modern_epg_log("EPG View initialized", 'DEBUG');

    $epg_controller = new EPG_Controller($epg_data_model, $epg_view, $channel_switcher);
    modern_epg_log("EPG Controller initialized", 'DEBUG');

    // Register shortcode
    add_shortcode('modern_epg', array($epg_controller, 'render_epg'));

    // Initialize AJAX handler
    new Modern_EPG_AJAX_Handler($epg_controller, $kodi_connection);

    // Register styles and scripts
    add_action('wp_enqueue_scripts', 'enqueue_modern_epg_styles');
    add_action('wp_enqueue_scripts', 'enqueue_modern_epg_scripts');

    modern_epg_log("Modern EPG Plugin initialization complete", 'INFO');
}

// Enqueue styles
function enqueue_modern_epg_styles() {
    wp_enqueue_style('modern-epg-plugin', MODERN_EPG_PLUGIN_URL . 'public/css/epg-style.css', array(), MODERN_EPG_VERSION, 'all');
}

// Enqueue scripts
function enqueue_modern_epg_scripts() {
    wp_enqueue_script('modern-epg-plugin', MODERN_EPG_PLUGIN_URL . 'public/js/epg-frontend.js', array('jquery'), MODERN_EPG_VERSION, true);
    wp_localize_script('modern-epg-plugin', 'modernEpgData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('modern_epg_nonce')
    ));
}

// Initialize the plugin
add_action('init', 'initialize_modern_epg_plugin');

// Debug: Verify the plugin is loaded
add_action('wp_footer', function() {
    echo "<!-- Modern EPG Plugin is active -->";
});

// Plugin activation hook
register_activation_hook(__FILE__, 'modern_epg_activate');

function modern_epg_activate() {
    $default_options = array(
        'kodi_url' => '',
        'kodi_port' => '',
        'kodi_username' => '',
        'kodi_password' => '',
        'm3u_file_path' => ''
    );
    add_option('modern_epg_options', $default_options);
}

// Initialize the admin (only in admin area)
function run_modern_epg_admin() {
    if (is_admin()) {
        require_once MODERN_EPG_PLUGIN_DIR . 'includes/admin/class-modern-epg-admin.php';
        Modern_EPG_Admin::get_instance();
    }
}

add_action('plugins_loaded', 'run_modern_epg_admin');

// Ensure logs directory is writable
$logs_dir = MODERN_EPG_PLUGIN_DIR . 'logs';
if (!is_dir($logs_dir)) {
    wp_mkdir_p($logs_dir);
}
if (!is_writable($logs_dir)) {
    error_log("Modern EPG: Logs directory is not writable: $logs_dir");
}

// Final initialization log
modern_epg_log("Modern EPG Plugin fully loaded", 'INFO');