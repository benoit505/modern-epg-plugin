<?php
if (!isset($channels) || !isset($programs) || !isset($channel_map)) {
    echo "Error: Missing required data for EPG display.";
    return;
}

// Debug information
echo "<!-- Debug: Total channels: " . count($channels) . " -->\n";
echo "<!-- Debug: Total programs: " . count($programs) . " -->\n";

$current_time = time();
// Start 30 minutes before the current time, rounded down to the nearest half hour
$start_time = strtotime('-30 minutes', $current_time);
$start_time = $start_time - ($start_time % 1800); // Round down to nearest half hour
$end_time = $start_time + (3 * 60 * 60); // 3 hours from start time

// At the beginning of the file
error_log("EPG Grid - Received channels data:");
$sample_channels = array_slice($channels, 0, 5, true);
error_log(print_r($sample_channels, true));

function calculate_left_position($program_start, $grid_start) {
    $minutes_from_start = ($program_start - $grid_start) / 60;
    return max(0, ($minutes_from_start / (3 * 60)) * 100);
}

function calculate_width($program_start, $program_end, $grid_start, $grid_end) {
    $start = max($program_start, $grid_start);
    $end = min($program_end, $grid_end);
    $duration_minutes = ($end - $start) / 60;
    return ($duration_minutes / (3 * 60)) * 100;
}
?>

<div class="epg-container" id="modern-epg-container">
    <div class="epg" id="epg-container">
        <?php foreach ($channels as $channel_id => $channel): 
            $channel_programs = $programs[$channel_id] ?? [];
            $kodi_channel_id = $channel['kodi_id'] ?? '';
            $channel_group = $channel['group'] ?? 'Uncategorized';
            echo "<!-- Debug: Channel {$channel_id} Group: {$channel_group} -->\n";
        ?>
            <div class="channel" 
                 data-channel-number="<?php echo esc_attr($channel['number']); ?>" 
                 data-group="<?php echo esc_attr($channel_group); ?>">
                <div class="channel-info">
                    <a href="#" class="channel-link" 
                       data-kodi-channel-id="<?php echo esc_attr($kodi_channel_id); ?>"
                       data-channel-name="<?php echo esc_attr($channel['name']); ?>">
                        <?php if (!empty($channel['logo'])): ?>
                            <img class="channel-logo" 
                                 src="<?php echo esc_url($channel['logo']); ?>" 
                                 alt="Channel <?php echo esc_attr($channel['number'] ?? ''); ?>">
                        <?php else: ?>
                            <div class="channel-logo-placeholder">
                                <?php echo esc_html($channel['number'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
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
                                error_log('Invalid program data: ' . print_r($program, true));
                                continue;
                            }
                            
                            if ($program_end < $start_time || $program_start > $end_time) continue;
                            
                            $program_count++;
                        ?>
                            <div class="programme <?php echo ($current_time >= $program_start && $current_time < $program_end) ? 'current-program' : ''; ?>" 
                                 data-start-time="<?php echo date('Y-m-d\TH:i:sP', $program_start); ?>"
                                 data-end-time="<?php echo date('Y-m-d\TH:i:sP', $program_end); ?>"
                                 data-title="<?php echo esc_attr($program['title']); ?>"
                                 data-description="<?php echo esc_attr($program['desc'] ?? ''); ?>"
                                 style="left: <?php echo calculate_left_position($program_start, $start_time); ?>%;
                                        width: <?php echo calculate_width($program_start, $program_end, $start_time, $end_time); ?>%;">
                                <div class="programme-time">
                                    <?php echo date('H:i', $program_start) . ' - ' . date('H:i', $program_end); ?>
                                </div>
                                <div class="programme-title"><?php echo esc_html($program['title']); ?></div>
                                <?php if (!empty($program['sub-title'])): ?>
                                    <div class="programme-sub-title"><?php echo esc_html($program['sub-title']); ?></div>
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