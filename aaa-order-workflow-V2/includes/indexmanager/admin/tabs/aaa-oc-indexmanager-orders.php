<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Orders Tab — PRG pattern, enable + triggers + qualifiers (status filter, purge),
 * retention (daily cleanup), and utility actions: Repair / Strict Rebuild / Reindex.
 */

$base_url = add_query_arg([
    'page' => 'aaa-oc-core-settings',
    'tab'  => 'aaa-oc-indexmanager-orders',
], admin_url('admin.php'));

/* ---------- SAVE (PRG) ---------- */
if ( 'POST' === $_SERVER['REQUEST_METHOD']
     && isset($_POST['aaa_oc_im_orders_submit'])
     && check_admin_referer('aaa_oc_im_orders_nonce') ) {

    $in = [
        'enabled'        => !empty($_POST['enabled']) ? 1 : 0,
        'triggers'       => array_values(array_filter(array_map('sanitize_text_field', $_POST['triggers'] ?? []))),
        // Qualifiers
        'allowed_statuses' => array_values(array_filter(array_map('sanitize_text_field', $_POST['allowed_statuses'] ?? []))),
        'purge_excluded'   => !empty($_POST['purge_excluded']) ? 1 : 0,
        // Retention
        'retention_enable' => !empty($_POST['retention_enable']) ? 1 : 0,
        'retention_days'   => max(0, (int)($_POST['retention_days'] ?? 0)),
        // Columns
        'columns'  => [],
    ];

    $cols = $_POST['col'] ?? [];
    $N = count($cols['col'] ?? []);
    for ($i=0; $i<$N; $i++){
        $name = isset($cols['col'][$i]) ? sanitize_key($cols['col'][$i]) : '';
        if ($name === '') continue;
        $in['columns'][] = [
            'col'     => $name,
            'source'  => sanitize_text_field($cols['source'][$i] ?? 'meta'),
            'key'     => sanitize_text_field($cols['key'][$i] ?? ''),
            'type'    => sanitize_text_field($cols['type'][$i] ?? 'VARCHAR(190)'),
            'primary' => !empty($cols['primary'][$i]),
            'index'   => !empty($cols['index'][$i]),
            'unique'  => !empty($cols['unique'][$i]),
        ];
    }
    if ( empty($in['columns']) ) $in = AAA_OC_IndexManager_Helpers::defaults('orders');

    AAA_OC_IndexManager_Helpers::set_opt('orders', $in);
    AAA_OC_IndexManager_Table_Installer::ensure('orders');
    // sync retention schedule
    AAA_OC_IndexManager_Helpers::sync_orders_retention_schedule();

    wp_safe_redirect( add_query_arg('saved', 1, $base_url) );
    exit;
}

/* ---------- REPAIR (ensure) ---------- */
if ( isset($_POST['aaa_oc_im_orders_repair']) && check_admin_referer('aaa_oc_im_orders_repair') ) {
    AAA_OC_IndexManager_Table_Installer::ensure('orders');
    wp_safe_redirect( add_query_arg('repaired', 1, $base_url) );
    exit;
}

/* ---------- STRICT REBUILD ---------- */
if ( isset($_POST['aaa_oc_im_orders_rebuild']) && check_admin_referer('aaa_oc_im_orders_rebuild') ) {
    AAA_OC_IndexManager_Table_Installer::rebuild('orders', true);
    wp_safe_redirect( add_query_arg('rebuilt', 1, $base_url) );
    exit;
}

/* ---------- REINDEX NOW ---------- */
if ( isset($_POST['aaa_oc_im_orders_reindex']) && check_admin_referer('aaa_oc_im_orders_reindex') ) {
    AAA_OC_IndexManager_Table_Indexer::reindex_table('orders');
    wp_safe_redirect( add_query_arg('reindexed', 1, $base_url) );
    exit;
}

/* ---------- LOAD ---------- */
$cfg = AAA_OC_IndexManager_Helpers::get_opt('orders');

if ( !empty($_GET['saved']) )     echo '<div class="notice notice-success"><p>Orders settings saved.</p></div>';
if ( !empty($_GET['repaired']) )  echo '<div class="notice notice-info"><p>Orders table repaired.</p></div>';
if ( !empty($_GET['rebuilt']) )   echo '<div class="notice notice-warning"><p>Orders table rebuilt (strict).</p></div>';
if ( !empty($_GET['reindexed']) ) echo '<div class="notice notice-info"><p>Orders reindexed.</p></div>';

$all_triggers = [
    'save_post_shop_order',
    'woocommerce_order_status_changed',
    'updated_post_meta',
    'woocommerce_new_order',
    'woocommerce_checkout_order_processed',
    'deleted_post',
    'trashed_post',
    'untrashed_post',
];
$statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
?>
<h2>Index Manager — Orders</h2>

