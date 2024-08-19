<?php
class Modern_EPG_Public {

    public function __construct() {
        // Hook into WordPress actions to enqueue styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue styles for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style('modern-epg-public', plugin_dir_url(__FILE__) . 'css/epg-style.css', array(), MODERN_EPG_VERSION, 'all');
    }

    /**
     * Enqueue scripts for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('modern-epg-public', plugin_dir_url(__FILE__) . 'js/epg-frontend.js', array('jquery'), MODERN_EPG_VERSION, true);

        // Localize the script with data for AJAX or other purposes
        wp_localize_script('modern-epg-public', 'modernEpgData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('modern_epg_nonce')
        ));
    }

    /**
     * Register the shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('modern_epg', array($this, 'render_epg_shortcode'));
    }

    /**
     * Render the EPG via shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output of the EPG.
     */
    public function render_epg_shortcode($atts) {
        // Assuming the EPG_Controller and EPG_View are correctly set up
        global $epg_controller;

        if (!isset($epg_controller)) {
            return 'EPG Controller is not set up.';
        }

        // Fetch the EPG content using the controller
        $epg_content = $epg_controller->render_epg();

        // Return the EPG content, which should be HTML
        return $epg_content;
    }
}

// Initialize the public-facing functionality of the plugin
function run_modern_epg_public() {
    $plugin_public = new Modern_EPG_Public();
    $plugin_public->register_shortcodes();
}
add_action('init', 'run_modern_epg_public');
