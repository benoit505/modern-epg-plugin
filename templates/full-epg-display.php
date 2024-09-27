<div id="modern-epg-container">
    <?php
    if (empty($channels) || empty($programs) || empty($channel_map)) {
        echo "Error: Missing EPG data";
        error_log('EPG Template: Missing data - Channels: ' . count($channels) . ', Programs: ' . count($programs) . ', Channel Map: ' . count($channel_map));
    } else {
        echo $this->render_filter_buttons();
        echo $this->render_grid($channels, $programs, $channel_map);
    }
    ?>
</div>

<script>
var modernEpgData = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('modern_epg_nonce'); ?>',
    currentGroup: '<?php echo esc_js($current_group); ?>'
};

// Inline debug script
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    const channelLinks = document.querySelectorAll('.channel-link');
    console.log('Found ' + channelLinks.length + ' channel links');
    
    channelLinks.forEach(link => {
        console.log('Adding click listener to:', link.dataset.channelName);
        link.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Channel link clicked:', this.dataset.channelName, 'Kodi Channel ID:', this.dataset.kodiChannelId);
        });
    });
});
</script>