<?php
class M3U_Parser {
    public function parse_file($file_path) {
        modern_epg_log("Starting parse_file method for $file_path", 'DEBUG');
        
        $content = @file_get_contents($file_path);
        if ($content === false) {
            modern_epg_log("Failed to read M3U file: $file_path", 'ERROR');
            return [];
        }
        
        modern_epg_log("File content length: " . strlen($content), 'DEBUG');
        
        // Log the first 500 characters of the M3U file
        $content_preview = substr($content, 0, 500);
        modern_epg_log("Content preview (first 500 chars): \n" . $content_preview, 'DEBUG');
        
        $channels = $this->parse_content($content);
        
        // Log a preview of the parsed channels
        $channels_preview = array_slice($channels, 0, 5, true);
        modern_epg_log("Parsed channels preview (first 5 channels): " . print_r($channels_preview, true), 'DEBUG');
        
        modern_epg_log("Finished parse_file method. Total channels parsed: " . count($channels), 'INFO');
        return $channels;
    }

    public function parse_content($content) {
        modern_epg_log("Starting parse_content method", 'DEBUG');
        
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $channels = [];
        $lines = explode("\n", $content);
        modern_epg_log("M3U file contains " . count($lines) . " lines", 'DEBUG');

        $current_channel = null;
        $channel_count = 0;

        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, '#EXTINF:') === 0) {
                $channel_count++;
                // // Commented out verbose debug log
                // modern_epg_log("Parsing channel $channel_count at line $line_number: $line", 'DEBUG');
                
                $attributes = $this->parse_attributes($line);
                $current_channel = $this->create_channel_from_attributes($attributes, $line, $line_number);
            } elseif (strpos($line, 'http') === 0) {
                // // Commented out verbose debug log
                // modern_epg_log("Found URL for channel {$current_channel['name']}: $line", 'DEBUG');
                
                $current_channel['url'] = $line;
                $channels[$current_channel['id']] = $current_channel;
                $current_channel = null;
            }
        }

        $channels = $this->sort_channels($channels);

        modern_epg_log("Finished parse_content method. Total channels parsed: " . count($channels), 'INFO');
        return $channels;
    }

    private function parse_attributes($line) {
        $attributes = [];
        preg_match_all('/([a-zA-Z0-9-]+)="([^"]*)"/', $line, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        return $attributes;
    }

    private function create_channel_from_attributes($attributes, $line, $index) {
        if (isset($attributes['tvg-id']) && isset($attributes['tvg-chno'])) {
            return [
                'id' => $attributes['tvg-id'],
                'name' => trim(substr($line, strrpos($line, ',') + 1)),
                'tvg_chno' => intval($attributes['tvg-chno']),
                'group' => $attributes['group-title'] ?? 'Uncategorized',
                'logo' => $attributes['tvg-logo'] ?? '',
                'attributes' => $attributes
            ];
        } else {
            modern_epg_log("Warning: Channel at line $index is missing tvg-id or tvg-chno", 'WARNING');
            return null;
        }
    }

    private function is_valid_url($url) {
        return strpos($url, 'http') === 0 || strpos($url, 'rtmp') === 0;
    }

    private function sort_channels($channels) {
        uasort($channels, function($a, $b) {
            return $a['tvg_chno'] - $b['tvg_chno'];
        });
        return $channels;
    }

    public function get_channel_order_from_m3u($m3u_content) {
        $channels = $this->parse_content($m3u_content);
        $channel_order = [];
        foreach ($channels as $id => $channel) {
            $channel_order[$id] = [
                'name' => $channel['name'],
                'tvg_chno' => $channel['tvg_chno'],
                'attributes' => $channel['attributes']
            ];
        }
        return $channel_order;
    }

    // Add this method to the class
    public function fetch_m3u_data($url) {
        modern_epg_log("Fetching M3U data from: " . $url, 'DEBUG');
        $content = file_get_contents($url);
        if ($content === false) {
            throw new Exception("Failed to fetch M3U data from URL: " . $url);
        }
        return $content;
    }
}
