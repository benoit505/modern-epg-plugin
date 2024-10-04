<?php

class Channel_Switcher {
    private $kodi_connection;

    public function __construct($kodi_connection) {
        $this->kodi_connection = $kodi_connection;
    }

    public function switch_channel($channel_id) {
        modern_epg_log("Attempting to switch to channel ID: $channel_id", 'DEBUG');
        
        if (!$this->kodi_connection->is_online()) {
            modern_epg_log("Cannot switch channel: Kodi is offline", 'ERROR');
            return false;
        }

        $result = $this->kodi_connection->switch_channel($channel_id);
        
        if ($result === true) {
            modern_epg_log("Successfully switched to channel ID: $channel_id", 'DEBUG');
            return true;
        } else {
            modern_epg_log("Failed to switch channel. Error: $result", 'ERROR');
            return false;
        }
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
