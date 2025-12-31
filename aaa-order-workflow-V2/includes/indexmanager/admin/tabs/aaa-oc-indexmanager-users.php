<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/admin/tabs/aaa-oc-indexmanager-users.php
 * Index Manager — Users (Session-only, triggers, columns, status, actions)
 */
if ( ! defined('ABSPATH') ) exit;

use \AAA_OC_IndexManager_Helpers as IMH;

// --------------------- SAVE HANDLERS ---------------------
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['action']) ) {
    $action = sanitize_text_field($_POST['action']);

    if ( $action === 'aaa_oc_im_users_save' && check_admin_restrict_nonce() ) {
        $in = [
            'enabled'      => !empty($_POST['enabled'])?1:0,
            'session_only' => !empty($_POST['session_only'])?1:0,
            'login_index'  => !empty($_POST['login_index'])?1:0,
            'logout_purge' => !empty($_POST['logout_purge'])?1:0,
            'triggers'     => array_values(array_filter(array_map('sanitize_text_field', $_POST['triggers'] ?? []))),
            'columns'      => [],
        ];
        $cols = $_POST['col'] ?? []; $N = count($cols['col'] ?? []);
        for ($i=0;$i<$N;$i++){
            $col = sanitize_key($cols['col'][$i] ?? '');
            if (! $col) continue;
            $source = sanitize_text_field($cols['source'][$i] ?? 'meta');

            $row = [
                'col'     => $col,
                'source'  => $source,
                'key'     => sanitize_text_field($cols['key'][$i] ?? ''),
                'type'    => sanitize_text_field($cols['type'][$i] ?? 'VARCHAR(190)'),
                'primary' => !empty($cols['primary'][$i]),
                'index'   => !empty($cols['index'][$i]),
                'unique'  => !empty($cols['unique'][$i]),
            ];

            if ( $source === 'table' ) {
                $row['ext_table']   = sanitize_key($cols['ext_table'][$i] ?? '');
                $row['ext_fk_col']  = sanitize_key($cols['ext_fk_col'][$i] ?? '');
                $row['ext_val_col'] = sanitize_key($cols['ext_val_col'][$i] ?? '');
            }

            $in['columns'][] = $row;
        }
        if (!$in['columns']) $in = IMH::defaults('users');

        IMH::set_opt('users', $in);
        AAA_OC_IndexManager_Table_Installer::ensure('users');

        wp_safe_redirect( add_query_arg(['saved'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        exit;
    }

    if ( $action === 'aaa_oc_im_users_reindex_me' ) {
        $uid = get_current_user_id();
        if ( $uid ) {
            AAA_OC_IndexManager_Table_Indexer::upsert_now('users', (int)$uid);
            if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][manual] upsert_now id='.$uid);
            wp_safe_redirect( add_query_arg(['reindexed_me'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        } else {
            wp_safe_redirect( add_query_arg(['reindexed_me'=>'0'], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        }
        exit;
    }

    if ( $action === 'aaa_oc_im_users_reindex_all' ) {
        AAA_OC_IndexManager_Table_Indexer::reindex_table('users'); // bulk; ignores session mode by design
        wp_safe_redirect( add_query_arg(['reindexed_all'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        exit;
    }

    if ( $action === 'aaa_oc_im_users_purge_me' ) {
        $uid = get_current_user_id();
        if ($uid) {
            AAA_OC_IndexManager_Table_Indexer::purge('users', (int)$uid);
            wp_safe_redirect( add_query_arg(['purged_me'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        } else {
            wp_safe_redirect( add_query_arg(['purged_me'=>'0'], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        }
        exit;
    }

    if ( $action === 'aaa_oc_im_users_repair' ) {
        check_admin_referer('aaa_oc_im_users_repair');
        AAA_OC_IndexManager_Table_Installer::ensure('users');
        wp_safe_redirect( add_query_arg(['repaired'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        exit;
    }

    if ( $action === 'aaa_oc_im_users_rebuild' ) {
        check_admin_referer('aaa_oc_im_users_rebuild');
        AAA_OC_IndexManager_Table_Installer::rebuild('users', true);
        wp_safe_redirect( add_query_arg(['rebuilt'=>1], menu_page_url('aaa-oc-core-settings', false).'&tab=aaa-oc-indexmanager-users') );
        exit;
    }
}

function check_admin_restrict_nonce(): bool {
    return isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce']);
}

// --------------------- LOAD STATE ---------------------
$cfg = IMH::get_opt('users');
global $wpdb;
$uid = get_current_user_id();
$table = IMH::table_name('users');
$pk    = IMH::primary_col('users');
$present = false;
if ( $uid ) {
    $present = (bool) $wpdb->get_var( $wpdb->prepare("SELECT 1 FROM `$table` WHERE `$pk` = %d LIMIT 1", $uid) );
}

// --------------------- notifications ---------------------
if ( isset($_GET['saved']) )        echo '<div class="updated notice"><p>Users settings saved.</p></div>';
if ( isset($_GET['repaired']) )     echo '<div class="updated notice"><p>Users index table synchronized (Repair Table).</p></div>';
if ( isset($_GET['rebuilt']) )      echo '<div class="notice notice-warning"><p>Users index table rebuilt. Verify data has been re-indexed.</p></div>';
if ( isset($_GET['reindexed_me']) ) echo '<div class="updated notice"><p>Current user reindexed.</p></div>';
if ( isset($_GET['reindexed_all']) )echo '<div class="updated notice"><p>All users queued for reindex (ignores Session mode).</p></div>';
if ( isset($_GET['purged_me']) )    echo '<div class="updated notice"><p>'.(($_GET['purged_me']=='1')?'Current user row purged.':'No logged-in user to purge.').'</p></div>';

$disableTriggers = !empty($cfg['session_only']);
?>
<div class="wrap">
    <h2>Index Manager — <strong>Users</strong></h2>

    <p><strong>Current user:</strong>
        <?php if ($uid): ?>
            ID <code><?php echo (int)$uid; ?></code>
            — Index row: <?php echo $present ? '<span style="color:#1e8e3e">Present</span>' : '<span style="color:#a00">Not found</span>'; ?>
        <?php else: ?>
            <em>no user logged in</em>
        <?php endif; ?>
    </p>

    <form method="post">
        <?php wp_nonce_field('aaa_oc_im_users_nonce'); ?>
        <input type="hidden" name="action" value="aaa_oc_im_users_save" />

        <h3>Session & Behavior</h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Enable'); ?></th>
                <td>
                    <label><input type="checkbox" name="enabled" value="1" <?php checked(!empty($req=$cfg['enabled'])) ?>>
                        <?php _e('Enable Users Index'); ?></label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Session mode'); ?></th>
                <td>
                    <label><input type="checkbox" name="session_only" value="1" <?php checked(!empty($cfg['ller']=$cfg['session_only'])) ?>>
                        <?php _e('Session-only (index on login, purge on logout; ignore other triggers)'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Login / Logout'); ?></th>
                <td>
                    <label><input type="checkbox" name="login_index" value="1" <?php checked(!empty($cfg['login_index'])) ?>>
                        <?php _e('Index current user on login'); ?></label><br>
                    <label><input type="checkbox" name="logout_purge" value="1" <?php checked(!empty($cfg['logout_purge'])) ?>>
                        <?php _e('Purge current user’s row on logout'); ?></label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Triggers (applied when Session-only is OFF)'); ?></th>
                <td>
                    <?php
                    $choices = ['wp_login','wp_logout_purge','profile_update','added_user_meta','updated_user_meta','deleted_user_meta','set_user_role','woocommerce_customer_save_address'];
                    foreach ($choices as $c) {
                        printf(
                            '<label style="margin-right:12px;opacity:%s"><input type="checkbox" name="triggers[]" value="%s" %s %s> %s</label>',
                            $disableTriggers ? .5 : 1,
                            esc_attr($c),
                            checked(in_array($c, (array)$cfg['triggers'], true), true, false),
                            $disableTriggers ? 'disabled="disabled"' : '',
                            esc_html($c)
                        );
                    }
                    ?>
                    <?php if ($disableTriggers): ?>
                        <p class="description"><?php _e('Disabled because Session-only is ON.'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h3>Columns</h3>
        <p class="description">
            Add columns (source: <code>core</code>, <code>meta</code>, <code>computed</code>, or <code>table</code> for external
            data such as FluentCRM contacts). Computed supports <code>billing_address</code>, <code>shipping_address</code>,
            <code>updated_at</code>, and JSON extraction with <code>json:&lt;meta_key&gt;.&lt;path&gt;</code>, e.g.
            <code>json:shipping_szbd-picked-location.lat</code>. Table sources use a prefixed table suffix, the user ID column,
            and the value column to pull into your index.
        </p>

        <button class="button" id="im-add-col">Add Column</button>
        <div id="im-cols" class="im-sort">
            <?php
            $cols = (array)$cfg['columns'];
            if (! $cols) $cols = IMH::defaults('users')['columns'];
            foreach ($cols as $c):
                $col = esc_attr($c['col'] ?? '');
                $src = esc_attr($c['species'] ?? $c['source'] ?? 'meta');
                $key = esc_attr($c['key'] ?? '');
                $type= esc_attr($c['type'] ?? 'VARCHAR(190)');
                $prim= !empty($c['primary']);
                $idx = !empty($c['index']);
                $uniq= !empty($c['unique']);
                $ext_table   = esc_attr($c['ext_table']   ?? '');
                $ext_fk_col  = esc_attr($c['ext_fk_col']  ?? '');
                $ext_val_col = esc_attr($c['ext_val_col'] ?? '');
            ?>
            <div class="im-row">
                <span class="dashicons dashicons-move im-handle"></span>
                <input type="text" name="col[col][]" placeholder="column_name" value="<?php echo $col; ?>" />
                <select name="col[source][]" class="im-source">
                    <option value="core"     <?php selected($src,'core');?>>core</option>
                    <option value="meta"     <?php selected($src,'meta');?>>meta</option>
                    <option value="computed" <?php selected($src,'computed');?>>computed</option>
                    <option value="table"    <?php selected($src,'table');?>>table</option>
                </select>
                <span class="im-key-group"<?php if ( $src === 'table' ) echo ' style="display:none;"'; ?>>
                    <input type="text" name="col[key][]" value="<?php echo $key; ?>" placeholder="key or computed token (e.g., json:foo.bar)" />
                </span>
                <span class="im-table-group"<?php if ( $src !== 'table' ) echo ' style="display:none;"'; ?>>
                    <input type="text" name="col[ext_table][]"  value="<?php echo $ext_table; ?>" placeholder="table (suffix)" />
                    <input type="text" name="col[ext_fk_col][]" value="<?php echo $ext_fk_col; ?>" placeholder="ID column" />
                    <input type="text" name="col[ext_val_col][]" value="<?php echo $ext_val_col; ?>" placeholder="value column" />
                </span>
                <select name="col[type][]">
                    <?php foreach (['VARCHAR(190)','VARCHAR(200)','TEXT','INT(11)','BIGINT(20) UNSIGNED','DECIMAL(12,6)','DECIMAL(18,6)','DATETIME'] as $t): ?>
                        <option value="<?php echo esc_attr($t);?>" <?php selected($type,$t);?>><?php echo esc_html($t);?></option>
                    <?php endforeach; ?>
                </select>
                <label><input type="checkbox" name="col[primary][]" <?php checked($prim);?>> <?php _e('Primary');?></label>
                <label><input type="checkbox" name="col[index][]"   <?php checked($idx);?>>   <?php _e('Index');?></label>
                <label><input type="checkbox" name="col[unique][]"  <?php checked($uniq);?>>  <?php _e('Unique');?></label>
                <a href="#" class="im-remove">×</a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="submit">
            <button type="submit" name="save" class="button button-primary"><?php _e('Save'); ?></button>
        </p>
        <input type="hidden" name="action" value="aaa_oc_im_users_save" />
        <?php wp_nonce_field('aaa_oc_im_users_nonce'); ?>
    </form>

    <hr/>

    <h3><?php _e('Index maintenance'); ?></h3>
    <p class="description">
        <strong><?php _e('Index current user'); ?></strong>
        — <?php _e('Rebuild this user’s row now (respects Session-only).'); ?>
    </p>
    <form method="post" style="display:inline-block;margin-right:10px">
        <input type="hidden" name="action" value="aaa_oc_im_users_reindex_me" />
        <button class="button"><?php _e('Index current user'); ?></button>
    </form>

    <p class="description" style="margin-top:1em">
        <strong><?php _e('Reindex ALL users'); ?></strong>
        — <?php _e('Queues a full rebuild of the entire users index.'); ?>
        <em><?php _e('This ignores Session-only mode.'); ?></em>
    </p>
    <form method="post" style="display:inline-block;margin-right:10px" onsubmit="return confirm('<?php echo esc_js(__('Reindex all users? This may take time.', 'aaa-oc')); ?>')">
        <input type="hidden" name="action" value="aaa_oc_im_users_reindex_all" />
        <button class="button"><?php _e('Reindex ALL users'); ?></button>
    </form>

    <p class="description" style="margin-top:1em">
        <strong><?php _e('Purge my row now'); ?></strong> — <?php _e('Deletes your current index row (useful to test logout purge).'); ?>
    </p>
    <form method="post" style="display:inline-block;margin-right:10px">
        <input type="hidden" name="action" value="aaa_oc_im_users_purge_me" />
        <button class="button"><?php _e('Purge my row now'); ?></button>
    </form>

    <hr/>

    <h3><?php _e('Table synchronization'); ?></h3>
    <p class="description">
        <strong><?php _e('Repair Table'); ?></strong> — <?php _e('Applies your current Columns to the DB (adds/updates columns & indexes).'); ?><br>
        <strong><?php _e('Rebuild Table (danger)'); ?></strong> — <?php _e('Drops and recreates the index table from your Columns. This deletes existing rows.'); ?>
    </p>
    <form method="post" style="display:inline-block;margin-right:10px">
        <?php wp_nonce_field('aaa_oc_im_users_repair'); ?>
        <input type="hidden" name="action" value="aaa_oc_im_users_repair" />
        <button class="button"><?php _e('Repair Table'); ?></button>
    </form>
    <form method="post" style="display:inline-block">
        <?php wp_nonce_field('aaa_oc_im_users_rebuild'); ?>
        <input type="hidden" name="action" value="aaa_oc_im_users_rebuild" />
        <button class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Rebuild the users index table? This will drop & recreate it.', 'aaa-oc')); ?>')">
            <?php _e('Rebuild Table (danger)'); ?>
        </button>
    </form>
</div>
