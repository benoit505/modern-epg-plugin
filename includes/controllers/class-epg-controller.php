<?php
class EPG_Controller {
    private $model;
    private $view;
    private $kodi_connection;
    private $channel_switcher;
    private $kodi_status_cache;
    private $channel_order_cache;

    public function __construct($model, $view, $kodi_connection, $channel_switcher) {
        $this->model = $model;
        $this->view = $view;
        $this->kodi_connection = $kodi_connection;
        $this->channel_switcher = $channel_switcher;
        $this->kodi_status_cache = get_transient('modern_epg_kodi_status');
        $this->channel_order_cache = get_transient('modern_epg_channel_order');
    }

    private function check_kodi_status() {
        return $this->kodi_connection->is_online();
    }

    public function render_epg() {
        $epg_data = $this->model->get_epg_data();
        $kodi_online = $this->check_kodi_status();
        return $this->view->render_full_epg($epg_data, 'all', $kodi_online);
    }

    public function update_epg($group) {
        try {
            $epg_data = $this->model->get_epg_data();
            modern_epg_log("EPG Data retrieved. Channels: " . count($epg_data['channels']) . ", Programs: " . count($epg_data['programs']), 'DEBUG');
            
            $kodi_online = $this->check_kodi_status();
            modern_epg_log("Kodi online status: " . ($kodi_online ? 'true' : 'false'), 'DEBUG');
            
            if (empty($epg_data['channels']) || empty($epg_data['programs'])) {
                modern_epg_log("EPG data is incomplete. Channels: " . (empty($epg_data['channels']) ? 'missing' : 'present') . ", Programs: " . (empty($epg_data['programs']) ? 'missing' : 'present'), 'ERROR');
                return [
                    'success' => false, 
                    'message' => "EPG data is incomplete", 
                    'html' => '',
                    'debug' => $epg_data
                ];
            }
            
            $html = $this->view->render_epg_grid($epg_data, $group, $kodi_online);
            modern_epg_log("EPG HTML generated. Length: " . strlen($html), 'DEBUG');
            
            return [
                'success' => true, 
                'html' => $html, 
                'debug' => [
                    'channels' => count($epg_data['channels']), 
                    'programs' => count($epg_data['programs'])
                ]
            ];
        } catch (Exception $e) {
            modern_epg_log("Error updating EPG: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false, 
                'message' => 'Failed to update EPG data: ' . $e->getMessage(), 
                'html' => '',
                'debug' => [
                    'error' => $e->getMessage(), 
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    public function handle_channel_switch($channel_id) {
        // modern_epg_log("Attempting to switch channel. Channel ID: $channel_id", 'DEBUG');
        
        if (!$this->kodi_connection->is_online()) {
            modern_epg_log("Cannot switch channel: Kodi is offline", 'WARNING');
            return false;
        }
        
        $result = $this->channel_switcher->switch_channel($channel_id);
        // modern_epg_log("Channel switch result: " . ($result ? 'success' : 'failure'), 'DEBUG');
        return $result;
    }

    public function get_current_channel() {
        if ($this->kodi_online) {
            return $this->channel_switcher->get_current_channel();
        } else {
            modern_epg_log("Cannot get current channel: Kodi is offline", 'WARNING');
            return false;
        }
    }

    public function is_kodi_online() {
        return $this->kodi_online;
    }

    public function switch_channel($channel_id) {
        // Implement the channel switching logic here
        // For example:
        $kodi_api = new Kodi_API();
        return $kodi_api->switch_to_channel($channel_id);
    }

    public function get_channel_order() {
        if ($this->channel_order_cache === false) {
            $order = $this->kodi_connection->get_channel_order();
            set_transient('modern_epg_channel_order', $order, 30 * MINUTE_IN_SECONDS);
            return $order;
        }
        return $this->channel_order_cache;
    }
}