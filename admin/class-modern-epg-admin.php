<?php
class Modern_EPG_Admin {
    public function __construct() {
        // Constructor code
    }

    public function enqueue_styles() {
        wp_enqueue_style('modern-epg-admin', plugin_dir_url(__FILE__) . 'css/epg-admin.css', array(), MODERN_EPG_VERSION, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('modern-epg-admin', plugin_dir_url(__FILE__) . 'js/epg-admin.js', array('jquery'), MODERN_EPG_VERSION, false);
    }

    // Add other admin-specific methods here
}