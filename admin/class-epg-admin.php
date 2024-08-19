<?php
class EPG_Admin {
    public function __construct() {
        error_log('EPG_Admin class constructed');
    }

    public function init() {
        error_log('EPG_Admin init method called');
        // Add any initialization code here
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_options_page(
            'EPG Settings',
            'EPG Settings',
            'manage_options',
            'epg-settings',
            array($this, 'display_settings_page')
        );
    }

    public function display_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>EPG Settings</h1>';
        echo '<p>Configure your EPG settings here.</p>';
        echo '</div>';
    }
}