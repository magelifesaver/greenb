<?php
/**
 * Settings manager for Rolling Lead‑Time Extension.
 *
 * Registers a settings page under WooCommerce → Settings and
 * exposes options for preparation time, dispatch buffer, default travel
 * time, Google API key and an enable switch. Each option is stored in
 * a single array option to simplify retrieval.
 *
 * @package RollingLeadTime
 */

if (!defined('ABSPATH')) {
    exit;
}

class RLT_Settings {

    /**
     * Holds the singleton instance.
     *
     * @var RLT_Settings|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance.
     *
     * @return RLT_Settings
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Hooks registration.
     */
    private function __construct() {
        // Register settings page.
        add_action('admin_menu', array($this, 'add_settings_page'));
        // Register settings.
        add_action('admin_init', array($this, 'register_settings'));
        // Filter plugin action links to include settings link.
        add_filter('plugin_action_links_' . plugin_basename(RLT_PLUGIN_DIR . 'rolling-leadtime-extension.php'), array($this, 'settings_link'));
    }

    /**
     * Adds a submenu under WooCommerce settings.
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Rolling Lead‑Time', 'rlt'),
            __('Rolling Lead‑Time', 'rlt'),
            'manage_woocommerce',
            'rlt-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registers our settings using the Settings API.
     */
    public function register_settings() {
        register_setting('rlt_settings_group', 'rlt_settings', array($this, 'sanitize_settings'));
        add_settings_section('rlt_main', __('Rolling Lead‑Time Settings', 'rlt'), array($this, 'section_desc'), 'rlt-settings');
        // Enable toggle.
        add_settings_field('rlt_enable', __('Enable rolling lead‑time', 'rlt'), array($this, 'render_field_enable'), 'rlt-settings', 'rlt_main');
        // Preparation time.
        add_settings_field('rlt_prep_time', __('Preparation time (minutes)', 'rlt'), array($this, 'render_field_prep_time'), 'rlt-settings', 'rlt_main');
        // Dispatch buffer.
        add_settings_field('rlt_dispatch_buffer', __('Dispatch buffer (minutes)', 'rlt'), array($this, 'render_field_dispatch_buffer'), 'rlt-settings', 'rlt_main');
        // Default travel time.
        add_settings_field('rlt_default_travel_time', __('Default travel time (minutes)', 'rlt'), array($this, 'render_field_default_travel'), 'rlt-settings', 'rlt_main');
        // API key.
        add_settings_field('rlt_api_key', __('Google API key', 'rlt'), array($this, 'render_field_api_key'), 'rlt-settings', 'rlt_main');
        // Slot length for product ETA.
        add_settings_field('rlt_slot_length', __('ETA window length (minutes)', 'rlt'), array($this, 'render_field_slot_length'), 'rlt-settings', 'rlt_main');
    }

    /**
     * Sanitizes the settings array.
     *
     * @param array $input Raw settings array.
     * @return array Sanitised settings.
     */
    public function sanitize_settings($input) {
        $output = array();
        $output['enable'] = isset($input['enable']) && $input['enable'] === 'yes' ? 'yes' : 'no';
        $output['prep_time'] = isset($input['prep_time']) ? absint($input['prep_time']) : 0;
        $output['dispatch_buffer'] = isset($input['dispatch_buffer']) ? absint($input['dispatch_buffer']) : 0;
        $output['default_travel_time'] = isset($input['default_travel_time']) ? absint($input['default_travel_time']) : 0;
        $output['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
        $output['slot_length'] = isset($input['slot_length']) ? absint($input['slot_length']) : 60;
        return $output;
    }

    /**
     * Renders the section description.
     */
    public function section_desc() {
        echo '<p>' . esc_html__('Configure how your earliest available delivery/pickup slot is calculated. Leave the API key empty to disable live travel time lookups.', 'rlt') . '</p>';
    }

    /**
     * Renders the enable field.
     */
    public function render_field_enable() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['enable']) ? $options['enable'] : 'no';
        echo '<select name="rlt_settings[enable]">';
        printf('<option value="yes" %s>%s</option>', selected($value, 'yes', false), esc_html__('Yes', 'rlt'));
        printf('<option value="no" %s>%s</option>', selected($value, 'no', false), esc_html__('No', 'rlt'));
        echo '</select>';
    }

    /**
     * Renders the preparation time field.
     */
    public function render_field_prep_time() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['prep_time']) ? intval($options['prep_time']) : 0;
        printf('<input type="number" min="0" name="rlt_settings[prep_time]" value="%d" class="small-text" />', $value);
    }

    /**
     * Renders the dispatch buffer field.
     */
    public function render_field_dispatch_buffer() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['dispatch_buffer']) ? intval($options['dispatch_buffer']) : 0;
        printf('<input type="number" min="0" name="rlt_settings[dispatch_buffer]" value="%d" class="small-text" />', $value);
    }

    /**
     * Renders the default travel time field.
     */
    public function render_field_default_travel() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['default_travel_time']) ? intval($options['default_travel_time']) : 0;
        printf('<input type="number" min="0" name="rlt_settings[default_travel_time]" value="%d" class="small-text" />', $value);
    }

    /**
     * Renders the API key field.
     */
    public function render_field_api_key() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        printf('<input type="text" name="rlt_settings[api_key]" value="%s" class="regular-text" />', $value);
    }

    /**
     * Renders the slot length field.
     */
    public function render_field_slot_length() {
        $options = get_option('rlt_settings', array());
        $value = isset($options['slot_length']) ? intval($options['slot_length']) : 60;
        printf('<input type="number" min="15" name="rlt_settings[slot_length]" value="%d" class="small-text" />', $value);
    }

    /**
     * Outputs the actual settings page.
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Rolling Lead‑Time', 'rlt') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('rlt_settings_group');
        do_settings_sections('rlt-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Adds a settings link to the plugin list page.
     *
     * @param array $links Existing action links.
     * @return array Modified links.
     */
    public function settings_link($links) {
        $settings_url = admin_url('admin.php?page=rlt-settings');
        $links[] = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'rlt') . '</a>';
        return $links;
    }
}
