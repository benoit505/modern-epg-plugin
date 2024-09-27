<?php

class EPG_Data_Model {
    private $xml_url = 'https://www.dropbox.com/scl/fi/olnk1zwz8pnugmdsl1457/live.xml?rlkey=dpk6ves6ok2ar4oyowtv8ay6j&dl=1';
    private $m3u_url = 'https://www.dropbox.com/scl/fi/hftp0fhhm55ahtudtzgs2/live.m3u?rlkey=dykq053kvckkcy3z3l3kl61h6&dl=1';
    private $timezone;
    private $kodi_url;
    private $kodi_port;
    private $kodi_username;
    private $kodi_password;
    private $last_error = '';

    public function __construct() {
        $this->timezone = new DateTimeZone('Europe/Brussels');
        date_default_timezone_set('Europe/Brussels');
        $this->kodi_url = get_option('modern_epg_kodi_url', 'http://192.168.0.3');
        $this->kodi_port = get_option('modern_epg_kodi_port', '8080');
        $this->kodi_username = get_option('modern_epg_kodi_username', '');
        $this->kodi_password = get_option('modern_epg_kodi_password', '');
    }

    public function get_epg_data() {
        $cached_epg_data = get_transient('modern_epg_data');
        $cached_kodi_mapping = get_transient('modern_kodi_channel_mapping');
        $current_time = time();

        if ($cached_epg_data === false) {
            $xml_data = $this->fetch_xml_data();
            $m3u_data = $this->fetch_m3u_data();
            
            if ($xml_data === false || $m3u_data === false) {
                error_log("Failed to fetch XML or M3U data");
                return false;
            }

            $epg_data = $this->parse_epg_data($xml_data, $m3u_data);
            $channels_with_groups = $this->parse_m3u($m3u_data);

            // Merge group information into EPG data
            foreach ($epg_data['channels'] as &$channel) {
                $matching_channel = array_filter($channels_with_groups, function($c) use ($channel) {
                    return $c['id'] === $channel['id'];
                });
                if (!empty($matching_channel)) {
                    $matching_channel = reset($matching_channel);
                    $channel['group'] = $matching_channel['group'];
                } else {
                    $channel['group'] = 'Uncategorized';
                }
            }

            $epg_data['last_updated'] = $current_time;
            set_transient('modern_epg_data', $epg_data, HOUR_IN_SECONDS);
        } else {
            $epg_data = $cached_epg_data;
        }

        // Always fetch fresh Kodi channel data
        $kodi_channels = $this->get_kodi_channels();
        if ($kodi_channels !== false) {
            $channel_map = $this->map_kodi_channels_to_epg($kodi_channels, $epg_data['channels']);
            $epg_data['channel_map'] = $channel_map;
            
            // Cache the Kodi channel mapping separately
            set_transient('modern_kodi_channel_mapping', $channel_map, 5 * MINUTE_IN_SECONDS);
        } else {
            $epg_data['channel_map'] = $cached_kodi_mapping !== false ? $cached_kodi_mapping : [];
        }

        return $epg_data;
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
            'programs' => $programs,
        ];
    }

    private function parse_m3u($m3u_content) {
        $channels = [];
        $lines = explode("\n", $m3u_content);

        foreach ($lines as $line) {
            if (strpos($line, '#EXTINF:') === 0) {
                preg_match('/tvg-id="([^"]*)"/', $line, $id_match);
                preg_match('/tvg-name="([^"]*)"/', $line, $name_match);
                preg_match('/tvg-logo="([^"]*)"/', $line, $logo_match);
                preg_match('/tvg-chno="([^"]*)"/', $line, $chno_match);
                preg_match('/group-title="([^"]*)"/', $line, $group_match);
                
                $channel = [
                    'id' => $id_match[1] ?? '',
                    'name' => $name_match[1] ?? '',
                    'logo' => $logo_match[1] ?? '',
                    'number' => intval($chno_match[1] ?? '0'),
                    'group' => $group_match[1] ?? 'Uncategorized'
                ];
                
                $channels[] = $channel;
            }
        }

        return $channels;
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

    private function parse_xml_date($date_string) {
        // Parse date string in format "20240818055956 +0200"
        $date = DateTime::createFromFormat('YmdHis O', $date_string, $this->timezone);
        return $date ? $date->getTimestamp() : 0;
    }

    public function check_kodi_connection() {
        $kodi_url = 'http://192.168.0.3:8080/jsonrpc'; // Replace with your Kodi URL
        $username = 'benoit'; // Replace with your Kodi username
        $password = '14235HOTmail'; // Replace with your Kodi password

        $command = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'JSONRPC.Ping',
            'id' => 1
        ]);

        $log_file = MODERN_EPG_PLUGIN_DIR . 'logs/kodi_connection.log';

        error_log("Attempting to ping Kodi...\n", 3, $log_file);

        $response = wp_remote_post($kodi_url, [
            'body' => $command,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            ],
            'timeout' => 5, // 5 second timeout for ping
        ]);

        if (is_wp_error($response)) {
            error_log("Failed to connect to Kodi: " . $response->get_error_message() . "\n", 3, $log_file);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($body, true);

        error_log("Kodi ping response: " . print_r($decoded_response, true) . "\n", 3, $log_file);

        if (isset($decoded_response['result']) && $decoded_response['result'] === 'pong') {
            error_log("Successfully pinged Kodi\n", 3, $log_file);
            return true;
        } else {
            error_log("Failed to ping Kodi. Unexpected response.\n", 3, $log_file);
            return false;
        }
    }

    public function get_kodi_channels() {
        $kodi_url = 'http://192.168.0.3:8080/jsonrpc'; // Replace with your Kodi URL
        $username = 'benoit'; // Replace with your Kodi username
        $password = '14235HOTmail'; // Replace with your Kodi password

        $command = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'PVR.GetChannels',
            'params' => [
                'channelgroupid' => 'alltv'
            ],
            'id' => 1
        ]);

        $response = wp_remote_post($kodi_url, [
            'body' => $command,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('Failed to connect to Kodi: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to decode Kodi response: ' . json_last_error_msg());
            return false;
        }

        if (isset($decoded_response['result']['channels'])) {
            return $decoded_response['result']['channels'];
        } elseif (isset($decoded_response['result'])) {
            return $decoded_response['result'];
        } else {
            error_log('Unexpected Kodi response format');
            return false;
        }
    }
    
    private function map_kodi_channels_to_epg($kodi_channels, $epg_channels) {
        $channel_map = [];
        foreach ($epg_channels as $epg_channel) {
            $epg_name = strtolower($epg_channel['name']);
            $epg_number = $epg_channel['number'];
            $best_match = null;
            $best_match_score = 0;

            foreach ($kodi_channels as $kodi_channel) {
                $kodi_name = strtolower($kodi_channel['label']);
                $score = 0;

                // Exact match
                if ($epg_name === $kodi_name) {
                    $score = 100;
                } 
                // Partial match
                elseif (strpos($kodi_name, $epg_name) !== false || strpos($epg_name, $kodi_name) !== false) {
                    $score = 50;
                }

                // If channel numbers match, increase score
                if (isset($kodi_channel['channelnumber']) && $kodi_channel['channelnumber'] == $epg_number) {
                    $score += 25;
                }

                if ($score > $best_match_score) {
                    $best_match = $kodi_channel;
                    $best_match_score = $score;
                }
            }

            if ($best_match) {
                $channel_map[$epg_number] = [
                    'kodi_channelid' => $best_match['channelid'],
                    'epg_number' => $epg_number,
                    'name' => $epg_channel['name']
                ];
            }
        }
        return $channel_map;
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
        
        // Partial matches (be cautious with this, might cause false positives)
        if (strpos($kodi_name, $epg_id) !== false || strpos($epg_id, $kodi_name) !== false ||
            strpos($kodi_name, $epg_name) !== false || strpos($epg_name, $kodi_name) !== false) {
            return true;
        }
        
        return false;
    }

    public function switch_kodi_channel($channel) {
        $kodi_url = 'http://192.168.0.3:8080/jsonrpc'; // Replace with your Kodi URL
        $username = 'benoit'; // Replace with your Kodi username
        $password = '14235HOTmail'; // Replace with your Kodi password

        // Determine if $channel is an ID or a name
        $is_channel_id = is_numeric($channel);

        $command = json_encode([
            'jsonrpc' => '2.0',
            'method' => $is_channel_id ? 'Player.Open' : 'GUI.ActivateWindow',
            'params' => $is_channel_id 
                ? ['item' => ['channelid' => intval($channel)]]
                : ['window' => 'tvchannels', 'parameters' => ["channel=$channel"]],
            'id' => 1,
        ]);

        $response = wp_remote_post($kodi_url, [
            'body' => $command,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            ],
            'timeout' => 10,
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

    public function get_current_kodi_channel() {
        $command = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'Player.GetItem',
            'params' => [
                'properties' => ['channeltype', 'channelnumber', 'channel'],
                'playerid' => 1
            ],
            'id' => 'getCurrentChannel'
        ]);

        $response = $this->send_kodi_command($command);
        if ($response === false) {
            return false;
        }

        $decoded_response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->last_error = 'Failed to decode Kodi response: ' . json_last_error_msg() . '. Raw response: ' . substr($response, 0, 100) . '...';
            return false;
        }

        if (isset($decoded_response['result']['item']['channelnumber'])) {
            return $decoded_response['result']['item']['channelnumber'];
        } else {
            $this->last_error = 'Channel number not found in Kodi response. Response: ' . json_encode($decoded_response);
            return false;
        }
    }

    private function send_kodi_command($command) {
        $url = "{$this->kodi_url}:{$this->kodi_port}/jsonrpc";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("{$this->kodi_username}:{$this->kodi_password}")
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $command);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->last_error = 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        // Log the full raw response for debugging
        error_log('Full Raw Kodi response: ' . $response);

        return $response;
    }

    public function get_last_error() {
        return $this->last_error;
    }
}
