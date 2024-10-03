<?php
class M3U_Parser {
    public function parse_file($file_path) {
        error_log("M3U_Parser: Starting parse_file method for $file_path");
        
        $content = file_get_contents($file_path);
        if ($content === false) {
            error_log("M3U_Parser: Failed to read M3U file: $file_path");
            return [];
        }
        
        error_log("M3U_Parser: File content length: " . strlen($content));
        
        // Log the first 1000 characters of the M3U file
        $content_preview = substr($content, 0, 1000);
        error_log("M3U_Parser: Content preview (first 1000 chars): \n" . $content_preview);
        
        $channels = $this->parse_content($content);
        
        // Log a preview of the parsed channels
        $channels_preview = array_slice($channels, 0, 10, true);
        error_log("M3U_Parser: Parsed channels preview (first 10 channels): " . print_r($channels_preview, true));
        
        error_log("M3U_Parser: Finished parse_file method. Total channels parsed: " . count($channels));
        return $channels;
    }

    public function parse_content($content) {
        error_log("M3U_Parser: Starting parse_content method");
        
        // Decode HTML entities
        $content = html_entity_decode($content);

        $channels = [];
        $lines = explode("\n", $content);
        error_log("M3U file contains " . count($lines) . " lines");

        $current_channel = null;
        $channel_count = 0;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, '#EXTINF:') === 0) {
                $channel_count++;
                error_log("Parsing channel $channel_count at line $index: $line");
                
                $attributes = [];
                preg_match_all('/([a-zA-Z0-9-]+)="([^"]*)"/', $line, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $attributes[$match[1]] = $match[2];
                }
                
                if (isset($attributes['tvg-id']) && isset($attributes['tvg-chno'])) {
                    $current_channel = [
                        'id' => $attributes['tvg-id'],
                        'name' => trim(substr($line, strrpos($line, ',') + 1)),
                        'tvg_chno' => intval($attributes['tvg-chno']),
                        'group' => $attributes['group-title'] ?? 'Uncategorized',
                        'logo' => $attributes['tvg-logo'] ?? '',
                        'attributes' => $attributes
                    ];
                    
                    error_log("Channel $channel_count info - Name: {$current_channel['name']}, Number: {$current_channel['tvg_chno']}, ID: {$current_channel['id']}, Group: {$current_channel['group']}");
                } else {
                    error_log("Warning: Channel at line $index is missing tvg-id or tvg-chno");
                }
            } elseif ($current_channel !== null && (strpos($line, 'http') === 0 || strpos($line, 'rtmp') === 0)) {
                // This is the URL for the current channel
                error_log("Found URL for channel {$current_channel['name']}: $line");
                $current_channel['url'] = $line;
                $channels[$current_channel['id']] = $current_channel;
                $current_channel = null;
            }
        }

        // Sort channels by tvg_chno
        uasort($channels, function($a, $b) {
            return $a['tvg_chno'] - $b['tvg_chno'];
        });

        error_log("M3U_Parser: Finished parse_content method. Total channels parsed: " . count($channels));
        return $channels;
    }

    public function get_channel_order_from_m3u($m3u_content) {
        $channel_info = [];
        $lines = explode("\n", $m3u_content);
        foreach ($lines as $line) {
            if (strpos($line, '#EXTINF:') !== false) {
                $attributes = [];
                preg_match_all('/([a-zA-Z0-9-]+)="([^"]*)"/', $line, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $attributes[$match[1]] = $match[2];
                }
                
                if (isset($attributes['tvg-id']) && isset($attributes['tvg-chno'])) {
                    $channel_info[$attributes['tvg-id']] = [
                        'name' => trim(substr($line, strrpos($line, ',') + 1)),
                        'tvg_chno' => intval($attributes['tvg-chno']),
                        'attributes' => $attributes
                    ];
                }
            }
        }
        uasort($channel_info, function($a, $b) {
            return $a['tvg_chno'] - $b['tvg_chno'];
        });
        return $channel_info;
    }
}
