<?php
/**
 * Plugin Name: DDD Troubleshooter Pro (Caching)
 * Plugin URI:  https://example.com
 * Description: Extends Dev Troubleshooter with File & Text Search and cache-flush commands.
 * Version:     2.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Dev_Troubleshooter_Pro {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_dt_search', [ __CLASS__, 'ajax_search' ] );
        add_action( 'wp_ajax_dt_flush_cache', [ __CLASS__, 'ajax_flush_cache' ] );
    }

    public static function add_admin_page() {
        add_menu_page(
            'Dev Troubleshooter Pro',
            'Dev TS Pro',
            'manage_options',
            'dt-pro',
            [ __CLASS__, 'render_admin_page' ],
            'dashicons-admin-tools',
            81
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'toplevel_page_dt-pro' !== $hook ) return;
        wp_enqueue_script('dt-pro-js', plugin_dir_url(__FILE__).'assets/js/dt-pro.js', ['jquery'], '2.0.0', true);
        wp_localize_script('dt-pro-js','DTPro_Ajax',[
            'ajax_url'=>admin_url('admin-ajax.php'),
            'nonce_search'=>wp_create_nonce('dt-search-nonce'),
            'nonce_flush'=>wp_create_nonce('dt-flush-nonce'),
        ]);
        wp_enqueue_style('dt-pro-css', plugin_dir_url(__FILE__).'assets/css/dt-pro.css', [], '2.0.0');
    }

    public static function render_admin_page() {
        // plugin list
        $active = get_option('active_plugins',[]);
        $sitewide = is_multisite()?array_keys(get_site_option('active_sitewide_plugins',[])):[];
        $all = array_unique(array_merge($active,$sitewide)); sort($all);
        ?>
        <div class="wrap">
          <h1>Dev Troubleshooter Pro</h1>
          <h2>Quick Actions</h2>
          <button id="dt-flush-cache" class="button">Flush WP Cache</button>
          <button id="dt-flush-rewrite" class="button">Flush Rewrite Rules</button>

          <h2>File & Text Search</h2>
          <p>
            <label for="dt-search-plugin">Plugin:</label>
            <select id="dt-search-plugin">
              <option value="">-- select --</option>
              <?php foreach($all as $pl){ printf('<option value="%s">%s</option>',esc_attr($pl),esc_html($pl)); } ?>
            </select>
          </p>
          <p>
            <label>Mode:</label>
            <label><input type="radio" name="dt-mode" value="filename" checked/> Filename</label>
            <label><input type="radio" name="dt-mode" value="content"/> Content</label>
          </p>
          <p>
            <label for="dt-search-term">Search term:</label>
            <input type="text" id="dt-search-term" style="width:300px;" />
          </p>
          <p>
            <button id="dt-search-btn" class="button button-primary">Search</button>
          </p>
          <div id="dt-search-results"></div>
        </div>
        <?php
    }

    public static function ajax_search() {
        check_ajax_referer('dt-search-nonce','nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $pl = sanitize_text_field($_POST['plugin']);
        $term = sanitize_text_field($_POST['term']);
        $mode = $_POST['mode']==='filename'?'filename':'content';
        $base = WP_PLUGIN_DIR.'/'.dirname($pl);
        if(!is_dir($base)) wp_send_json_error('Plugin not found');
        $res = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
        foreach($it as $f){ if(!$f->isFile()) continue;
            $rel = str_replace(WP_PLUGIN_DIR.'/','',$f->getPathname());
            if($mode==='filename'){
                if(stripos($rel,$term)!==false) $res[] = $rel;
            } else {
                $lines = file($f->getPathname());
                foreach($lines as $i=>$line){ if(stripos($line,$term)!==false){
                    $res[$rel][] = sprintf('%d: %s', $i+1, esc_html($line));
                }}
            }
        }
        wp_send_json_success($res);
    }

    public static function ajax_flush_cache() {
        check_ajax_referer('dt-flush-nonce','nonce');
        if(!current_user_can('manage_options')) wp_send_json_error();
        if(function_exists('wp_cache_flush')) wp_cache_flush();
        flush_rewrite_rules();
        wp_send_json_success('Done');
    }
}
Dev_Troubleshooter_Pro::init();
