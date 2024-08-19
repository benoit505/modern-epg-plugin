<?php
class EPG_View {
    private $view_log_file;

    public function __construct() {
        $this->view_log_file = MODERN_EPG_PLUGIN_DIR . 'logs/view.log';
    }

    public function render_grid($channels, $programs) {
        if (empty($channels) || empty($programs)) {
            $this->log_error('No EPG data available.');
            return '<p>No EPG data available.</p>';
        }

        ob_start();
        include MODERN_EPG_PLUGIN_DIR . 'templates/epg-display.php';
        $output = ob_get_clean();

        if (empty($output)) {
            $this->log_error('Failed to render EPG template.');
            return '<p>Error rendering EPG.</p>';
        }

        return $output;
    }

    private function log_error($message) {
        error_log($message, 3, $this->view_log_file);
    }
}
