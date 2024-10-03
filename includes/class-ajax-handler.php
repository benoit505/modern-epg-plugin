<?php
class Modern_EPG_AJAX_Handler {
    private $controller;
    private $kodi_connection;

    public function __construct($controller, $kodi_connection) {
        $this->controller = $controller;
        $this->kodi_connection = $kodi_connection;
        $this->init();
    }

    public function init() {
        add_action('wp_ajax_epg_action', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_epg_action', [$this, 'handle_ajax']);
    }

    public function handle_ajax() {
        check_ajax_referer('modern_epg_nonce', 'nonce');
        
        $action = isset($_POST['epg_action']) ? sanitize_text_field($_POST['epg_action']) : '';
        
        switch ($action) {
            case 'switch_kodi_channel':
                $this->switch_kodi_channel();
                break;
            default:
                if (method_exists($this->controller, 'handle_ajax_request')) {
                    $this->controller->handle_ajax_request($action);
                } else {
                    wp_send_json_error(['message' => 'Invalid action']);
                }
                break;
        }
    }

    private function switch_kodi_channel() {
        $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;

        if ($channel_id <= 0) {
            wp_send_json_error(['message' => 'Invalid channel ID']);
            return;
        }

        $result = $this->kodi_connection->switch_channel($channel_id);

        if ($result === true) {
            wp_send_json_success(['message' => 'Channel switched successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to switch channel: ' . $result]);
        }
    }
}
