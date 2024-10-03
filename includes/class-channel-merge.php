<?php
class Channel_Merge { // Changed from Channel_Merger to Channel_Merge
    private $kodi_connection;
    private $m3u_parser;

    public function __construct($kodi_connection, $m3u_parser) {
        $this->kodi_connection = $kodi_connection;
        $this->m3u_parser = $m3u_parser;
    }

    public function merge_channel_info($m3u_channels, $kodi_channels) {
        $merged_channels = [];

        foreach ($m3u_channels as $channel_id => $m3u_channel) {
            $kodi_channel = $this->find_matching_kodi_channel($kodi_channels, $m3u_channel);

            $merged_channel = [
                'id' => $channel_id,
                'number' => $m3u_channel['tvg_chno'] ?? '',
                'name' => $m3u_channel['name'] ?? '',
                'logo' => $m3u_channel['logo'] ?? '',
                'group' => $m3u_channel['group'] ?? 'Uncategorized',
                'kodi_id' => $kodi_channel ? $kodi_channel['channelid'] : null,
            ];

            error_log("Merged channel: " . print_r($merged_channel, true));

            $merged_channels[$channel_id] = $merged_channel;
        }

        return $merged_channels;
    }

    private function find_matching_kodi_channel($kodi_channels, $m3u_channel) {
        if (!$kodi_channels) return null;

        foreach ($kodi_channels as $kodi_channel) {
            if ($this->channels_match($kodi_channel, $m3u_channel)) {
                return $kodi_channel;
            }
        }
        return null;
    }

    private function channels_match($kodi_channel, $m3u_channel) {
        // Match by name (case-insensitive)
        if (strcasecmp($kodi_channel['label'], $m3u_channel['name']) === 0) {
            return true;
        }

        // Match by channel number
        if (isset($kodi_channel['channelnumber']) && isset($m3u_channel['tvg_chno']) &&
            $kodi_channel['channelnumber'] == $m3u_channel['tvg_chno']) {
            return true;
        }

        // Add more matching criteria if needed

        return false;
    }

    private function map_channels($kodi_channels, $m3u_channels) {
        $merged_channels = [];

        foreach ($kodi_channels as $kodi_info) {
            $best_match = null;
            $best_match_score = 0;

            foreach ($m3u_channels as $m3u_channel) {
                $score = 0;

                // Use the channel_names_match function for matching
                if ($this->channel_names_match($kodi_info['label'], $m3u_channel['id'], $m3u_channel['name'])) {
                    $score = 100;
                }

                // If channel numbers match, increase score
                if (isset($kodi_info['channelnumber']) && $kodi_info['channelnumber'] == $m3u_channel['tvg_chno']) {
                    $score += 25;
                }

                if ($score > $best_match_score) {
                    $best_match = $m3u_channel;
                    $best_match_score = $score;
                }
            }

            if ($best_match) {
                $merged_channels[] = [
                    'id' => $kodi_info['channelid'],
                    'number' => $kodi_info['channelnumber'],
                    'name' => $kodi_info['label'],
                    'chno' => $best_match['tvg_chno'],
                    'url' => $best_match['url']
                ];
            }
        }

        usort($merged_channels, function($a, $b) {
            return $a['number'] <=> $b['number'];
        });

        modern_epg_log("Final merged channels: " . print_r($merged_channels, true), 'DEBUG');
        return $merged_channels;
    }

    private function normalize_channel_name($name) {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', '', $name);
        $name = str_replace(['nl', 'be', 'uhd', '4k', 'hd', 'sd'], '', $name);
        return trim($name);
    }

    private function channel_names_match($kodi_name, $epg_id, $epg_name) {
        $kodi_name = $this->normalize_channel_name($kodi_name);
        $epg_id = $this->normalize_channel_name($epg_id);
        $epg_name = $this->normalize_channel_name($epg_name);
        
        // Exact matches
        if ($kodi_name === $epg_id || $kodi_name === $epg_name) {
            return true;
        }
        
        // Remove common prefixes/suffixes and check again
        $prefixes_suffixes = ['hd', 'sd', 'uhd', '4k', 'nl', 'be', 'uk', 'us'];
        foreach ($prefixes_suffixes as $affix) {
            $kodi_name_trimmed = trim(str_ireplace($affix, '', $kodi_name));
            $epg_id_trimmed = trim(str_ireplace($affix, '', $epg_id));
            $epg_name_trimmed = trim(str_ireplace($affix, '', $epg_name));
            
            if ($kodi_name_trimmed === $epg_id_trimmed || $kodi_name_trimmed === $epg_name_trimmed) {
                return true;
            }
        }
        
        // Partial matches
        if (strpos($kodi_name, $epg_id) !== false || strpos($epg_id, $kodi_name) !== false ||
            strpos($kodi_name, $epg_name) !== false || strpos($epg_name, $kodi_name) !== false) {
            return true;
        }
        
        return false;
    }
}

