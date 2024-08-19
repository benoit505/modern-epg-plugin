<?php
class Modern_EPG_i18n {
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'modern-epg-plugin',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}