<?php
class EPG_View {
    private $view_log_file;

    public function __construct() {
        $this->view_log_file = MODERN_EPG_PLUGIN_DIR . 'logs/view.log';
    }

    public function render_filter_buttons() {
        ob_start();
        include MODERN_EPG_PLUGIN_DIR . 'templates/filter-buttons.php';
        return ob_get_clean();
    }

    public function render_grid($channels, $programs, $channel_map) {
        // Added null checks
        if (!is_array($channels) || !is_array($programs) || !is_array($channel_map)) {
            modern_epg_log('Invalid data passed to render_grid', 'ERROR');
            return '<div class="epg-error">Error: Invalid data for grid rendering.</div>';
        }

        modern_epg_log('Rendering grid. Channels: ' . count($channels) . ', Programs: ' . count($programs), 'DEBUG');
        
        ob_start();
        include MODERN_EPG_PLUGIN_DIR . 'templates/epg-grid.php';
        $output = ob_get_clean();
        
        modern_epg_log('Grid rendering complete. Output length: ' . strlen($output), 'DEBUG');
        return $output;
    }

    public function render_full_epg($channels, $programs, $channel_map = [], $current_group = 'all') {
        modern_epg_log('Starting render_full_epg', 'DEBUG');

        // Added null checks and default to empty arrays
        $channels = is_array($channels) ? $channels : [];
        $programs = is_array($programs) ? $programs : [];
        $channel_map = is_array($channel_map) ? $channel_map : [];

        modern_epg_log('Channels count: ' . count($channels), 'DEBUG');
        modern_epg_log('Programs count: ' . count($programs), 'DEBUG');
        modern_epg_log('Channel Map count: ' . count($channel_map), 'DEBUG');
        modern_epg_log('Current Group: ' . $current_group, 'DEBUG');

        if (empty($channels)) {
            modern_epg_log('Channels array is empty', 'ERROR');
        }
        if (empty($programs)) {
            modern_epg_log('Programs array is empty', 'ERROR');
        }
        if (empty($channel_map)) {
            modern_epg_log('Channel Map array is empty', 'ERROR');
        }

        if (empty($channels) || empty($programs) || empty($channel_map)) {
            modern_epg_log('Missing essential data for EPG rendering', 'ERROR');
            return '<div class="epg-error">Error: Unable to load EPG data. Please try again later.</div>';
        }

        ob_start();
        try {
            modern_epg_log('Including full-epg-display.php template', 'DEBUG');
            include MODERN_EPG_PLUGIN_DIR . 'templates/full-epg-display.php';
            modern_epg_log('Finished including full-epg-display.php template', 'DEBUG');
        } catch (Exception $e) {
            modern_epg_log('Exception while rendering EPG: ' . $e->getMessage(), 'ERROR');
            return '<div class="epg-error">Error: An unexpected error occurred while rendering the EPG.</div>';
        }
        $output = ob_get_clean();
        
        if (empty($output)) {
            modern_epg_log('Empty output from full-epg-display.php', 'ERROR');
            return '<div class="epg-error">Error: Unable to render EPG. Please contact support.</div>';
        }
        
        modern_epg_log('Full EPG rendered successfully. Output length: ' . strlen($output), 'INFO');
        return $output;
    }

    private function log_error($message) {
        error_log($message, 3, $this->view_log_file);
    }
}
