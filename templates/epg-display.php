<div class="epg-container">
    <div class="epg" id="epg-container">
        <?php
        $current_time = time();
        $start_time = $current_time - (15 * 60); // 15 minutes ago
        $end_time = $current_time + (3 * 60 * 60); // 3 hours from now

        foreach ($channels as $channel): 
            $channel_programs = $programs[$channel['id']] ?? [];
        ?>
            <div class="channel" data-channel-number="<?php echo esc_attr($channel['number']); ?>">
                <div class="channel-info">
                    <img class="channel-logo" src="<?php echo esc_url($channel['logo']); ?>" alt="Channel <?php echo esc_attr($channel['number']); ?>">
                </div>
                <div class="programme-list-container">
                    <div class="programme-list">
                        <?php foreach ($channel_programs as $program):
                            if ($program['stop'] < $start_time || $program['start'] > $end_time) continue;

                            $start_minutes = max(0, ($program['start'] - $start_time) / 60);
                            $end_minutes = min(180, ($program['stop'] - $start_time) / 60);
                            $grid_column_start = round($start_minutes) + 1;
                            $grid_column_end = round($end_minutes) + 1;

                            $is_current_program = ($current_time >= $program['start'] && $current_time < $program['stop']) ? 'current-program' : '';
                        ?>
                            <div class="programme <?php echo $is_current_program; ?>" 
                                 data-channel="<?php echo esc_attr($channel['id']); ?>"
                                 data-title="<?php echo esc_attr($program['title']); ?>"
                                 data-sub-title="<?php echo esc_attr($program['sub-title']); ?>"
                                 data-description="<?php echo esc_attr($program['desc']); ?>"
                                 data-start-time="<?php echo esc_attr(date('H:i', $program['start'])); ?>"
                                 data-end-time="<?php echo esc_attr(date('H:i', $program['stop'])); ?>"
                                 style="grid-column: <?php echo $grid_column_start . ' / ' . $grid_column_end; ?>;">
                                <div class="programme-time"><?php echo date('H:i', $program['start']) . ' – ' . date('H:i', $program['stop']); ?></div>
                                <div class="programme-title"><?php echo esc_html($program['title']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
