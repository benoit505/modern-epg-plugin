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
            if ($epg_data === false) {
                error_log('EPG Controller: Failed to fetch EPG data');
                return '<div class="epg-error">Error: Unable to load EPG data. Please try again later.</div>';
            }
            
            $channels = $epg_data['channels'] ?? [];
            $programs = $epg_data['programs'] ?? [];
            $channel_map = $epg_data['channel_map'] ?? [];
            
            error_log('EPG Controller: Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));
            
            if (empty($channels) || empty($programs)) {
                error_log('EPG Controller: Empty channels or programs');
                return '<div class="epg-error">Error: No EPG data available.</div>';
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
            
            // Debug information
            error_log('EPG Controller: Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));
            
            // Get the current group from the AJAX request
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
}
