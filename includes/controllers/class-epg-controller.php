<?php
class EPG_Controller {
    private $model;
    private $view;

    public function __construct($model, $view) {
        $this->model = $model;
        $this->view = $view;
        $this->init();
    }

    public function init() {
        add_action('wp_ajax_get_current_channel', [$this, 'get_current_channel']);
        add_action('wp_ajax_nopriv_get_current_channel', [$this, 'get_current_channel']);
    }

    public function get_kodi_channels() {
        return $this->model->get_kodi_channels();
    }

    public function render_epg() {
        try {
            $epg_data = $this->model->get_epg_data();
            if ($epg_data === false || !isset($epg_data['channels']) || !isset($epg_data['programs'])) {
                error_log('EPG Controller: Failed to fetch EPG data or data is incomplete');
                return '<div class="epg-error">Error: Unable to load EPG data. Please check your settings and try again.</div>';
            }
            
            $channels = $epg_data['channels'];
            $programs = $epg_data['programs'];
            $channel_map = $this->model->get_kodi_channel_mapping() ?: [];
            
            error_log('EPG Controller: Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));
            
            if (empty($channels)) {
                error_log('EPG Controller: No channels available');
                return '<div class="epg-error">Error: No channels available.</div>';
            }
            
            return $this->view->render_full_epg($channels, $programs, $channel_map);
        } catch (Exception $e) {
            error_log('EPG Controller Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return '<div class="epg-error">An error occurred while rendering the EPG.</div>';
        }
    }

    public function update_epg() {
        try {
            $epg_data = $this->model->get_epg_data();
            if ($epg_data === false) {
                error_log('EPG Controller: Failed to fetch EPG data');
                wp_send_json_error(['message' => 'Failed to fetch EPG data']);
                return;
            }
            
            $channels = $epg_data['channels'] ?? [];
            $programs = $epg_data['programs'] ?? [];
            $channel_map = $epg_data['channel_map'] ?? [];
            
            error_log('EPG Controller: Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));
            
            $current_group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : 'all';
            
            $full_epg = $this->view->render_full_epg($channels, $programs, $channel_map, $current_group);
            wp_send_json_success(['html' => $full_epg]);
        } catch (Exception $e) {
            error_log('EPG Update Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            wp_send_json_error(['message' => 'An error occurred while updating the EPG: ' . $e->getMessage()]);
        }
    }

    public function switch_channel() {
        check_ajax_referer('modern_epg_nonce', 'nonce');
        
        $channel = isset($_POST['channel']) ? $_POST['channel'] : '';
        
        if (empty($channel)) {
            wp_send_json_error(['message' => 'Invalid channel']);
            return;
        }

        error_log('Attempting to switch to channel: ' . $channel);

        $result = $this->model->switch_kodi_channel($channel);
        
        if ($result === true) {
            error_log('Channel switched successfully');
            wp_send_json_success(['message' => 'Channel switched successfully']);
        } else {
            $error_message = is_string($result) ? $result : 'Failed to switch channel';
            error_log('Failed to switch channel: ' . $error_message);
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    public function change_kodi_channel() {
        if (isset($_GET['channel'])) {
            $channel_id = intval($_GET['channel']);
            $result = $this->model->switch_kodi_channel($channel_id);
            wp_redirect(wp_get_referer());
            exit;
        }
    }

    public function get_current_channel() {
        check_ajax_referer('modern_epg_nonce', 'nonce');

        $current_channel = $this->model->get_current_kodi_channel();
        if ($current_channel !== false) {
            wp_send_json_success(['channel' => $current_channel]);
        } else {
            wp_send_json_error(['message' => 'Kodi is currently unavailable']);
        }
    }

    public function check_kodi_availability() {
        try {
            if (!check_ajax_referer('modern_epg_nonce', 'nonce', false)) {
                error_log('EPG Controller: Nonce check failed in check_kodi_availability');
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            $kodi_available = $this->model->check_kodi_availability();
            if ($kodi_available) {
                wp_send_json_success();
            } else {
                wp_send_json_error(['message' => 'Kodi is not available']);
            }
        } catch (Exception $e) {
            error_log('EPG Controller Exception in check_kodi_availability: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred while checking Kodi availability']);
        }
    }

    public function save_epg_settings() {
        check_ajax_referer('modern_epg_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to change these settings.']);
            return;
        }

        $kodi_url = esc_url_raw($_POST['kodi_url']);
        $kodi_port = intval($_POST['kodi_port']);
        $kodi_username = sanitize_text_field($_POST['kodi_username']);
        $kodi_password = $_POST['kodi_password'];
        $xml_url = esc_url_raw($_POST['xml_url']);
        $m3u_url = esc_url_raw($_POST['m3u_url']);

        // Validate URLs
        if (!filter_var($kodi_url, FILTER_VALIDATE_URL) || !filter_var($xml_url, FILTER_VALIDATE_URL) || !filter_var($m3u_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'Invalid URL provided.']);
            return;
        }

        // Validate port
        if ($kodi_port < 1 || $kodi_port > 65535) {
            wp_send_json_error(['message' => 'Invalid port number.']);
            return;
        }

        // Encrypt password before storing
        $encrypted_password = wp_hash_password($kodi_password);

        // Save settings
        update_option('modern_epg_kodi_url', $kodi_url);
        update_option('modern_epg_kodi_port', $kodi_port);
        update_option('modern_epg_kodi_username', $kodi_username);
        update_option('modern_epg_kodi_password', $encrypted_password);
        update_option('modern_epg_xml_url', $xml_url);
        update_option('modern_epg_m3u_url', $m3u_url);

        wp_send_json_success(['message' => 'Settings saved successfully.']);
    }
}