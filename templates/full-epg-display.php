<?php
if (!isset($channels) || !isset($programs) || !isset($channel_map)) {
    error_log('EPG Template: Missing required variables');
    echo '<div class="epg-error">Error: Missing required data for EPG display.</div>';
    return;
}

// Debug information
error_log('EPG Template: Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));

if (empty($channels) || empty($programs)) {
    echo '<div class="epg-error">Error: No EPG data available.</div>';
    return;
}
?>

<div id="modern-epg-container">
    <button id="epg-settings-button" class="epg-settings-button">⚙️</button>
    <?php
    echo $this->render_filter_buttons();
    echo $this->render_grid($channels, $programs, $channel_map);
    ?>
</div>

<div id="epg-settings-overlay" class="epg-settings-overlay">
    <div class="epg-settings-content">
        <h2>EPG Settings</h2>
        <button id="epg-settings-close" class="epg-settings-close">×</button>
        <form id="epg-settings-form">
            <label for="kodi_url">Kodi URL:</label>
            <input type="url" id="kodi_url" name="kodi_url" value="<?php echo esc_url(get_option('modern_epg_kodi_url', '')); ?>" required>
            
            <label for="kodi_port">Kodi Port:</label>
            <input type="number" id="kodi_port" name="kodi_port" value="<?php echo esc_attr(get_option('modern_epg_kodi_port', '8080')); ?>" required>
            
            <label for="kodi_username">Kodi Username:</label>
            <input type="text" id="kodi_username" name="kodi_username" value="<?php echo esc_attr(get_option('modern_epg_kodi_username', '')); ?>" required>
            
            <label for="kodi_password">Kodi Password:</label>
            <input type="password" id="kodi_password" name="kodi_password" required>
            
            <label for="xml_url">XML File URL:</label>
            <input type="url" id="xml_url" name="xml_url" value="<?php echo esc_url(get_option('modern_epg_xml_url', '')); ?>" required>
            
            <label for="m3u_url">M3U File URL:</label>
            <input type="url" id="m3u_url" name="m3u_url" value="<?php echo esc_url(get_option('modern_epg_m3u_url', '')); ?>" required>
            
            <button type="submit">Save Settings</button>
        </form>
    </div>
</div>

<script>
var modernEpgData = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('modern_epg_nonce'); ?>'
};

// Inline debug script
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    console.log('EPG container:', document.getElementById('modern-epg-container'));
    
    const channelLinks = document.querySelectorAll('.channel-link');
    console.log('Found ' + channelLinks.length + ' channel links');
    
    channelLinks.forEach(link => {
        console.log('Channel link:', link.dataset.channelName);
    });
});
</script>