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
    private $kodi_url;
    private $username;
    private $password;

    public function __construct($kodi_url, $kodi_port, $username, $password) {
        $this->kodi_url = rtrim($kodi_url, '/') . ':' . $kodi_port . '/jsonrpc';
        $this->username = $username;
        $this->password = $password;
    }

    public function is_online() {
        $result = $this->send_request('JSONRPC.Ping');
        return ($result !== false && isset($result['result']) && $result['result'] === 'pong');
    }

    public function get_channel_order() {
        $result = $this->send_request('PVR.GetChannels', ['channelgroupid' => 'alltv']);
        
        if ($result !== false && isset($result['result']['channels']) && is_array($result['result']['channels'])) {
            modern_epg_log('Kodi channels retrieved successfully. Count: ' . count($result['result']['channels']), 'DEBUG');
            if (!empty($result['result']['channels'])) {
                modern_epg_log('Sample channel data: ' . print_r($result['result']['channels'][0], true), 'DEBUG');
            }
            return $result['result']['channels'];
        } else {
            modern_epg_log('Failed to get channel order from Kodi', 'ERROR');
            return [];
        }
    }

    public function get_channel_map() {
        $channels = $this->get_channel_order();
        $channel_map = [];
        foreach ($channels as $channel) {
            $channel_map[$channel['channelid']] = $channel;
        }
        return $channel_map;
    }

    private function build_url($method) {
        return "http://{$this->url}:{$this->port}/jsonrpc";
    }

    private function send_request($method, $params = []) {
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        ];

        $json_request = json_encode($request);
        error_log("Kodi_Connection: Sending request to Kodi: " . $json_request);

        $ch = curl_init($this->kodi_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_request)
        ]);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            error_log("Kodi_Connection: cURL error: " . curl_error($ch));
        } else {
            error_log("Kodi_Connection: HTTP response code: " . $http_code);
            error_log("Kodi_Connection: Response from Kodi: " . $result);
        }

        curl_close($ch);

        return json_decode($result, true);
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
