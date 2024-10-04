<div class="group-filters">
    <button class="group-filter" data-group="all">All</button>
    <?php
    $groups = ['Belgium', 'The Netherlands', 'USA', 'UK', 'ETV', 'Movies', 'Comedy', 'Documentary', 'YouTube'];
    foreach ($groups as $group) {
        $image_name = str_replace(' ', '_', $group);
        echo "<button class='group-filter' data-group='" . esc_attr($group) . "'>";
        echo "<img src='" . esc_url(MODERN_EPG_PLUGIN_URL . "images/{$image_name}.png") . "' alt='" . esc_attr($group) . "'>";
        echo "</button>";
    }
    ?>
</div>