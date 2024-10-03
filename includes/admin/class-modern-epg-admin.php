<?php
class Modern_EPG_Admin {
    private static $instance = null;
    private $options; // Declare the options property

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        $this->options = get_option('modern_epg_options', array()); // Initialize options
    }

    public function add_plugin_page() {
        add_options_page(
            'Modern EPG Settings', 
            'Modern EPG', 
            'manage_options', 
            'modern-epg', 
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        static $form_rendered = false;

        if ($form_rendered) {
            return;
        }

        $form_rendered = true;

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('modern_epg_option_group');
                do_settings_sections('modern-epg');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        static $settings_registered = false;

        if ($settings_registered) {
            return;
        }

        $settings_registered = true;

        register_setting(
            'modern_epg_option_group',
            'modern_epg_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'modern_epg_setting_section',
            'Modern EPG Settings',
            array($this, 'print_section_info'),
            'modern-epg'
        );

        add_settings_field(
            'kodi_url', 
            'Kodi URL', 
            array($this, 'kodi_url_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );

        add_settings_field(
            'kodi_port', 
            'Kodi Port', 
            array($this, 'kodi_port_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );

        add_settings_field(
            'kodi_username', 
            'Kodi Username', 
            array($this, 'kodi_username_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );

        add_settings_field(
            'kodi_password', 
            'Kodi Password', 
            array($this, 'kodi_password_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );

        add_settings_field(
            'm3u_url', 
            'M3U URL', 
            array($this, 'm3u_url_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );

        add_settings_field(
            'xml_url', 
            'XML URL', 
            array($this, 'xml_url_callback'), 
            'modern-epg',
            'modern_epg_setting_section'
        );
    }

    public function sanitize($input) {
        $sanitized_input = array();
        if(isset($input['kodi_url']))
            $sanitized_input['kodi_url'] = sanitize_text_field($input['kodi_url']);
        if(isset($input['kodi_port']))
            $sanitized_input['kodi_port'] = sanitize_text_field($input['kodi_port']);
        if(isset($input['kodi_username']))
            $sanitized_input['kodi_username'] = sanitize_text_field($input['kodi_username']);
        if(isset($input['kodi_password']))
            $sanitized_input['kodi_password'] = sanitize_text_field($input['kodi_password']);
        if(isset($input['m3u_url']))
            $sanitized_input['m3u_url'] = esc_url_raw($input['m3u_url']);
        if(isset($input['xml_url']))
            $sanitized_input['xml_url'] = esc_url_raw($input['xml_url']);
        return $sanitized_input;
    }

    public function print_section_info() {
        print 'Enter your Kodi settings below:';
    }

    public function kodi_url_callback() {
        printf(
            '<input type="text" id="modern_epg_kodi_url" name="modern_epg_options[kodi_url]" value="%s" />',
            isset($this->options['kodi_url']) ? esc_attr($this->options['kodi_url']) : ''
        );
    }

    public function kodi_port_callback() {
        printf(
            '<input type="text" id="modern_epg_kodi_port" name="modern_epg_options[kodi_port]" value="%s" />',
            isset($this->options['kodi_port']) ? esc_attr($this->options['kodi_port']) : ''
        );
    }

    public function kodi_username_callback() {
        printf(
            '<input type="text" id="modern_epg_kodi_username" name="modern_epg_options[kodi_username]" value="%s" />',
            isset($this->options['kodi_username']) ? esc_attr($this->options['kodi_username']) : ''
        );
    }

    public function kodi_password_callback() {
        printf(
            '<input type="password" id="modern_epg_kodi_password" name="modern_epg_options[kodi_password]" value="%s" />',
            isset($this->options['kodi_password']) ? esc_attr($this->options['kodi_password']) : ''
        );
    }

    public function m3u_url_callback() {
        $options = get_option('modern_epg_options');
        $m3u_url = isset($options['m3u_url']) ? $options['m3u_url'] : '';
        echo "<input type='text' name='modern_epg_options[m3u_url]' value='{$m3u_url}' class='regular-text' />";
    }

    public function xml_url_callback() {
        $options = get_option('modern_epg_options');
        $xml_url = isset($options['xml_url']) ? $options['xml_url'] : '';
        echo "<input type='text' name='modern_epg_options[xml_url]' value='{$xml_url}' class='regular-text' />";
    }
}