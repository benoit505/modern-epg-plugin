<?php
class EPG_Shortcode {
    private $api;

    public function __construct($api) {
        $this->api = $api;
    }

    public function register() {
        // Register the shortcode
        add_shortcode('modern_epg', array($this, 'render_epg'));
    }

    public function render_epg($atts) {
    error_log('EPG shortcode render_epg method called');
    $output = '<!-- EPG Shortcode Start -->';
    $output .= '<div id="modern-epg-container" class="modern-epg-container" style="border: 2px solid red; padding: 10px;">';
    $output .= 'EPG will be loaded here';
    $output .= '</div>';
    $output .= '<!-- EPG Shortcode End -->';
    error_log('EPG shortcode output: ' . $output);
    return $output;
}


    public function get_controller() {
        return $this->api;
    }
}
