<?php
if ( ! defined('ABSPATH') ) exit;
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/admin/tabs/aaa-oc-indexmanager-products.php
 * Products Tab — PRG pattern, enable + triggers + qualifiers,
 * and utility actions: Repair Table, Strict Rebuild, Reindex Now.
 */

$base_url = add_query_arg([
    'page' => 'aaa-oc-core-settings',
    'tab'  => 'aaa-oc-indexmanager-products',
], admin_url('admin.php'));

/* ---------- SAVE (PRG) ---------- */
if ( 'POST' === $_SERVER['REQUEST_METHOD']
     && isset($_POST['aaa_oc_im_products_submit'])
     && check_admin_referer('aaa_oc_im_products_nonce') ) {

    $in = [
        'enabled'  => ! empty($_POST['enabled']) ? 1 : 0,
        'triggers' => array_values(array_filter(array_map('sanitize_text_field', $_POST['triggers'] ?? []))),
        // Qualifiers
        'allowed_stock_statuses' => array_values(array_filter(array_map('sanitize_text_field', $_POST['allowed_stock_statuses'] ?? []))),
        'purge_excluded'         => ! empty($_POST['purge_excluded']) ? 1 : 0,
        // Columns
        'columns'  => [],
    ];

    $cols = $_POST['col'] ?? [];
    $N    = count($cols['col'] ?? []);
    for ( $i = 0; $i < $N; $i++ ) {
        $name = isset($cols['col'][ $i ]) ? sanitize_key($cols['col'][ $i ]) : '';
        if ( $name === '' ) {
            continue;
        }

        $source     = sanitize_text_field($cols['source'][ $i ] ?? 'meta');
        $key        = sanitize_text_field($cols['key'][ $i ] ?? '');
        $type       = sanitize_text_field($cols['type'][ $i ] ?? 'VARCHAR(190)');
        $is_primary = ! empty($cols['primary'][ $i ]);
        $is_index   = ! empty($cols['index'][ $i ]);
        $is_unique  = ! empty($cols['unique'][ $i ]);

        $row = [
            'col'     => $name,
            'source'  => $source,
            'key'     => $key,
            'type'    => $type,
            'primary' => $is_primary,
            'index'   => $is_index,
            'unique'  => $is_unique,
        ];

        // External table mapping when source = table
        if ( $source === 'table' ) {
            $row['ext_table']   = sanitize_key($cols['ext_table'][ $i ] ?? '');
            $row['ext_fk_col']  = sanitize_key($cols['ext_fk_col'][ $i ] ?? '');
            $row['ext_val_col'] = sanitize_key($cols['ext_val_col'][ $i ] ?? '');
        }

        $in['columns'][] = $row;
    }

    if ( empty($in['columns']) ) {
        $in = AAA_OC_IndexManager_Helpers::defaults('products');
    }

    AAA_OC_IndexManager_Helpers::set_opt('products', $in);
    AAA_OC_IndexManager_Table_Installer::ensure('products');

    wp_safe_redirect( add_query_arg('saved', 1, $base_url) );
    exit;
}

/* ---------- REPAIR (ensure) ---------- */
if ( isset($_POST['aaa_oc_im_products_repair']) && check_admin_referer('aaa_oc_im_products_repair') ) {
    AAA_OC_IndexManager_Table_Installer::ensure('products');
    wp_safe_redirect( add_query_arg('repaired', 1, $base_url) );
    exit;
}

/* ---------- STRICT REBUILD (drop removed columns) ---------- */
if ( isset($_POST['aaa_oc_im_products_rebuild']) && check_admin_referer('aaa_oc_im_products_rebuild') ) {
    AAA_OC_IndexManager_Table_Installer::rebuild('products', true);
    wp_safe_redirect( add_query_arg('rebuilt', 1, $base_url) );
    exit;
}

/* ---------- REINDEX NOW ---------- */
if ( isset($_POST['aaa_oc_im_products_reindex']) && check_admin_referer('aaa_oc_im_products_reindex') ) {
    AAA_OC_IndexManager_Table_Indexer::reindex_table('products');
    wp_safe_redirect( add_query_arg('reindexed', 1, $base_url) );
    exit;
}

/* ---------- LOAD ---------- */
$cfg = AAA_OC_IndexManager_Helpers::get_opt('products');

if ( ! empty($_GET['saved']) )     echo '<div class="notice notice-success"><p>Products settings saved.</p></div>';
if ( ! empty($_GET['repaired']) )  echo '<div class="notice notice-info"><p>Products table repaired.</p></div>';
if ( ! empty($_GET['rebuilt']) )   echo '<div class="notice notice-warning"><p>Products table rebuilt (strict).</p></div>';
if ( ! empty($_GET['reindexed']) ) echo '<div class="notice notice-info"><p>Products reindexed.</p></div>';

/* Triggers & qualifiers */
$all_triggers = [
    'save_post_product',
    'woocommerce_product_set_stock',
    'updated_post_meta',
    'woocommerce_update_product',
    'woocommerce_admin_process_product_object',
    'woocommerce_product_quick_edit_save',
    'woocommerce_after_product_object_save',
    'set_object_terms',
    'trashed_post',
    'untrashed_post',
];
$stock_options = [
    'instock'     => 'In stock',
    'onbackorder' => 'On backorder',
    'outofstock'  => 'Out of stock',
];
?>
<h2>Index Manager — Products</h2>

