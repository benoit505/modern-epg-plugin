<?php
/**
 * Kodi Connection Class
 *
 * @package Modern_EPG_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if (!function_exists('modern_epg_log')) {
    require_once plugin_dir_path(__FILE__) . 'modern-epg-helpers.php';
}

class Kodi_Connection {
    private $ip;
    private $port;
    private $username;
    private $password;
    private $connection_timeout = 5; // Reduced timeout to 5 seconds
    private $last_check_time = 0;
    private $check_interval = 600; // 10 minutes in seconds

    public function __construct($ip, $port, $username, $password) {
        $this->ip = $ip;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function is_online() {
        $current_time = time();
        $cached_status = get_transient('kodi_connection_status');

        if ($cached_status !== false && ($current_time - $this->last_check_time) < $this->check_interval) {
            return $cached_status === 'online';
        }

        $this->last_check_time = $current_time;

        try {
            $response = $this->send_request('JSONRPC.Ping', [], true);
            $is_online = isset($response['result']) && $response['result'] === 'pong';
            set_transient('kodi_connection_status', $is_online ? 'online' : 'offline', $this->check_interval);
            return $is_online;
        } catch (Exception $e) {
            modern_epg_log("Kodi connection check failed: " . $e->getMessage(), 'WARNING');
            set_transient('kodi_connection_status', 'offline', $this->check_interval);
            return false;
        }
    }

    public function send_request($method, $params = [], $ignore_offline = false) {
        if (!$ignore_offline && !$this->is_online()) {
            throw new Exception("Kodi is offline");
        }

        $url = "http://{$this->ip}:{$this->port}/jsonrpc";
        $data = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->connection_timeout);

        $response = curl_exec($ch);
        curl_close($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $result = json_decode($response, true);

        if (isset($result['error'])) {
            throw new Exception($result['error']['message']);
        }

        return $result;
    }

    public function get_channel_order() {
        try {
            $response = $this->send_request('PVR.GetChannels', ['channelgroupid' => 'alltv']);
            if (isset($response['result']['channels'])) {
                return $response['result']['channels'];
            }
        } catch (Exception $e) {
            modern_epg_log("Failed to get channel order from Kodi: " . $e->getMessage(), 'WARNING');
        }
        return [];
    }

    public function get_channel_map() {
        try {
            $channels = $this->get_channel_order();
            $channel_map = [];
            foreach ($channels as $channel) {
                $channel_map[$channel['channelid']] = $channel['label'];
            }
            return $channel_map;
        } catch (Exception $e) {
            modern_epg_log("Failed to get channel map from Kodi: " . $e->getMessage(), 'WARNING');
            return [];
        }
    }

    public function switch_channel($channel_id) {
        error_log("Kodi_Connection: Attempting to switch to channel ID: " . $channel_id);
        
        $method = 'Player.Open';
        $params = [
            'item' => [
                'channelid' => intval($channel_id)
            ]
        ];

        $response = $this->send_request($method, $params);
        error_log("Kodi_Connection: Raw response from Kodi: " . print_r($response, true));

        if (isset($response['result']) && $response['result'] === 'OK') {
            error_log("Kodi_Connection: Channel switched successfully to ID: " . $channel_id);
            return true;
        } else {
            $error_message = 'Failed to switch channel: ' . json_encode($response);
            error_log("Kodi_Connection: " . $error_message);
            return $error_message;
        }
    }
}
