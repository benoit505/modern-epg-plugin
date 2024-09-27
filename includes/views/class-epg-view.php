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

    public function render_full_epg($channels, $programs, $channel_map, $current_group = 'all') {
        ob_start();
        include MODERN_EPG_PLUGIN_DIR . 'templates/full-epg-display.php';
        $output = ob_get_clean();
        
        if (empty($output)) {
            error_log('EPG View: Empty output from full-epg-display.php');
        }
        
        return $output;
    }

    private function log_error($message) {
        error_log($message, 3, $this->view_log_file);
    }
}
