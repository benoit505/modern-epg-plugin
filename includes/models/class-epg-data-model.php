<?php

class EPG_Data_Model {
    private $xml_url = 'https://www.dropbox.com/scl/fi/olnk1zwz8pnugmdsl1457/live.xml?rlkey=dpk6ves6ok2ar4oyowtv8ay6j&dl=1';
    private $m3u_url = 'https://www.dropbox.com/scl/fi/hftp0fhhm55ahtudtzgs2/live.m3u?rlkey=dykq053kvckkcy3z3l3kl61h6&dl=1';
    private $timezone;

    public function __construct() {
        $this->timezone = new DateTimeZone('Europe/Brussels');
        date_default_timezone_set('Europe/Brussels');
    }

    public function get_epg_data() {
        $cached_data = get_transient('modern_epg_data');
        if ($cached_data !== false) {
            return $cached_data;
        }

        $xml_data = $this->fetch_xml_data();
        $m3u_data = $this->fetch_m3u_data();

        if ($xml_data === false || $m3u_data === false) {
            return false;
        }

        $epg_data = $this->parse_epg_data($xml_data, $m3u_data);
        set_transient('modern_epg_data', $epg_data, HOUR_IN_SECONDS);

        return $epg_data;
    }

    private function fetch_xml_data() {
        $response = wp_remote_get($this->xml_url);
        if (is_wp_error($response)) {
            error_log('Failed to fetch XML data: ' . $response->get_error_message());
            return false;
        }
        return wp_remote_retrieve_body($response);
    }

    private function fetch_m3u_data() {
        $response = wp_remote_get($this->m3u_url);
        if (is_wp_error($response)) {
            error_log('Failed to fetch M3U data: ' . $response->get_error_message());
            return false;
        }
        return wp_remote_retrieve_body($response);
    }

    private function parse_epg_data($xml_data, $m3u_data) {
        $xml = simplexml_load_string($xml_data);
        if (!$xml) {
            error_log('Failed to parse XML data');
            return false;
        }

        $channels = $this->parse_m3u($m3u_data);
        $programs = $this->parse_xml_programs($xml);

        return [
            'channels' => $channels,
            'programs' => $programs
        ];
    }

    private function parse_m3u($m3u_content) {
        $channels = [];
        $lines = explode("\n", $m3u_content);

        foreach ($lines as $index => $line) {
            if (strpos($line, '#EXTINF:') === 0) {
                preg_match('/tvg-id="([^"]+)"/', $line, $tvg_id_match);
                preg_match('/tvg-logo="([^"]+)"/', $line, $tvg_logo_match);
                preg_match('/tvg-chno="([^"]+)"/', $line, $tvg_chno_match);

                $channel = [
                    'id' => $tvg_id_match[1] ?? '',
                    'logo' => $tvg_logo_match[1] ?? '',
                    'number' => $tvg_chno_match[1] ?? ''
                ];

                $channels[] = $channel;
            }
        }

        return $channels;
    }

    private function parse_xml_programs($xml) {
        $programs = [];
        
        foreach ($xml->programme as $programme) {
            $channel_id = (string)$programme['channel'];
            $start = $this->parse_xml_date((string)$programme['start']);
            $stop = $this->parse_xml_date((string)$programme['stop']);

            $programs[$channel_id][] = [
                'title' => (string)$programme->title,
                'sub-title' => (string)$programme->{'sub-title'},
                'desc' => (string)$programme->desc,
                'start' => $start,
                'stop' => $stop
            ];
        }

        return $programs;
    }

    private function parse_xml_date($date_string) {
        // Parse date string in format "20240818055956 +0200"
        $date = DateTime::createFromFormat('YmdHis O', $date_string, $this->timezone);
        return $date ? $date->getTimestamp() : 0;
    }
    public function switch_kodi_channel($channel_id) {
    $kodi_url = 'http://192.168.0.3:8080/jsonrpc'; // Replace with your Kodi URL
    $username = 'benoit'; // Replace with your Kodi username
    $password = '14235HOTmail'; // Replace with your Kodi password

    $command = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'Player.Open',
        'params' => [
            'item' => [
                'channelid' => (int)$channel_id
            ]
        ],
        'id' => 1
    ]);

    $response = wp_remote_post($kodi_url, [
        'body' => $command,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode("$username:$password")
        ]
    ]);

    if (is_wp_error($response)) {
        error_log('Failed to send command to Kodi: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $decoded_response = json_decode($body, true);

    if (isset($decoded_response['error'])) {
        error_log('Kodi returned an error: ' . $decoded_response['error']['message']);
        return false;
    }

    return true;
}

}