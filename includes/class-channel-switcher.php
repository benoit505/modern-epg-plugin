<?php

class Channel_Switcher {
    private $kodi_connection;

    public function __construct($kodi_connection) {
        $this->kodi_connection = $kodi_connection;
    }

    public function switch_channel($channel) {
        modern_epg_log("Attempting to switch to channel: $channel", 'DEBUG');
        
        if (!$this->kodi_connection->is_online()) {
            modern_epg_log("Cannot switch channel: Kodi is offline", 'ERROR');
            return false;
        }

        // Determine if $channel is an ID or a name
        $is_channel_id = is_numeric($channel);
        $params = $is_channel_id 
            ? ['item' => ['channelid' => intval($channel)]]
            : ['window' => 'tvchannels', 'parameters' => ["channel=$channel"]];

        $method = $is_channel_id ? 'Player.Open' : 'GUI.ActivateWindow';
        $response = $this->kodi_connection->send_jsonrpc_request($method, $params);
        
        if ($response === false) {
            modern_epg_log("Failed to switch channel. Response: " . print_r($response, true), 'ERROR');
            return false;
        }
        
        modern_epg_log("Channel switch response: " . json_encode($response), 'DEBUG');
        return isset($response['result']) && $response['result'] === 'OK';
    }

    public function get_channel_id_by_name($channel_name) {
        modern_epg_log("Attempting to get channel ID for: $channel_name", 'DEBUG');
        
        $response = $this->kodi_connection->send_jsonrpc_request('PVR.GetChannels', ['channelgroupid' => 'alltv']);
        
        if ($response === false || !isset($response['result']['channels'])) {
            modern_epg_log("Failed to get channel list", 'ERROR');
            return false;
        }
        
        foreach ($response['result']['channels'] as $channel) {
            if (strtolower($channel['label']) === strtolower($channel_name)) {
                modern_epg_log("Found channel ID {$channel['channelid']} for $channel_name", 'DEBUG');
                return $channel['channelid'];
            }
        }
        
        modern_epg_log("Channel not found: $channel_name", 'WARNING');
        return false;
    }
}