<form method="post">
    <?php wp_nonce_field('aaa_oc_im_products_nonce'); ?>
    <table class="form-table">
        <tr>
            <th>Enable</th>
            <td>
                <label>
                    <input type="checkbox" name="enabled" value="1" <?php checked(! empty($cfg['enabled'])); ?>>
                    Enable Products Index
                </label>
            </td>
        </tr>

        <tr>
            <th>Triggers</th>
            <td>
                <?php foreach ( $all_triggers as $h ) : ?>
                    <label style="margin-right:12px">
                        <input type="checkbox" name="triggers[]" value="<?php echo esc_attr($h); ?>"
                            <?php checked(in_array($h, (array) $cfg['triggers'], true)); ?>>
                        <?php echo esc_html($h); ?>
                    </label>
                <?php endforeach; ?>
            </td>
        </tr>

        <tr>
            <th>Qualifiers</th>
            <td>
                <strong>Allowed stock statuses:</strong><br>
                <?php foreach ( $stock_options as $key => $label ) : ?>
                    <label style="margin-right:12px">
                        <input type="checkbox" name="allowed_stock_statuses[]" value="<?php echo esc_attr($key); ?>"
                            <?php checked(in_array($key, (array) $cfg['allowed_stock_statuses'], true)); ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php endforeach; ?>
                <p>
                    <label>
                        <input type="checkbox" name="purge_excluded" value="1" <?php checked(! empty($cfg['purge_excluded'])); ?>>
                        Purge row if product no longer qualifies
                    </label>
                </p>
            </td>
        </tr>
    </table>

    <h3>Columns</h3>
    <p class="description">
        Sources:
        <code>core</code> (Woo product fields),
        <code>meta</code> (postmeta),
        <code>computed</code> (e.g. <code>updated_at</code>),
        and <code>table</code> for values joined from another table via product ID
        (configure table suffix, ID column, and value column).
    </p>

    <button class="button" id="im-add-col">Add Column</button>
    <div id="im-cols" class="im-sort">
        <?php foreach ( (array) $cfg['columns'] as $c ) :
            $col         = esc_attr($c['col'] ?? '');
            $src         = esc_attr($c['source'] ?? 'meta');
            $key         = esc_attr($c['key'] ?? '');
            $type        = esc_attr($c['type'] ?? 'VARCHAR(190)');
            $prim        = ! empty($c['primary']);
            $idx         = ! empty($c['index']);
            $uniq        = ! empty($c['unique']);
            $ext_table   = esc_attr($c['ext_table']   ?? '');
            $ext_fk_col  = esc_attr($c['ext_fk_col']  ?? '');
            $ext_val_col = esc_attr($c['ext_val_col'] ?? '');
        ?>
            <div class="im-row">
                <span class="dashicons dashicons-move im-handle"></span>

                <input type="text" name="col[col][]" placeholder="column_name" value="<?php echo $col; ?>">

                <select name="col[source][]" class="im-source">
                    <option value="core"     <?php selected($src, 'core'); ?>>core</option>
                    <option value="meta"     <?php selected($src, 'meta'); ?>>meta</option>
                    <option value="computed" <?php selected($src, 'computed'); ?>>computed</option>
                    <option value="table"    <?php selected($src, 'table'); ?>>table</option>
                </select>

                <span class="im-key-group"<?php if ( $src === 'table' ) echo ' style="display:none;"'; ?>>
                    <input type="text" name="col[key][]" placeholder="key or computed token"
                           value="<?php echo $key; ?>">
                </span>

                <span class="im-table-group"<?php if ( $src !== 'table' ) echo ' style="display:none;"'; ?>>
                    <input type="text" name="col[ext_table][]"  placeholder="table (suffix)"
                           value="<?php echo $ext_table; ?>">
                    <input type="text" name="col[ext_fk_col][]" placeholder="ID column"
                           value="<?php echo $ext_fk_col; ?>">
                    <input type="text" name="col[ext_val_col][]" placeholder="value column"
                           value="<?php echo $ext_val_col; ?>">
                </span>

                <select name="col[type][]">
                    <?php
                    $types = [
                        'VARCHAR(190)','VARCHAR(200)','TEXT','INT(11)','BIGINT(20) UNSIGNED',
                        'DECIMAL(12,6)','DECIMAL(18,6)','DATETIME','TINYINT(1)'
                    ];
                    foreach ( $types as $t ) {
                        echo '<option value="' . esc_attr($t) . '" ' . selected($type, $t, false) . '>' . esc_html($t) . '</option>';
                    }
                    ?>
                </select>

                <label><input type="checkbox" name="col[primary][]" value="1" <?php checked($prim); ?>> Primary</label>
                <label><input type="checkbox" name="col[index][]" value="1"   <?php checked($idx); ?>> Index</label>
                <label><input type="checkbox" name="col[unique][]" value="1"  <?php checked($uniq); ?>> Unique</label>
                <a href="#" class="im-remove">×</a>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="submit">
        <button type="submit" name="aaa_oc_im_products_submit" class="button button-primary">Save</button>
    </p>
</form>

<!-- Utilities: Repair / Strict Rebuild / Reindex -->
<form method="post" style="display:inline;margin-right:8px">
    <?php wp_nonce_field('aaa_oc_im_products_repair'); ?>
    <input type="hidden" name="aaa_oc_im_products_repair" value="1">
    <button class="button">Repair Table</button>
</form>

<form method="post" style="display:inline;margin-right:8px">
    <?php wp_nonce_field('aaa_oc_im_products_rebuild'); ?>
    <input type="hidden" name="aaa_oc_im_products_rebuild" value="1">
    <button class="button button-secondary"
        onclick="return confirm('Strict rebuild will drop removed columns. Continue?');">
        Strict Rebuild
    </button>
</form>

<form method="post" style="display:inline">
    <?php wp_nonce_field('aaa_oc_im_products_reindex'); ?>
    <input type="hidden" name="aaa_oc_im_products_reindex" value="1">
    <button class="button">Reindex Now</button>
</form>
