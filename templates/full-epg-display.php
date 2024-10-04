<?php
if (!isset($epg_data['channels']) || !isset($epg_data['programs'])) {
    echo "Error: Missing required data for EPG display.";
    return;
}
?>

<?php
// modern_epg_log("Kodi online status in template: " . ($kodi_online ? 'true' : 'false'), 'DEBUG');
?>

<div id="modern-epg-wrapper" class="modern-epg" data-kodi-online="<?php echo $kodi_online ? 'true' : 'false'; ?>">
    <?php if (!$kodi_online): ?>
        <div class="kodi-offline-notice"></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="epg-error-notice"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    <?php include MODERN_EPG_PLUGIN_DIR . 'templates/filter-buttons.php'; ?>
    <?php 
    // Set variables before including the template
    $epg_grid_data = [
        'channels' => $channels,
        'programs' => $programs,
        'channel_map' => $channel_map,
        'kodi_online' => $kodi_online
    ];
    include MODERN_EPG_PLUGIN_DIR . 'templates/epg-grid.php';
    ?>
</div>