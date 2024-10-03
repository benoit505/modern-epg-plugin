<?php

class EPG_Data_Model {
    private $xml_url;
    private $m3u_url;
    private $timezone;
    private $last_error = '';
    private $kodi_connection;
    private $m3u_parser;
    private $channel_merge;

    public function __construct($kodi_connection, $m3u_parser, $channel_merge, $m3u_url, $xml_url) {
        $this->kodi_connection = $kodi_connection;
        $this->m3u_parser = $m3u_parser;
        $this->channel_merge = $channel_merge;
        $this->m3u_url = $m3u_url;
        $this->xml_url = $xml_url;
        $this->timezone = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
    }

    public function get_epg_data() {
        modern_epg_log('Starting to fetch EPG data', 'DEBUG');
        
        try {
            $xml_content = $this->fetch_xml_data();
            $m3u_content = $this->fetch_m3u_data();

            if ($xml_content === false || $m3u_content === false) {
                throw new Exception('Failed to fetch EPG or M3U data');
            }

            $epg_data = $this->parse_epg_data($xml_content, $m3u_content);

            // Get Kodi channels (we'll use this for additional information, not as primary source)
            $kodi_channels = $this->kodi_connection->get_channel_order();
            
            if ($kodi_channels === false) {
                modern_epg_log('Failed to retrieve Kodi channels, proceeding with M3U data only', 'WARNING');
            } else {
                modern_epg_log('Kodi channels retrieved. Count: ' . count($kodi_channels), 'DEBUG');
            }

            // Merge channel information, prioritizing M3U data
            $merged_channels = $this->channel_merge->merge_channel_info($epg_data['channels'], $kodi_channels);

            $result = [
                'channels' => $merged_channels,
                'programs' => $epg_data['programs'],
                'channel_map' => $this->kodi_connection->get_channel_map(),
            ];

            // Add debug logging here
            error_log("EPG Data Model - Sample of merged channels:");
            $sample_channels = array_slice($result['channels'], 0, 5, true);
            error_log(print_r($sample_channels, true));

            modern_epg_log('EPG data fetched and merged successfully', 'INFO');
            modern_epg_log('Channels count: ' . count($result['channels']), 'DEBUG');
            modern_epg_log('Programs count: ' . count($result['programs']), 'DEBUG');
            modern_epg_log('Channel map count: ' . count($result['channel_map']), 'DEBUG');
            
            if (!empty($result['channels'])) {
                modern_epg_log('Sample merged channel data: ' . print_r(reset($result['channels']), true), 'DEBUG');
            }

            return $result;
        } catch (Exception $e) {
            $this->last_error = 'Error fetching EPG data: ' . $e->getMessage();
            modern_epg_log($this->last_error, 'ERROR');
            return false;
        }
    }

    private function parse_epg_data($xml_data, $m3u_content) {
        $xml = simplexml_load_string($xml_data);
        if (!$xml) {
            throw new Exception('Failed to parse XML data');
        }

        $m3u_parser = new M3U_Parser();
        $channels = $m3u_parser->parse_content($m3u_content);

        // Log the first few channels to check if group information is present
        $channel_sample = array_slice($channels, 0, 5, true);
        error_log("Sample of parsed channels: " . print_r($channel_sample, true));

        $programs = [];

        // Parse programs from XML data
        foreach ($xml->programme as $programme) {
            $channel_id = (string) $programme['channel'];
            $start = $this->parse_xml_date((string) $programme['start']);
            $stop = $this->parse_xml_date((string) $programme['stop']);

            if (!isset($programs[$channel_id])) {
                $programs[$channel_id] = [];
            }

            $programs[$channel_id][] = [
                'title' => (string) $programme->title,
                'sub-title' => (string) $programme->{'sub-title'},
                'desc' => (string) $programme->desc,
                'start' => $start,
                'stop' => $stop,
                'category' => (string) $programme->category,
            ];
        }

        modern_epg_log('Parsed ' . count($channels) . ' channels and ' . count($programs) . ' program lists', 'DEBUG');

        return [
            'channels' => $channels,
            'programs' => $programs,
        ];
    }

    private function parse_xml_programs($xml) {
        $programs = [];

        foreach ($xml->programme as $programme) {
            $channel_id = (string) $programme['channel'];
            $start = $this->parse_xml_date((string) $programme['start']);
            $stop = $this->parse_xml_date((string) $programme['stop']);

            $programs[$channel_id][] = [
                'title' => (string) $programme->title,
                'sub-title' => (string) $programme->{'sub-title'},
                'desc' => (string) $programme->desc,
                'start' => $start,
                'stop' => $stop,
            ];
        }

        return $programs;
    }

    private function fetch_xml_data() {
        modern_epg_log("Fetching XML data from: {$this->xml_url}", 'DEBUG');
        $response = wp_remote_get($this->xml_url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch XML data: ' . $response->get_error_message());
        }
        return wp_remote_retrieve_body($response);
    }

    private function fetch_m3u_data() {
        modern_epg_log("Fetching M3U data from: {$this->m3u_url}", 'DEBUG');
        $response = wp_remote_get($this->m3u_url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch M3U data: ' . $response->get_error_message());
        }
        return wp_remote_retrieve_body($response);
    }

    private function parse_xml_date($date_string) {
        $date = DateTime::createFromFormat('YmdHis O', $date_string, $this->timezone);
        return $date ? $date->getTimestamp() : 0;
    }

    public function get_last_error() {
        return $this->last_error;
    }

    public function get_channels() {
        return $this->kodi_connection->get_channel_order();
    }

    public function get_programs() {
        $epg_data = $this->get_epg_data();
        return $epg_data['programs'] ?? [];
    }

    public function get_channel_map() {
        return $this->kodi_connection->get_channel_map();
    }
}

