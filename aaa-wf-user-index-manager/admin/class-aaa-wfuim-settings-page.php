<?php
/**
 * File: /wp-content/plugins/aaa-wf-user-index-manager/admin/class-aaa-wfuim-settings-page.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] settings-page loaded');

class AAA_WFUIM_Settings_Page {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_aaa_wfuim_save', [__CLASS__, 'save']);
        add_action('admin_post_aaa_wfuim_reindex', [__CLASS__, 'reindex_now']);
        add_action('admin_post_aaa_wfuim_repair', [__CLASS__, 'repair_table']);
    }

    public static function menu() {
        add_options_page(
            __('WF User Index Manager','aaa-wfuim'),
            __('WF User Index','aaa-wfuim'),
            'manage_options',
            'aaa-wfuim',
            [__CLASS__,'render']
        );
    }

    public static function render() {
        if ( ! current_user_can('manage_options') ) return;
        $s = aaa_wfuim_get_settings();
        $table_status = \AAA_WFUIM_Table_Installer::exists() ? __('Present','aaa-wfuim') : __('Missing','aaa-wfuim');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WF User Index Manager','aaa-wfuim'); ?></h1>
            <p><strong><?php esc_html_e('Table status:','aaa-wfuim'); ?></strong> <?php echo esc_html( $table_status ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field('aaa_wfuim_save','aaa_wfuim_nonce'); ?>
                <input type="hidden" name="action" value="aaa_wfuim_save" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="enabled"><?php esc_html_e('Enable indexing','aaa-wfuim'); ?></label></th>
                        <td><input type="checkbox" name="enabled" id="enabled" value="1" <?php checked(!empty($s['enabled'])); ?> />
                            <p class="description"><?php esc_html_e('Master switch per site. When off, no login/indexing occurs.','aaa-wfuim');?></p></td>
                    </tr>
                    <tr>
                        <th><label for="auto_update"><?php esc_html_e('Auto-update on meta change','aaa-wfuim'); ?></label></th>
                        <td><input type="checkbox" name="auto_update" id="auto_update" value="1" <?php checked(!empty($s['auto_update'])); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="purge_on_logout"><?php esc_html_e('Purge on logout','aaa-wfuim'); ?></label></th>
                        <td><input type="checkbox" name="purge_on_logout" id="purge_on_logout" value="1" <?php checked(!empty($s['purge_on_logout'])); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="whitelist"><?php esc_html_e('Whitelist (one key per line, * = prefix)','aaa-wfuim'); ?></label></th>
                        <td>
                            <textarea name="whitelist" id="whitelist" rows="6" class="large-text code"><?php echo esc_textarea($s['whitelist']); ?></textarea>
                            <p class="description"><?php esc_html_e('Tip: Woo user meta is usually "billing_*" / "shipping_*" (no leading underscore).','aaa-wfuim'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="exclude"><?php esc_html_e('Exclude (one key per line, * = prefix)','aaa-wfuim'); ?></label></th>
                        <td><textarea name="exclude" id="exclude" rows="4" class="large-text code"><?php echo esc_textarea($s['exclude']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Coordinate keys (CSV)','aaa-wfuim'); ?></th>
                        <td>
                            <label><?php esc_html_e('Latitude keys','aaa-wfuim'); ?></label>
                            <input type="text" name="lat_keys" value="<?php echo esc_attr($s['lat_keys']); ?>" class="regular-text" />
                            <br/>
                            <label><?php esc_html_e('Longitude keys','aaa-wfuim'); ?></label>
                            <input type="text" name="lng_keys" value="<?php echo esc_attr($s['lng_keys']); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('First matching key wins. Your ADBC keys with slashes are supported.','aaa-wfuim'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="extra_columns"><?php esc_html_e('Extra columns (cloneable)','aaa-wfuim'); ?></label></th>
                        <td>
                            <textarea name="extra_columns" id="extra_columns" rows="6" class="large-text code"><?php echo esc_textarea($s['extra_columns']); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('One per line: column_name|meta_key|type(optional). Types: VARCHAR(190) (default), TEXT, DECIMAL(12,6), INT(11). Example:','aaa-wfuim'); ?>
                                <br/><code>first_order_source|_wc_order_attribution_source_type|VARCHAR(190)</code>
                                <br/><code>checkout_lat|_wc_billing/aaa-delivery-blocks/latitude|DECIMAL(12,6)</code>
                                <br/><code>vip_level|customer_vip_level|INT(11)</code>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __('Save Settings','aaa-wfuim') ); ?>
            </form>

            <hr/>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="aaa-wfuim-reindex-box">
                <?php wp_nonce_field('aaa_wfuim_reindex','aaa_wfuim_reindex_nonce'); ?>
                <input type="hidden" name="action" value="aaa_wfuim_reindex" />
                <h2><?php esc_html_e('Reindex a user now (admin tool)','aaa-wfuim'); ?></h2>
                <p><input type="text" name="user_identifier" class="regular-text" placeholder="<?php esc_attr_e('User ID or Email','aaa-wfuim'); ?>"/>
                <?php submit_button( __('Reindex','aaa-wfuim'), 'secondary', '', false ); ?></p>
            </form>

            <hr/>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field('aaa_wfuim_repair','aaa_wfuim_repair_nonce'); ?>
                <input type="hidden" name="action" value="aaa_wfuim_repair" />
                <h2><?php esc_html_e('Repair / Install Table','aaa-wfuim'); ?></h2>
                <p class="description"><?php esc_html_e('If the index table was dropped, click to recreate it (and apply extra columns) for this site.','aaa-wfuim'); ?></p>
                <?php submit_button( __('Repair Table','aaa-wfuim'), 'delete' ); ?>
            </form>
        </div>
        <?php
    }

    public static function save() {
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
        check_admin_referer('aaa_wfuim_save','aaa_wfuim_nonce');

        $in = [
            'enabled'         => isset($_POST['enabled']) ? 1 : 0,
            'auto_update'     => isset($_POST['auto_update']) ? 1 : 0,
            'purge_on_logout' => isset($_POST['purge_on_logout']) ? 1 : 0,
            'whitelist'       => wp_unslash( $_POST['whitelist'] ?? '' ),
            'exclude'         => wp_unslash( $_POST['exclude'] ?? '' ),
            'lat_keys'        => sanitize_text_field( $_POST['lat_keys'] ?? '' ),
            'lng_keys'        => sanitize_text_field( $_POST['lng_keys'] ?? '' ),
            'extra_columns'   => wp_unslash( $_POST['extra_columns'] ?? '' ),
        ];
        update_option( aaa_wfuim_option_key(), $in );
        \AAA_WFUIM_Table_Installer::ensure();
        \AAA_WFUIM_Table_Installer::ensure_extra_columns( $in );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] settings saved + ensured schema');
        wp_safe_redirect( admin_url('options-general.php?page=aaa-wfuim&saved=1') );
        exit;
    }

    public static function reindex_now() {
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
        check_admin_referer('aaa_wfuim_reindex','aaa_wfuim_reindex_nonce');

        $id = trim( (string)($_POST['user_identifier'] ?? '') );
        $uid = 0;
        if ( is_numeric($id) ) { $uid = (int)$id; }
        elseif ( is_email($id) ) {
            $u = get_user_by('email', $id); if ($u) $uid = $u->ID;
        }
        if ( $uid ) {
            \AAA_WFUIM_Indexer::index_user( $uid );
            $msg = 'ok';
        } else { $msg = 'user_not_found'; }
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] manual reindex '. $msg );
        wp_safe_redirect( admin_url('options-general.php?page=aaa-wfuim&reindex='.$msg) );
        exit;
    }

    public static function repair_table() {
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
        check_admin_referer('aaa_wfuim_repair','aaa_wfuim_repair_nonce');
        \AAA_WFUIM_Table_Installer::install();
        \AAA_WFUIM_Table_Installer::ensure_extra_columns( aaa_wfuim_get_settings() );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] table repair/install executed');
        wp_safe_redirect( admin_url('options-general.php?page=aaa-wfuim&repaired=1') );
        exit;
    }
}
