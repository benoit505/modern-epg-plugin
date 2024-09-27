<?php
// Remove or comment out these debug logs
// error_log('Channels: ' . print_r($channels, true));
// error_log('Programs: ' . print_r($programs, true));
// error_log('Channel Map: ' . print_r($channel_map, true));
?>

<?php
// Start of epg-display.php
if (!isset($channels) || !isset($programs) || !isset($channel_map)) {
    echo "Error: Missing required data for EPG display.";
    return;
}

// Remove or comment out this debug log
// if (!empty($channels) && !empty($programs)) {
//     $first_channel_id = $channels[0]['id'] ?? null;
//     if ($first_channel_id && !empty($programs[$first_channel_id])) {
//         error_log('First program structure: ' . print_r($programs[$first_channel_id][0], true));
//     }
// }
?>

<div class="epg-container" id="modern-epg-container">
    <div class="epg" id="epg-container">
        <?php
        $current_time = time();
        // Start 30 minutes before the current time, rounded down to the nearest half hour
        $start_time = strtotime('-30 minutes', $current_time);
        $start_time = $start_time - ($start_time % 1800); // Round down to nearest half hour
        $end_time = $start_time + (3 * 60 * 60); // 3 hours from start time
        
        error_log('Number of channels: ' . count($channels));
        foreach ($channels as $channel): 
            $channel_programs = $programs[$channel['id']] ?? [];
            $kodi_channel_id = $channel_map[$channel['number']]['kodi_channelid'] ?? '';
            
            error_log('Channel: ' . $channel['name'] . ', Number of programs: ' . count($channel_programs));
        ?>
            <div class="channel" 
                 data-channel-number="<?php echo esc_attr($channel['number']); ?>" 
                 data-group="<?php echo esc_attr($channel['group'] ?? 'Uncategorized'); ?>">
                <div class="channel-info">
                    <a href="#" class="channel-link" 
                       data-kodi-channel-id="<?php echo esc_attr($kodi_channel_id); ?>"
                       data-channel-name="<?php echo esc_attr($channel['name']); ?>">
                        <img class="channel-logo" 
                             src="<?php echo esc_url($channel['logo'] ?? ''); ?>" 
                             alt="Channel <?php echo esc_attr($channel['number'] ?? ''); ?>">
                    </a>
                </div>
                <div class="programme-list-container">
                    <div class="programme-list">
                        <?php 
                        $program_count = 0;
                        foreach ($channel_programs as $program): 
                            $program_start = isset($program['start']) ? $program['start'] : null;
                            $program_end = isset($program['stop']) ? $program['stop'] : null;
                            
                            if ($program_start === null || $program_end === null) {
                                error_log('Invalid program data: ' . print_r($program, true));
                                continue;
                            }
                            
                            if ($program_end < $start_time || $program_start > $end_time) continue;
                            
                            $program_count++;
                        ?>
                            <div class="programme <?php echo ($current_time >= $program_start && $current_time < $program_end) ? 'current-program' : ''; ?>" 
                                 data-start-time="<?php echo date('c', $program_start); ?>"
                                 data-end-time="<?php echo date('c', $program_end); ?>"
                                 data-title="<?php echo esc_attr($program['title'] ?? ''); ?>"
                                 data-description="<?php echo esc_attr($program['desc'] ?? ''); ?>"
                                 style="left: <?php echo calculate_left_position($program_start, $start_time); ?>%;
                                        width: <?php echo calculate_width($program_start, $program_end, $start_time, $end_time); ?>%;">
                                <div class="programme-time">
                                    <?php echo date('H:i', $program_start) . ' – ' . date('H:i', $program_end); ?>
                                </div>
                                <div class="programme-title"><?php echo esc_html($program['title'] ?? 'No Title'); ?></div>
                            </div>
                        <?php endforeach; 
                        error_log('Programs displayed for channel ' . $channel['name'] . ': ' . $program_count);
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
function calculate_left_position($program_start, $grid_start) {
    $minutes_from_start = ($program_start - $grid_start) / 60;
    return max(0, ($minutes_from_start / (3 * 60)) * 100); // 3 hours, ensure non-negative
}

function calculate_width($program_start, $program_end, $grid_start, $grid_end) {
    $start = max($program_start, $grid_start);
    $end = min($program_end, $grid_end);
    $duration_minutes = ($end - $start) / 60;
    return ($duration_minutes / (3 * 60)) * 100; // 3 hours
}
?>