<?php
class EPG_Controller {
    private $model;
    private $view;
    private $kodi_connection;
    private $channel_switcher;
    private $channel_merge;
    private $m3u_parser;

    public function __construct($model, $view) {
        modern_epg_log("EPG_Controller constructor called", 'DEBUG');
        $this->model = $model;
        $this->view = $view;
        
        // Get Kodi settings from WordPress options
        $options = get_option('modern_epg_options', array());
        $kodi_url = isset($options['kodi_url']) ? $options['kodi_url'] : 'localhost';
        $kodi_url = str_replace(['http://', 'https://'], '', $kodi_url); // Remove protocol if present
        $kodi_port = isset($options['kodi_port']) ? $options['kodi_port'] : '8080';
        $kodi_username = isset($options['kodi_username']) ? $options['kodi_username'] : '';
        $kodi_password = isset($options['kodi_password']) ? $options['kodi_password'] : '';
        modern_epg_log("Kodi settings - URL: $kodi_url, Port: $kodi_port", 'DEBUG');
        
        $this->kodi_connection = new Kodi_Connection($kodi_url, $kodi_port, $kodi_username, $kodi_password);
        $this->channel_switcher = new Channel_Switcher($this->kodi_connection);
        
        // Make sure M3U_Parser is also included
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class-m3u-parser.php';
        $this->m3u_parser = new M3U_Parser();
        $this->channel_merge = new Channel_Merge($this->kodi_connection, $this->m3u_parser);
        modern_epg_log("Kodi_Connection instance created in EPG_Controller", 'DEBUG');
        
        $this->init();
        modern_epg_log("EPG_Controller initialized", 'DEBUG');
    }

    public function init() {
        add_action('wp_ajax_get_current_channel', [$this, 'ajax_get_current_channel']);
        add_action('wp_ajax_nopriv_get_current_channel', [$this, 'ajax_get_current_channel']);
        add_action('wp_ajax_switch_channel', [$this, 'ajax_switch_channel']);
        add_action('wp_ajax_nopriv_switch_channel', [$this, 'ajax_switch_channel']);
        add_action('wp_ajax_check_kodi_availability', [$this, 'ajax_check_kodi_availability']);
        add_action('wp_ajax_nopriv_check_kodi_availability', [$this, 'ajax_check_kodi_availability']);
    }

    public function get_kodi_channels() {
        return $this->model->get_kodi_channels();
    }

    public function render_epg() {
        modern_epg_log("Starting render_epg", 'DEBUG');
        $epg_data = $this->model->get_epg_data();
        if ($epg_data === false || !is_array($epg_data)) {
            modern_epg_log("EPG data is not valid", 'ERROR');
            return "Error: Unable to fetch EPG data. Please check your settings.";
        }
        
        $output = $this->view->render_full_epg($epg_data['channels'], $epg_data['programs'], $epg_data['channel_map']);
        modern_epg_log("EPG HTML output length: " . strlen($output), 'DEBUG');
        return $output;
    }

    public function update_epg() {
        modern_epg_log('update_epg called', 'DEBUG');
        try {
            $epg_data = $this->model->get_epg_data();
            if ($epg_data === false) {
                $error_message = $this->model->get_last_error();
                modern_epg_log('Failed to fetch EPG data in update_epg. Error: ' . $error_message, 'ERROR');
                wp_send_json_error(['message' => 'Failed to fetch EPG data: ' . $error_message]);
                return;
            }
            
            modern_epg_log('EPG data fetched successfully. Channels: ' . count($epg_data['channels']) . ', Programs: ' . count($epg_data['programs']), 'INFO');
            
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
            modern_epg_log('Error in update_epg: ' . $e->getMessage(), 'ERROR');
            wp_send_json_error(['message' => 'An error occurred while updating the EPG: ' . $e->getMessage()]);
        }
    }

    public function switch_channel($channel_id) {
        modern_epg_log("EPG_Controller: Switching to channel ID: $channel_id", 'DEBUG');
        $result = $this->channel_switcher->switch_channel($channel_id);
        if ($result) {
            modern_epg_log("Successfully switched to channel ID: $channel_id", 'INFO');
        } else {
            modern_epg_log("Failed to switch to channel ID: $channel_id", 'ERROR');
        }
        return $result;
    }

    public function ajax_switch_channel() {
        check_ajax_referer('modern_epg_nonce', 'nonce');
        
        $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;
        
        if ($channel_id <= 0) {
            wp_send_json_error(['message' => 'Invalid channel ID']);
            return;
        }
        
        $result = $this->channel_switcher->switch_channel($channel_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Channel switched successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to switch channel']);
        }
    }

