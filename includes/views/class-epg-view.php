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
        ob_start();
        include MODERN_EPG_PLUGIN_DIR . 'templates/epg-grid.php';
        return ob_get_clean();
    }

    public function render_full_epg($channels, $programs, $channel_map = [], $current_group = 'all') {
        if (empty($channels) || empty($programs)) {
            error_log('EPG View: Missing essential data - Channels: ' . count($channels) . ', Programs: ' . count($programs));
            return '<div class="epg-error">Error: Unable to load EPG data. Please try again later.</div>';
        }

        ob_start();
        try {
            include MODERN_EPG_PLUGIN_DIR . 'templates/full-epg-display.php';
        } catch (Exception $e) {
            error_log('EPG View Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return '<div class="epg-error">Error: An unexpected error occurred while rendering the EPG.</div>';
        }
        $output = ob_get_clean();
        
        if (empty($output)) {
            error_log('EPG View: Empty output from full-epg-display.php');
            return '<div class="epg-error">Error: Unable to render EPG. Please contact support.</div>';
        }
        
        return $output;
    }

    private function log_error($message) {
        error_log($message, 3, $this->view_log_file);
    }
}
