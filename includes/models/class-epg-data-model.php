<?php

class EPG_Data_Model {
    private $m3u_parser;
    private $xml_parser;
    private $channel_merge;
    private $kodi_connection;
    private $m3u_url;
    private $xml_url;

    public function __construct($m3u_parser, $xml_parser, $channel_merge, $kodi_connection, $m3u_url, $xml_url) {
        $this->m3u_parser = $m3u_parser;
        $this->xml_parser = $xml_parser;
        $this->channel_merge = $channel_merge;
        $this->kodi_connection = $kodi_connection;
        $this->m3u_url = $m3u_url;
        $this->xml_url = $xml_url;
    }

    public function get_epg_data() {
        modern_epg_log("Getting EPG data", 'DEBUG');
        
        try {
            $m3u_channels = $this->get_channels_from_m3u();
            modern_epg_log("M3U channels retrieved. Count: " . count($m3u_channels), 'DEBUG');
            
            $xml_programs = $this->get_programs_from_xml();
            modern_epg_log("XML programs retrieved. Count: " . count($xml_programs), 'DEBUG');
            
            if (empty($m3u_channels)) {
                throw new Exception("No channels found in M3U file");
            }
            
            if (empty($xml_programs)) {
                throw new Exception("No programs found in XML file");
            }
            
            $merged_channels = $this->channel_merge->merge_channel_info($m3u_channels);
            modern_epg_log("Channels merged. Count: " . count($merged_channels), 'DEBUG');
            
            $combined_data = [
                'channels' => $merged_channels,
                'programs' => $xml_programs
            ];
            
            return $combined_data;
        } catch (Exception $e) {
            modern_epg_log("Error getting EPG data: " . $e->getMessage(), 'ERROR');
            return [
                'channels' => [],
                'programs' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    public function switch_channel($channel_id) {
        return $this->kodi_connection->switch_channel($channel_id);
    }

    private function get_channels_from_m3u() {
        $m3u_content = $this->m3u_parser->fetch_m3u_data($this->m3u_url);
        return $this->m3u_parser->parse_content($m3u_content);
    }

    private function get_programs_from_xml() {
        $xml_content = $this->xml_parser->fetch_xml_data($this->xml_url);
        return $this->xml_parser->parse_content($xml_content);
    }
}