    public function ajax_get_current_channel() {
        check_ajax_referer('modern_epg_nonce', 'nonce');

        $current_channel = $this->channel_switcher->get_current_channel();
        if ($current_channel !== false) {
            wp_send_json_success(['channel' => $current_channel]);
        } else {
            wp_send_json_error(['message' => 'Failed to get current channel']);
        }
    }

    public function ajax_check_kodi_availability() {
        $is_online = $this->kodi_connection->is_online();
        wp_send_json(['is_online' => $is_online]);
    }

    public function handle_ajax_request($action) {
        check_ajax_referer('modern_epg_nonce', 'nonce');

        switch ($action) {
            case 'switch_channel':
                $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;
                if ($channel_id <= 0) {
                    wp_send_json_error(['message' => 'Invalid channel ID']);
                    return;
                }
                $result = $this->channel_switcher->switch_channel($channel_id);
                $result ? wp_send_json_success(['message' => 'Channel switched successfully']) 
                        : wp_send_json_error(['message' => 'Failed to switch channel']);
                break;

            case 'get_current_channel':
                $current_channel = $this->channel_switcher->get_current_channel();
                $current_channel !== false ? wp_send_json_success(['channel' => $current_channel]) 
                                           : wp_send_json_error(['message' => 'Failed to get current channel']);
                break;

            case 'check_kodi_availability':
                $kodi_available = $this->kodi_connection->is_online();
                $kodi_available ? wp_send_json_success(['message' => 'Kodi is available']) 
                                : wp_send_json_error(['message' => 'Kodi is not available']);
                break;

            default:
                wp_send_json_error(['message' => 'Invalid action']);
        }
    }

    public function test_kodi_connection() {
        modern_epg_log("Testing Kodi connection from EPG_Controller", 'DEBUG');
        $is_online = $this->kodi_connection->is_online();
        modern_epg_log("Kodi connection test result: " . ($is_online ? "Online" : "Offline"), 'INFO');
        return $is_online;
    }

    public function merge_channel_info() {
        $kodi_channels = $this->kodi_connection->get_channel_order();
        
        $m3u_parser = new M3U_Parser();
        $m3u_channels = $m3u_parser->parse_file(MODERN_EPG_PLUGIN_DIR . 'path/to/your/m3u/file.m3u');

        $merged_channels = [];

        foreach ($kodi_channels as $channel_id => $kodi_info) {
            $channel_name = $kodi_info['name'];
            if (isset($m3u_channels[$channel_name])) {
                $merged_channels[] = [
                    'id' => $channel_id,
                    'number' => $kodi_info['number'],
                    'name' => $channel_name,
                    'chno' => $m3u_channels[$channel_name]['chno'],
                    'url' => $m3u_channels[$channel_name]['url']
                ];
            }
        }

        // Sort by Kodi channel number
        usort($merged_channels, function($a, $b) {
            return $a['number'] <=> $b['number'];
        });

        return $merged_channels;
    }

    public function get_merged_channels() {
        error_log("EPG_Controller: Starting get_merged_channels method");
        
        $m3u_parser = new M3U_Parser();
        error_log("EPG_Controller: M3U_Parser instantiated");
        
        error_log("EPG_Controller: M3U URL: " . $this->m3u_url);
        $channels = $m3u_parser->parse_file($this->m3u_url);
        error_log("EPG_Controller: M3U parsing completed. Channel count: " . count($channels));
        
        return $this->channel_merge->merge_channel_info($m3u_file_path);
    }

    public function get_channel_map() {
        $epg_data = $this->model->get_epg_data();
        if (isset($epg_data['channel_map'])) {
            $channel_map = $epg_data['channel_map'];
            // Proceed with using $channel_map
        } else {
            // Handle the missing channel_map case
            error_log("Error: Missing 'channel_map' in EPG data.");
            // You can set a default value or handle the error as needed
            $channel_map = []; // or handle the error appropriately
            echo "Error: Missing required data for EPG display.";
            return; // Exit or redirect as needed
        }
        return $channel_map;
    }

    public function handle_channel_switch($channel_id) {
        error_log("EPG_Controller: Received channel switch request for ID: $channel_id");
        
        if ($this->kodi_connection->is_online()) {
            $switch_result = $this->kodi_connection->switch_channel($channel_id);
            
            if ($switch_result) {
                error_log("EPG_Controller: Channel switch successful for ID: $channel_id");
                // Handle success scenario
            } else {
                error_log("EPG_Controller: Channel switch failed for ID: $channel_id");
                // Handle failure scenario
            }
        } else {
            error_log("EPG_Controller: Kodi is offline. Channel switching disabled.");
            // Handle Kodi offline scenario
        }
    }
}