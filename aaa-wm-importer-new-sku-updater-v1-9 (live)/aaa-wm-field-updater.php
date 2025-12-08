<?php
/**
 * Plugin Name: AAA WM Importer Sync By New Sku v1-9s (extended)
 * Description: Matches products by lkd_wm_new_sku (CSV "id") and updates
 *              selected WM‑table columns, prices, slug, link, etc.  Adds support
 *              for importing brand, category, strain and body fields.  These extra
 *              columns are displayed in the staging preview and applied to the
 *              product during update.  Brands are assigned to the berocket_brand
 *              taxonomy, categories to the product categories and strains to
 *              the dedicated strain attribute taxonomy.  If any of the terms
 *              cannot be found the row is skipped and reported.
 * Version:     1.9.1
 * Requires:    PHP 7.4+, WooCommerce
 */

/*
 * This plugin extends the standard AAA WM Importer Sync tool by introducing
 * support for additional import columns.  When uploading a CSV file the
 * following fields will now be read and stored in the staging table:
 *
 *  - brand    (column 4 in the supplied sample) will be applied to the
 *             "berocket_brand" taxonomy.  The term must already exist; new
 *             terms are never created.  Matching ignores trademark symbols
 *             and diacritics (accents) to provide robust lookups.
 *  - category (column 12) will be applied to the built‑in product_cat
 *             taxonomy.  Only one category is accepted per row.  As with
 *             brands, categories must already exist; otherwise the row is
 *             skipped.
 *  - strain   (column 10) will be applied to a strain product attribute
 *             taxonomy.  The attribute slug is assumed to be "pa_strain" –
 *             adjust this constant if your store uses a different slug.  Like
 *             the other terms, strains must already exist; new attributes
 *             are never created.
 *  - body     (column 2) will be copied into the product's short description
 *             (post_excerpt).
 *
 * The plugin adds these fields to the staging database table and displays
 * them in the unmatched and matched preview grids.  During the matching
 * process, if a term cannot be found the row remains unmatched and a
 * notification is shown indicating how many rows were skipped due to missing
 * terms.  Skipped rows remain available in the unmatched list for review.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_WM_Updater_V191 {
    const STAGING   = 'aaa_wm_import_staging_19';
    const WM_TABLE  = 'lkd_wm_fields';
    const LOG_FILE  = 'aaa_wm_updater.log';
    const PK_COL    = 'ID';
    // taxonomy slugs used for term assignments
    const BRAND_TAX   = 'berocket_brand';
    const CAT_TAX     = 'product_cat';
    const STRAIN_TAX  = 'pa_strain';

    private static array $allowed    = [];
    private static bool  $updated_any = false;
    private static int   $skipped    = 0;

    /* ── boot ───────────────────────────────────────── */
    public static function init(): void {
        register_activation_hook(__FILE__, [ __CLASS__, 'activate' ]);
        add_action('admin_menu',            [ __CLASS__, 'admin_page' ]);
    }
    public static function activate(): void {
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
        // extend staging table with additional columns
        $wpdb->query(sprintf(
            "CREATE TABLE IF NOT EXISTS %s%s (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                csv_row_id  VARCHAR(50),
                product_id  VARCHAR(50),
                slug        VARCHAR(200),
                name        TEXT,
                published   VARCHAR(5),
                unit_price  DECIMAL(10,2),
                price_rule_adjustment_value DECIMAL(6,2),
                brand       VARCHAR(200),
                category    VARCHAR(200),
                strain      VARCHAR(200),
                body        TEXT,
                matched     TINYINT(1) DEFAULT 0,
                PRIMARY KEY(id),
                KEY new_sku (csv_row_id)
            ) %s",
            $wpdb->prefix, self::STAGING, $wpdb->get_charset_collate()
        ));
        if ( ! file_exists(self::log_path()) ) file_put_contents(self::log_path(), '');
    }

    /* ── admin page ─────────────────────────────────── */
    public static function admin_page(): void {
        add_management_page('WM Product Updater 1.9U','AAA Product Import 1.9','manage_woocommerce','aaa-wm-updater-19',[ __CLASS__,'render' ]);
    }

    public static function render(): void {
        if ( ! current_user_can('manage_woocommerce') ) wp_die('No permission');
        global $wpdb; $s=$wpdb->prefix.self::STAGING;

        /* upload CSV */
        if(isset($_POST['wm_upload']) && check_admin_referer('wm_up_19') && !empty($_FILES['wm_csv']['tmp_name'])){
            $rows=array_map('str_getcsv',file($_FILES['wm_csv']['tmp_name']));
            $header=array_map('sanitize_key',array_shift($rows));
            $need=['id','published','name','product_id'];
            if($miss=array_diff($need,$header)){
                echo '<div class="notice notice-error"><p>Missing: '.esc_html(implode(', ',$miss)).'</p></div>';
            }else{
                $wpdb->query("TRUNCATE TABLE {$s}");
                foreach($rows as $csv){
                    $d=array_combine($header,$csv);
                    // Map additional fields based on known column names
                    $brand  = $d['brand_name']   ?? ($d['brand'] ?? '');
                    $category = $d['categories'] ?? ($d['category'] ?? '');
                    $strain = $d['strain_id']    ?? ($d['strain'] ?? '');
                    $body   = $d['body']         ?? '';
                    $wpdb->insert($s,[
                        'csv_row_id' => sanitize_text_field($d['id']),
                        'product_id' => sanitize_text_field($d['product_id']),
                        'slug'       => sanitize_text_field($d['slug']??''),
                        'name'       => sanitize_text_field($d['name']),
                        'published'  => in_array(strtolower(trim($d['published']??'')),['1','true','yes','active'],true)?'true':'false',
                        'unit_price' => isset($d['unit_price'])?(float)$d['unit_price']:null,
                        'price_rule_adjustment_value' => isset($d['price_rule_adjustment_value'])?(float)$d['price_rule_adjustment_value']:null,
                        'brand'      => sanitize_text_field($brand),
                        'category'   => sanitize_text_field($category),
                        'strain'     => sanitize_text_field($strain),
                        'body'       => sanitize_textarea_field($body)
                    ],[ '%s','%s','%s','%s','%s','%f','%f','%s','%s','%s','%s' ]);
                }
                echo '<div class="notice notice-success"><p>CSV staged.</p></div>';
            }
        }

        /* match */
        if(isset($_POST['wm_match']) && check_admin_referer('wm_match_19')){
            self::$allowed=array_flip(array_map('sanitize_key',$_POST['fields']??[]));
            self::$skipped = 0;
            $matched=self::process_matches();
            echo '<div class="notice notice-success"><p>WM NEW-SKU matches: '.esc_html($matched).'</p></div>';
            if(self::$skipped){
                echo '<div class="notice notice-warning"><p>Rows skipped due to missing brand/category/strain terms: '.esc_html(self::$skipped).'</p></div>';
            }
            if(self::$updated_any && function_exists('wp_cache_flush')){
                wp_cache_flush(); if(function_exists('wp_cache_flush_runtime')) wp_cache_flush_runtime();
                self::log('Object cache flushed.');
            }
        }

        /* clear */
        if(isset($_POST['wm_clear']) && check_admin_referer('wm_clear_19')){
            $wpdb->query("TRUNCATE TABLE {$s}");
            echo '<div class="notice notice-success"><p>Staging table cleared.</p></div>';
        }

        $total=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$s}");
        $todo =(int)$wpdb->get_var("SELECT COUNT(*) FROM {$s} WHERE matched=0");

        echo '<div class="wrap">';
        echo '<h1>AAA Product Import & Update V1.91</h1>';
        echo '<p><em>Log: <code>'.esc_html(self::log_path()).'</code></em></p>';

        /* forms */
        echo '<h2>Upload CSV</h2><form method="post" enctype="multipart/form-data">';
        wp_nonce_field('wm_up_19');
        echo '<input type="file" name="wm_csv" accept=".csv" required> ';
        submit_button('Upload & Stage','primary','wm_upload');
        echo '</form>';

        echo '<h2>Match & Update (by <strong>WM NEW SKU</strong>)</h2><form method="post">';
        wp_nonce_field('wm_match_19');
        $fields=[
            'lkd_wm_og_price'                    =>'WM OG Price',
            'regular_price'                      =>'Regular Price',
            'sale_price'                         =>'Sale Price',
            'lkd_wm_price_rule_adjustment_value' =>'Discount (%)',
            'lkd_wm_og_slug'                     =>'WM OG Slug',
            'lkd_wm_link'                        =>'WM Admin Link',
            'lkd_wm_status'                      =>'WM Status',
            'lkd_wm_product_id'                  =>'WM Product ID',
            'lkd_wm_og_name'                     =>'OG Name'
            ,
            // Added fields for optional updates of brand, category, strain and body.
            // When these boxes are selected the importer will attempt to assign the
            // corresponding taxonomy terms or product short description.  If unchecked
            // the values from the CSV are ignored.
            'brand'                              =>'Brand',
            'category'                           =>'Category',
            'strain'                             =>'Strain',
            'body'                               =>'Short Description'
        ];
        foreach($fields as $val=>$label){
            echo '<label><input type="checkbox" name="fields[]" value="'.esc_attr($val).'"> '.esc_html($label).'</label><br>';
        }
        echo '<br>'; submit_button('Run Match & Update','secondary','wm_match'); echo '</form>';

        echo '<form method="post" style="margin-top:12px">';
        wp_nonce_field('wm_clear_19'); submit_button('Clear Staging Table','delete','wm_clear'); echo '</form>';

        echo '<p>Total staged: <strong>'.$total.'</strong> &nbsp; Unmatched: <strong>'.$todo.'</strong></p>';

        /* unmatched list */
        $un=$wpdb->get_results("SELECT * FROM {$s} WHERE matched=0 ORDER BY id",ARRAY_A);
        echo '<h2>Unmatched rows ('.count($un).')</h2>';
        if($un){
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>WM NEW SKU</th><th>Name</th><th>Published</th><th>Price</th><th>Discount</th><th>Slug</th><th>Product ID</th>';
            echo '<th>Brand</th><th>Category</th><th>Strain</th><th>Body</th>';
            echo '</tr></thead><tbody>';
            foreach($un as $r){
                printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    esc_html($r['csv_row_id']),
                    esc_html($r['name']),
                    esc_html($r['published']),
                    esc_html($r['unit_price']),
                    esc_html($r['price_rule_adjustment_value']),
                    esc_html($r['slug']),
                    esc_html($r['product_id']),
                    esc_html($r['brand']),
                    esc_html($r['category']),
                    esc_html($r['strain']),
                    esc_html($r['body'])
                );
            }
            echo '</tbody></table>';
        }else echo '<p><em>None.</em></p>';

        /* matched list */
        $hit=$wpdb->get_results("SELECT * FROM {$s} WHERE matched=1 ORDER BY id",ARRAY_A);
        echo '<h2>Updated rows ('.count($hit).')</h2>';
        if($hit){
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>WM NEW SKU</th><th>Name</th><th>Price</th><th>Discount</th><th>Slug</th>';
            echo '<th>Brand</th><th>Category</th><th>Strain</th><th>Body</th>';
            echo '</tr></thead><tbody>';
            foreach($hit as $r){
                printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    esc_html($r['csv_row_id']),
                    esc_html($r['name']),
                    esc_html($r['unit_price']),
                    esc_html($r['price_rule_adjustment_value']),
                    esc_html($r['slug']),
                    esc_html($r['brand']),
                    esc_html($r['category']),
                    esc_html($r['strain']),
                    esc_html($r['body'])
                );
            }
            echo '</tbody></table>';
        }else echo '<p><em>None.</em></p>';

        echo '</div>';
    }

    /* ── match & helpers */
    private static function process_matches():int{
        global $wpdb; $s=$wpdb->prefix.self::STAGING; $tbl=$wpdb->prefix.self::WM_TABLE; $ok=0;
        foreach($wpdb->get_results("SELECT * FROM {$s} WHERE matched=0 AND csv_row_id<>''") as $r){
            $pid=(int)$wpdb->get_var($wpdb->prepare("SELECT ".self::PK_COL." FROM {$tbl} WHERE lkd_wm_new_sku=%s LIMIT 1",$r->csv_row_id));
            if($pid){
                if(self::finalize($pid,$r)){
                    $ok++;
                } else {
                    // skipped; leave unmatched
                    self::$skipped++;
                }
            }
        }
        return $ok;
    }
    /**
     * Perform all updates for a matched row.  Returns true if updated, false if
     * the row should be skipped due to missing terms.
     */
    private static function finalize(int $post_id,$row):bool{
        // update Weedmaps table columns
        self::update_wm_table($post_id,$row);
        // update WooCommerce price metadata
        self::update_price_meta($post_id,$row);
        // update taxonomy assignments and body
        $skip_terms = self::update_terms_and_body($post_id,$row);
        if($skip_terms){
            // do not mark matched; do not increment counters
            return false;
        }
        self::$updated_any=true;
        global $wpdb; $wpdb->update($wpdb->prefix.self::STAGING,['matched'=>1],['id'=>$row->id],['%d'],['%d']);
        return true;
    }
    private static function update_wm_table(int $post_id,$row):void{
        global $wpdb; $tbl=$wpdb->prefix.self::WM_TABLE;
        $map=[
            'lkd_wm_og_price'                    =>'unit_price',
            'lkd_wm_price_rule_adjustment_value' =>'price_rule_adjustment_value',
            'lkd_wm_og_slug'                     =>'slug',
            'lkd_wm_status'                      =>'published',
            'lkd_wm_product_id'                  =>'product_id',
            'lkd_wm_og_name'                     =>'name'
        ];
        $data=[]; foreach($map as $dest=>$src) if(isset(self::$allowed[$dest])) $data[$dest]=$row->$src;
        if(isset(self::$allowed['lkd_wm_link'])){
            $data['lkd_wm_link']=$row->slug? 'https://weedmaps.com/new_admin/deliveries/lo-key-delivery/menu_items/'.$row->slug.'/edit?offset=0&sort=-updated_at':'';
        }
        if($data){ $wpdb->update($tbl,$data,[self::PK_COL=>$post_id],array_fill(0,count($data),'%s'),['%d']); }
    }
    private static function update_price_meta(int $post_id,$row):void{
        if(!isset(self::$allowed['regular_price']) && !isset(self::$allowed['sale_price'])) return;
        $unit=(float)$row->unit_price; $pct=is_numeric($row->price_rule_adjustment_value)?(float)$row->price_rule_adjustment_value:0; $sale=(isset(self::$allowed['sale_price'])&&$pct>0)?round($unit-($unit*$pct/100),2):'';
        delete_post_meta($post_id,'_regular_price'); delete_post_meta($post_id,'_sale_price'); delete_post_meta($post_id,'_price');
        if(isset(self::$allowed['regular_price'])) add_post_meta($post_id,'_regular_price',$unit,true);
        if($sale!=='') add_post_meta($post_id,'_sale_price',$sale,true);
        add_post_meta($post_id,'_price',$sale!==''?$sale:$unit,true);
    }
    /**
     * Normalize a term by removing trademark symbols and diacritics.
     */
    private static function normalize_term(string $term): string {
        // remove trademark and similar symbols
        $term = str_replace(['™','®','©'], '', $term);
        // convert accents to ASCII
        $converted = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$term);
        if($converted !== false){
            $term = $converted;
        }
        // lower case
        $term = strtolower($term);
        // collapse whitespace
        $term = trim(preg_replace('/\s+/', ' ', $term));
        return $term;
    }
    /**
     * Attempt to find a term in a given taxonomy by comparing a normalized
     * version of the name.  Returns a WP_Term on success or null on failure.
     */
    private static function find_existing_term(string $taxonomy, string $search_name){
        $target = self::normalize_term($search_name);
        if(!$target) return null;
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);
        if(is_wp_error($terms)) return null;
        foreach($terms as $term){
            $normalized = self::normalize_term($term->name);
            if($normalized === $target){
                return $term;
            }
        }
        return null;
    }
    /**
     * Update brand, category and strain terms as well as the product short
     * description.  Returns true if a required term was missing and the row
     * should be skipped.
     */
    private static function update_terms_and_body(int $post_id, $row): bool {
        // Only skip a row if a required term is missing *and* that field is selected
        $missing = false;
        // brand assignment (optional)
        if(!empty($row->brand) && isset(self::$allowed['brand'])){
            $brand_term = self::find_existing_term(self::BRAND_TAX, $row->brand);
            if($brand_term){
                wp_set_object_terms($post_id, [$brand_term->term_id], self::BRAND_TAX, false);
            } else {
                $missing = true;
            }
        }
        // category assignment (optional)
        if(!empty($row->category) && isset(self::$allowed['category'])){
            // categories may be separated by '>' or '|' - pick the last segment
            $cat_raw = $row->category;
            if(strpos($cat_raw, '>') !== false){
                $parts = array_map('trim', explode('>', $cat_raw));
                $cat_raw = end($parts);
            } elseif(strpos($cat_raw, '|') !== false){
                $parts = array_map('trim', explode('|', $cat_raw));
                $cat_raw = end($parts);
            }
            $cat_term = self::find_existing_term(self::CAT_TAX, $cat_raw);
            if($cat_term){
                wp_set_object_terms($post_id, [$cat_term->term_id], self::CAT_TAX, false);
            } else {
                $missing = true;
            }
        }
        // strain assignment (optional)
        if(!empty($row->strain) && isset(self::$allowed['strain'])){
            $strain_term = self::find_existing_term(self::STRAIN_TAX, $row->strain);
            if($strain_term){
                wp_set_object_terms($post_id, [$strain_term->term_id], self::STRAIN_TAX, false);
            } else {
                $missing = true;
            }
        }
        // update short description (optional)
        if(!empty($row->body) && isset(self::$allowed['body'])){
            wp_update_post([
                'ID'           => $post_id,
                'post_excerpt' => wp_kses_post($row->body)
            ]);
        }
        return $missing;
    }
    private static function log(string $m):void{ file_put_contents(self::log_path(),'['.date_i18n('Y-m-d H:i:s')."] {$m}\n",FILE_APPEND); }
    private static function log_path():string{ return plugin_dir_path(__FILE__).self::LOG_FILE; }
}

AAA_WM_Updater_V191::init();