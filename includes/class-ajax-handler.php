<?php
class Modern_EPG_AJAX_Handler {
    private $controller;

    public function __construct($controller) {
        $this->controller = $controller;
        $this->init();
    }

    public function init() {
        add_action('wp_ajax_epg_action', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_epg_action', [$this, 'handle_ajax']);
    }

    public function handle_ajax() {
        if (!check_ajax_referer('modern_epg_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $action = isset($_POST['epg_action']) ? sanitize_text_field($_POST['epg_action']) : '';
        
        switch ($action) {
            case 'switch_kodi_channel':
                $this->switch_kodi_channel();
                break;
            case 'update_epg':
                $this->update_epg();
                break;
            default:
                wp_send_json_error(['message' => 'Invalid action']);
                break;
        }
    }

    private function switch_kodi_channel() {
        modern_epg_log("AJAX handler: switch_kodi_channel called", 'DEBUG');
        
        if (!isset($_POST['channel_id'])) {
            modern_epg_log("AJAX handler: Missing channel_id", 'ERROR');
            wp_send_json_error(['message' => 'Missing channel_id']);
            return;
        }
        
        $channel_id = intval($_POST['channel_id']);
        modern_epg_log("AJAX handler: Attempting to switch to channel ID: $channel_id", 'DEBUG');
        
        $result = $this->controller->handle_channel_switch($channel_id);
        
        if ($result) {
            modern_epg_log("AJAX handler: Channel switched successfully to ID: $channel_id", 'DEBUG');
            wp_send_json_success(['message' => "Channel switched successfully to ID: $channel_id"]);
        } else {
            modern_epg_log("AJAX handler: Failed to switch channel to ID: $channel_id", 'ERROR');
            wp_send_json_error(['message' => "Failed to switch channel to ID: $channel_id"]);
        }
    }

    private function update_epg() {
        $group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : 'all';
        $result = $this->controller->update_epg($group);
        wp_send_json($result);  // Send the entire result, don't modify it
    }
}