<form method="post">
    <?php wp_nonce_field('aaa_oc_im_orders_nonce'); ?>
    <table class="form-table">
        <tr>
            <th>Enable</th>
            <td><label><input type="checkbox" name="enabled" value="1" <?php checked(!empty($cfg['enabled'])); ?>> Enable Orders Index</label></td>
        </tr>

        <tr>
            <th>Triggers</th>
            <td>
                <?php foreach ($all_triggers as $h): ?>
                    <label style="margin-right:12px">
                        <input type="checkbox" name="triggers[]" value="<?php echo esc_attr($h); ?>" <?php checked(in_array($h,(array)$cfg['triggers'],true)); ?>>
                        <?php echo esc_html($h); ?>
                    </label>
                <?php endforeach; ?>
            </td>
        </tr>

        <tr>
            <th>Qualifiers</th>
            <td>
                <strong>Allowed statuses:</strong><br>
                <?php foreach ($statuses as $slug=>$label): ?>
                    <label style="display:inline-block;margin-right:12px">
                        <input type="checkbox" name="allowed_statuses[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug,(array)$cfg['allowed_statuses'],true)); ?>>
                        <?php echo esc_html($label); ?> (<?php echo esc_html($slug); ?>)
                    </label>
                <?php endforeach; ?>
                <p><label><input type="checkbox" name="purge_excluded" value="1" <?php checked(!empty($cfg['purge_excluded'])); ?>> Purge row if order no longer qualifies</label></p>
            </td>
        </tr>

        <tr>
            <th>Retention</th>
            <td>
                <label><input type="checkbox" name="retention_enable" value="1" <?php checked(!empty($cfg['retention_enable'])); ?>> Enable daily cleanup</label>
                <br>
                <label>Days to keep: <input type="number" min="0" step="1" name="retention_days" value="<?php echo (int)$cfg['retention_days']; ?>"></label>
                <p class="description">Purges index rows where the order’s creation date is older than N days (runs daily).</p>
            </td>
        </tr>
    </table>

    <h3>Columns</h3>
    <button class="button" id="im-add-col">Add Column</button>
    <div id="im-cols" class="im-sort">
        <?php foreach ((array)$cfg['columns'] as $c): ?>
            <div class="im-row">
                <span class="dashicons dashicons-move im-handle"></span>
                <input type="text" name="col[col][]" placeholder="column_name" value="<?php echo esc_attr($c['col']); ?>">
                <select name="col[source][]">
                    <?php foreach (['core','meta','computed'] as $s){ echo '<option value="'.$s.'" '.selected($s,$c['source'],false).'>'.$s.'</option>'; } ?>
                </select>
                <input type="text" name="col[key][]" placeholder="key" value="<?php echo esc_attr($c['key']); ?>">
                <select name="col[type][]">
                    <?php foreach (['VARCHAR(190)','VARCHAR(200)','TEXT','INT(11)','BIGINT(20) UNSIGNED','DECIMAL(12,6)','DECIMAL(18,6)','DATETIME','TINYINT(1)'] as $t){ echo '<option value="'.$t.'" '.selected($t,$c['type'],false).'>'.$t.'</option>'; } ?>
                </select>
                <label><input type="checkbox" name="col[primary][]" value="1" <?php checked(!empty($c['primary'])); ?>> Primary</label>
                <label><input type="checkbox" name="col[index][]" value="1" <?php checked(!empty($c['index'])); ?>> Index</label>
                <label><input type="checkbox" name="col[unique][]" value="1" <?php checked(!empty($c['unique'])); ?>> Unique</label>
                <a href="#" class="im-remove">×</a>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="submit">
        <button type="submit" name="aaa_oc_im_orders_submit" class="button button-primary">Save</button>
    </p>
</form>

<!-- Utilities: Repair / Strict Rebuild / Reindex -->
<form method="post" style="display:inline;margin-right:8px">
    <?php wp_nonce_field('aaa_oc_im_orders_repair'); ?>
    <input type="hidden" name="aaa_oc_im_orders_repair" value="1">
    <button class="button">Repair Table</button>
</form>

<form method="post" style="display:inline;margin-right:8px">
    <?php wp_nonce_field('aaa_oc_im_orders_rebuild'); ?>
    <input type="hidden" name="aaa_oc_im_orders_rebuild" value="1">
    <button class="button button-secondary" onclick="return confirm('Strict rebuild will drop removed columns. Continue?');">Strict Rebuild</button>
</form>

<form method="post" style="display:inline">
    <?php wp_nonce_field('aaa_oc_im_orders_reindex'); ?>
    <input type="hidden" name="aaa_oc_im_orders_reindex" value="1">
    <button class="button">Reindex Now</button>
</form>
