<?php
class EPG_API {
    private $api_url;

    public function __construct() {
        $this->api_url = 'https://epg.bbwordpress.xyz/api/epg';
    }

    public function get_epg_data() {
        $response = wp_remote_get($this->api_url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}