<?php
class EPG_View {
    private $view_log_file;

    public function __construct() {
        $this->view_log_file = MODERN_EPG_PLUGIN_DIR . 'logs/view.log';
    }

    public function render_filter_buttons() {
        modern_epg_log('Rendering filter buttons', 'DEBUG');
        return $this->render_template('filter-buttons.php');
    }

    public function render_full_epg($epg_data, $group = 'all', $kodi_online = false) {
        modern_epg_log("Starting render_full_epg method", 'DEBUG');

        if (empty($epg_data) || !isset($epg_data['channels']) || !isset($epg_data['programs'])) {
            modern_epg_log("Error: Missing required data for EPG display", 'ERROR');
            return "Error: Missing required data for EPG display.";
        }

        $channels = $epg_data['channels'];
        $programs = $epg_data['programs'];
        $channel_map = $epg_data['channel_map'] ?? [];

        modern_epg_log("Number of channels: " . count($channels), 'DEBUG');
        modern_epg_log("Number of programs: " . count($programs), 'DEBUG');

        // Comment out or remove the following lines
        // modern_epg_log("Sample channel data: " . print_r(array_slice($channels, 0, 1, true), true), 'DEBUG');
        // modern_epg_log("Sample program data: " . print_r(array_slice($programs, 0, 1, true), true), 'DEBUG');

        $html = $this->render_template('full-epg-display.php', [
            'epg_data' => $epg_data,
            'channels' => $channels,
            'programs' => $programs,
            'channel_map' => $channel_map,
            'group' => $group,
            'kodi_online' => $kodi_online
        ]);

        modern_epg_log("EPG HTML generated successfully", 'DEBUG');
        return $html;
    }

    public function render_epg_grid($epg_data, $group = 'all', $kodi_online = false) {
        if (empty($epg_data) || !isset($epg_data['channels']) || !isset($epg_data['programs'])) {
            modern_epg_log("Error: Missing required data for EPG display", 'ERROR');
            return ''; // Return empty string instead of error message
        }

        return $this->render_template('epg-grid.php', [
            'epg_data' => $epg_data,
            'group' => $group,
            'kodi_online' => $kodi_online
        ]);
    }

    private function render_template($template_name, $data = []) {
        ob_start();
        extract($data);
        include MODERN_EPG_PLUGIN_DIR . "templates/$template_name";
        return ob_get_clean();
    }

    private function log_epg_data($epg_data, $current_group) {
        modern_epg_log('Channels count: ' . count($epg_data['channels'] ?? []), 'DEBUG');
        modern_epg_log('Programs count: ' . count($epg_data['programs'] ?? []), 'DEBUG');
        modern_epg_log('Channel Map count: ' . count($epg_data['channel_map'] ?? []), 'DEBUG');
        modern_epg_log('Current Group: ' . $current_group, 'DEBUG');
    }

    private function should_show_offline_message($epg_data, $kodi_online) {
        $channels = $epg_data['channels'] ?? [];
        if (empty($channels) && !$kodi_online) {
            modern_epg_log('Channels array is empty and Kodi is offline. This is expected.', 'INFO');
            return true;
        }
        return false;
    }

    private function log_error($message) {
        error_log($message, 3, $this->view_log_file);
    }
}
