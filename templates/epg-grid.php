<?php
if (!isset($channels) || !isset($programs) || !isset($channel_map)) {
    echo "Error: Missing required data for EPG display.";
    return;
}

modern_epg_log("Total channels: " . count($channels), 'DEBUG');
modern_epg_log("Total programs: " . count($programs), 'DEBUG');

$current_time = time();
$start_time = strtotime('-30 minutes', $current_time) - (strtotime('-30 minutes', $current_time) % 1800);
$end_time = $start_time + (3 * 60 * 60);

modern_epg_log("EPG Grid - Sample channels data: " . print_r(array_slice($channels, 0, 5, true), true), 'DEBUG');

function calculate_left_position($program_start, $grid_start) {
    return max(0, (($program_start - $grid_start) / 60) / (3 * 60) * 100);
}

function calculate_width($program_start, $program_end, $grid_start, $grid_end) {
    $start = max($program_start, $grid_start);
    $end = min($program_end, $grid_end);
    return (($end - $start) / 60) / (3 * 60) * 100;
}
?>

<div class="epg-container" data-kodi-online="<?php echo $kodi_online ? 'true' : 'false'; ?>">
    <div class="epg" id="epg-container">
        <?php foreach ($channels as $channel_id => $channel): 
            $channel_programs = $programs[$channel['id']] ?? [];
            $kodi_channel_id = $channel['kodi_id'] ?? '';
            $channel_group = $channel['group'] ?? 'Uncategorized';
            $channel_number = $channel['number'] ?? '';
            modern_epg_log("Channel {$channel_id} Group: {$channel_group}", 'DEBUG');
        ?>
            <div class="channel" 
                 data-channel-number="<?php echo esc_attr($channel_number); ?>" 
                 data-group="<?php echo esc_attr($channel_group); ?>">
                <div class="channel-info">
                    <a href="#" class="channel-link <?php echo $kodi_online ? '' : 'disabled'; ?>" 
                       data-kodi-channel-id="<?php echo esc_attr($kodi_channel_id); ?>"
                       data-channel-name="<?php echo esc_attr($channel['name'] ?? 'Unknown Channel'); ?>">
                        <?php if (!empty($channel['logo'])): ?>
                            <img class="channel-logo" 
                                 src="<?php echo esc_url($channel['logo']); ?>" 
                                 alt="Channel <?php echo esc_attr($channel_number); ?>">
                        <?php else: ?>
                            <div class="channel-logo-placeholder">
                                <?php echo esc_html($channel_number); ?>
                            </div>
                        <?php endif; ?>
                        <div class="channel-name"><?php echo esc_html($channel['name'] ?? 'Unknown Channel'); ?></div>
                    </a>
                </div>
                <div class="programme-list-container">
                    <div class="programme-list">
                        <?php 
                        $program_count = 0;
                        foreach ($channel_programs as $program):
                            $program_start = $program['start'] ?? null;
                            $program_end = $program['stop'] ?? null;
                            
                            if ($program_start === null || $program_end === null) {
                                modern_epg_log('Invalid program data for channel ' . $channel_id . ': ' . print_r($program, true), 'ERROR');
                                continue;
                            }
                            
                            if ($program_end < $start_time || $program_start > $end_time) continue;
                            
                            $program_count++;
?>
                            <div class="programme <?php echo ($current_time >= $program_start && $current_time < $program_end) ? 'current-program' : ''; ?>" 
                                 data-start-time="<?php echo date('Y-m-d\TH:i:sP', $program_start); ?>"
                                 data-end-time="<?php echo date('Y-m-d\TH:i:sP', $program_end); ?>"
                                 data-title="<?php echo esc_attr($program['title'] ?? 'Unknown Program'); ?>"
                                 data-description="<?php echo esc_attr($program['desc'] ?? ''); ?>"
                                 style="left: <?php echo calculate_left_position($program_start, $start_time); ?>%;
                                        width: <?php echo calculate_width($program_start, $program_end, $start_time, $end_time); ?>%;">
                                <div class="programme-time">
                                    <?php echo date('H:i', $program_start) . ' - ' . date('H:i', $program_end); ?>
                                </div>
                                <div class="programme-title"><?php echo esc_html($program['title'] ?? 'Unknown Program'); ?></div>
                                <?php if (!empty($program['sub-title'])): ?>
                                    <div class="programme-subtitle"><?php echo esc_html($program['sub-title']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($program['category'])): ?>
                                    <div class="programme-category"><?php echo esc_html($program['category']); ?></div>
                                <?php endif; ?>
                            </div>
<?php endforeach; 
echo "<!-- Debug: Programs displayed for channel {$channel['name']}: {$program_count} -->\n";
?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$debug_program = reset($channel_programs);
if ($debug_program) {
    echo "<!-- Debug: Sample program data\n";
    echo "Title: " . esc_html($debug_program['title'] ?? 'Unknown') . "\n";
    echo "Start: " . date('Y-m-d H:i:s', $debug_program['start'] ?? 0) . "\n";
    echo "End: " . date('Y-m-d H:i:s', $debug_program['stop'] ?? 0) . "\n";
    echo "Description: " . esc_html(substr($debug_program['desc'] ?? '', 0, 100)) . "...\n";
    echo "-->\n";
}
?>
