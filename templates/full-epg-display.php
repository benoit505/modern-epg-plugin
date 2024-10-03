<?php
if (!isset($channels) || !isset($programs) || !isset($channel_map)) {
    echo "Error: Missing required data for EPG display.";
    return;
}
?>

<div id="modern-epg-container" class="modern-epg">
    <?php include MODERN_EPG_PLUGIN_DIR . 'templates/filter-buttons.php'; ?>
    <?php include MODERN_EPG_PLUGIN_DIR . 'templates/epg-grid.php'; ?>
</div>