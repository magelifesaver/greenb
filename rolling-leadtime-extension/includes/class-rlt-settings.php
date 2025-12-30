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
        // Add debug page after the settings page.
        add_action('admin_menu', array($this, 'add_debug_page'));
        // Register settings.
        add_action('admin_init', array($this, 'register_settings'));
        // Display an admin notice when settings are saved.
        add_action('admin_notices', array($this, 'settings_saved_notice'));
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
     * Adds a debug submenu page. This page provides runtime diagnostics for
     * administrators, helping them verify that the plugin is functioning
     * correctly. It appears under the WooCommerce menu alongside the
     * settings page.
     */
    public function add_debug_page() {
        add_submenu_page(
            'woocommerce',
            __('Rolling Lead‑Time Debug', 'rlt'),
            __('Rolling Lead‑Time Debug', 'rlt'),
            'manage_woocommerce',
            'rlt-debug',
            array($this, 'render_debug_page')
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
     * Displays a notice when the settings page is saved. WordPress
     * automatically appends `settings-updated` to the query string when
     * options.php redirects after saving settings. We check for this
     * parameter to show a friendly success message.
     */
    public function settings_saved_notice() {
        if (isset($_GET['page']) && $_GET['page'] === 'rlt-settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Rolling Lead‑Time settings saved.', 'rlt') . '</p></div>';
        }
    }

    /**
     * Renders the debug page. This page shows various diagnostic
     * information about the current plugin configuration and allows
     * administrators to test their Google API key. It uses AJAX to
     * perform live requests so that the page can update without a reload.
     */
    public function render_debug_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        // Enqueue a small script to handle AJAX calls. We localize the
        // endpoints and a nonce for security. This script will be printed
        // inline below to avoid the need for external asset loading.
        $ajax_nonce = wp_create_nonce('rlt_debug_nonce');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Rolling Lead‑Time Debug', 'rlt'); ?></h1>
            <p><?php esc_html_e('This page provides diagnostic information to help troubleshoot issues with the Rolling Lead‑Time plugin. The information below is generated dynamically based on your current settings and environment.', 'rlt'); ?></p>
            <h2><?php esc_html_e('Current Configuration', 'rlt'); ?></h2>
            <div id="rlt-debug-info"><p><?php esc_html_e('Loading debug information…', 'rlt'); ?></p></div>
            <h2><?php esc_html_e('Test Google API Key', 'rlt'); ?></h2>
            <p><?php esc_html_e('Enter a Google Distance Matrix API key below and click “Test Key” to verify that it is valid. A valid key will return travel time data.', 'rlt'); ?></p>
            <input type="text" id="rlt-test-key" class="regular-text" />
            <button type="button" class="button" id="rlt-test-key-btn"><?php esc_html_e('Test Key', 'rlt'); ?></button>
            <span id="rlt-test-key-result" style="margin-left:10px;"></span>
        </div>
        <script type="text/javascript">
        (function($){
            $(document).ready(function(){
                // Function to load debug info.
                function loadDebug() {
                    $('#rlt-debug-info').html('<?php echo esc_js(__('Loading debug information…', 'rlt')); ?>');
                    $.post(ajaxurl, {
                        action: 'rlt_get_debug_info',
                        _ajax_nonce: '<?php echo esc_js($ajax_nonce); ?>'
                    }, function(response){
                        if (response && response.success) {
                            var data = response.data;
                            var html = '<table class="widefat striped"><tbody>';
                            html += '<tr><th>' + '<?php echo esc_js(__('Preparation time', 'rlt')); ?>' + '</th><td>' + data.prep_time + ' <?php echo esc_js(__('mins', 'rlt')); ?>' + '</td></tr>';
                            html += '<tr><th>' + '<?php echo esc_js(__('Dispatch buffer', 'rlt')); ?>' + '</th><td>' + data.dispatch_buffer + ' <?php echo esc_js(__('mins', 'rlt')); ?>' + '</td></tr>';
                            if (data.live_travel_time !== false) {
                                html += '<tr><th>' + '<?php echo esc_js(__('Travel time (live)', 'rlt')); ?>' + '</th><td>' + data.live_travel_time + ' <?php echo esc_js(__('mins', 'rlt')); ?>' + '</td></tr>';
                            } else {
                                html += '<tr><th>' + '<?php echo esc_js(__('Travel time (live)', 'rlt')); ?>' + '</th><td>' + '<?php echo esc_js(__('N/A (using default)', 'rlt')); ?>' + '</td></tr>';
                            }
                            html += '<tr><th>' + '<?php echo esc_js(__('Travel time used', 'rlt')); ?>' + '</th><td>' + data.travel_time_used + ' <?php echo esc_js(__('mins', 'rlt')); ?>' + '</td></tr>';
                            html += '<tr><th>' + '<?php echo esc_js(__('Total lead time', 'rlt')); ?>' + '</th><td>' + data.total_lead_time + ' <?php echo esc_js(__('mins', 'rlt')); ?>' + '</td></tr>';
                            if (data.earliest_slot_timestamp) {
                                html += '<tr><th>' + '<?php echo esc_js(__('Earliest slot start', 'rlt')); ?>' + '</th><td>' + data.earliest_slot_string + '</td></tr>';
                                html += '<tr><th>' + '<?php echo esc_js(__('Earliest slot end', 'rlt')); ?>' + '</th><td>' + data.earliest_slot_end + '</td></tr>';
                            } else {
                                html += '<tr><th>' + '<?php echo esc_js(__('Earliest slot', 'rlt')); ?>' + '</th><td>' + '<?php echo esc_js(__('No available slot found', 'rlt')); ?>' + '</td></tr>';
                            }
                            html += '</tbody></table>';
                            $('#rlt-debug-info').html(html);
                        } else {
                            $('#rlt-debug-info').html('<?php echo esc_js(__('Unable to load debug information.', 'rlt')); ?>');
                        }
                    }).fail(function(){
                        $('#rlt-debug-info').html('<?php echo esc_js(__('Error loading debug information.', 'rlt')); ?>');
                    });
                }
                // Initial load.
                loadDebug();
                // Test API key button handler.
                $('#rlt-test-key-btn').on('click', function(){
                    var key = $('#rlt-test-key').val();
                    $('#rlt-test-key-result').removeClass('rlt-valid rlt-invalid').text('<?php echo esc_js(__('Testing…', 'rlt')); ?>');
                    $.post(ajaxurl, {
                        action: 'rlt_test_api_key',
                        _ajax_nonce: '<?php echo esc_js($ajax_nonce); ?>',
                        api_key: key
                    }, function(response){
                        if (response && response.success) {
                            if (response.data.valid) {
                                $('#rlt-test-key-result').addClass('rlt-valid').text('<?php echo esc_js(__('Valid key', 'rlt')); ?>');
                            } else {
                                $('#rlt-test-key-result').addClass('rlt-invalid').text('<?php echo esc_js(__('Invalid key', 'rlt')); ?>');
                            }
                        } else {
                            $('#rlt-test-key-result').addClass('rlt-invalid').text('<?php echo esc_js(__('Error testing key', 'rlt')); ?>');
                        }
                    }).fail(function(){
                        $('#rlt-test-key-result').addClass('rlt-invalid').text('<?php echo esc_js(__('Error testing key', 'rlt')); ?>');
                    });
                });
            });
        })(jQuery);
        </script>
        <style>
        #rlt-test-key-result.rlt-valid { color: #46b450; font-weight: bold; }
        #rlt-test-key-result.rlt-invalid { color: #dc3232; font-weight: bold; }
        </style>
        <?php
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
