<?php
class XML_Parser {
    public function parse_content($xml_content) {
        $programs = [];
        
        try {
            $xml = new SimpleXMLElement($xml_content);
            
            foreach ($xml->programme as $programme) {
                $channel_id = (string)$programme['channel'];
                $start = $this->parse_xmltv_time((string)$programme['start']);
                $stop = $this->parse_xmltv_time((string)$programme['stop']);
                
                if (!isset($programs[$channel_id])) {
                    $programs[$channel_id] = [];
                }
                
                $programs[$channel_id][] = [
                    'title' => (string)$programme->title,
                    'sub-title' => (string)$programme->{'sub-title'},
                    'desc' => (string)$programme->desc,
                    'start' => $start,
                    'stop' => $stop,
                    'category' => (string)$programme->category,
                ];
            }
            
            // Comment out or remove this debug log
            // modern_epg_log("XML parsing completed. Total channels: " . count($programs), 'INFO');
        } catch (Exception $e) {
            modern_epg_log("Error parsing XML: " . $e->getMessage(), 'ERROR');
        }
        
        return $programs;
    }
    
    private function parse_xmltv_time($time_string) {
        // XMLTV time format: YYYYMMDDHHMMSS +0000
        $datetime = DateTime::createFromFormat('YmdHis O', $time_string);
        return $datetime ? $datetime->getTimestamp() : 0;
    }

    public function fetch_xml_data($xml_url) {
        // modern_epg_log("Fetching XML data from: " . $xml_url, 'DEBUG');
        $xml_content = file_get_contents($xml_url);
        if ($xml_content === false) {
            throw new Exception("Failed to fetch XML data");
        }
        return $xml_content;
    }
}
