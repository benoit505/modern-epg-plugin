<?php
class EPG_Controller {
    private $model;
    private $view;
    private $log_file;

    public function __construct($model, $view) {
        $this->model = $model;
        $this->view = $view;
        $this->log_file = MODERN_EPG_PLUGIN_DIR . 'logs/controller.log';
    }

    public function render_epg() {
        //error_log('Render EPG method called.', 3, $this->log_file);

        date_default_timezone_set('Europe/Brussels');

        $epg_data = $this->model->get_epg_data();

        if ($epg_data === false) {
            //error_log('Failed to fetch EPG data.', 3, $this->log_file);
            return 'Error fetching EPG data.';
        }

        $channels = $epg_data['channels'];
        $programs = $epg_data['programs'];

        if (empty($channels) || empty($programs)) {
            //error_log('No channels or programs found.', 3, $this->log_file);
            return 'No EPG data available.';
        }

        //error_log('Channels fetched: ' . count($channels), 3, $this->log_file);
        //error_log('Programs fetched for ' . count($programs) . ' channels', 3, $this->log_file);

        //error_log('Rendering grid with channels and programs.', 3, $this->log_file);
        return $this->view->render_grid($channels, $programs);
    }

    public function update_epg() {
        //error_log('EPG update handler called', 3, $this->log_file);

        $epg_data = $this->model->get_epg_data();

        if ($epg_data === false) {
            wp_send_json_error(['message' => 'Failed to fetch EPG data']);
            return;
        }

        $channels = $epg_data['channels'];
        $programs = $epg_data['programs'];

        if (empty($channels) || empty($programs)) {
            wp_send_json_error(['message' => 'No EPG data available']);
            return;
        }

        $epg_html = $this->view->render_grid($channels, $programs);

        if (!$epg_html) {
            wp_send_json_error(['message' => 'Failed to render EPG']);
            return;
        }

        wp_send_json_success(['html' => $epg_html]);
    }
}
