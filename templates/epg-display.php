<div class="epg-container">
    <div class="epg" id="epg-container">
        <?php foreach ($channels as $channel): ?>
            <div class="channel" data-channel-number="<?php echo esc_attr($channel['number']); ?>">
                <div class="channel-info">
                    <img class="channel-logo" src="<?php echo esc_url($channel['logo']); ?>" alt="Channel <?php echo esc_attr($channel['number']); ?>">
                    <div class="channel-name"><?php echo esc_html($channel['name']); ?></div>
                </div>
                <div class="programme-list-container">
                    <div class="programme-list">
                        <?php 
                        $start_time = strtotime('today midnight');
                        $minutes_per_column = 5; // 5 minutes per column
                        $total_columns = 24 * 60 / $minutes_per_column; // 24 hours, 288 columns

                        foreach ($channel_programs as $program):
                            $program_start = max($start_time, $program['start']);
                            $program_end = min($start_time + 86400, $program['stop']); // 86400 seconds in a day

                            $start_column = (($program_start - $start_time) / 60) / $minutes_per_column + 1;
                            $end_column = (($program_end - $start_time) / 60) / $minutes_per_column + 1;
                            $span = $end_column - $start_column;
                        ?>
                            <div class="programme" 
                                 data-start-time="<?php echo date('Y-m-d\TH:i:sP', $program['start']); ?>"
                                 data-end-time="<?php echo date('Y-m-d\TH:i:sP', $program['stop']); ?>"
                                 data-title="<?php echo esc_attr($program['title']); ?>"
                                 style="grid-column: <?php echo $start_column . ' / span ' . $span; ?>;">
                                <div class="programme-time">
                                    <?php echo date('H:i', $program['start']) . ' - ' . date('H:i', $program['stop']); ?>
                                </div>
                                <div class="programme-title"><?php echo esc_html($program['title']); ?></div>
                                <?php if (!empty($program['sub-title'])): ?>
                                    <div class="programme-sub-title"><?php echo esc_html($program['sub-title']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
