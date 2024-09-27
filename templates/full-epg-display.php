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
    <?php
    echo $this->render_filter_buttons();
    echo $this->render_grid($channels, $programs, $channel_map);
    ?>
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