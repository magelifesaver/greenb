<?php
if ( ! defined('ABSPATH') ) exit;

require_once __DIR__.'/core/class-aaa-wfuim-capabilities.php';
require_once __DIR__.'/core/defs/class-aaa-wfuim-entities.php';
require_once __DIR__.'/core/class-aaa-wfuim-registry.php';
require_once __DIR__.'/index/class-aaa-wfuim-schema.php';
require_once __DIR__.'/index/class-aaa-wfuim-engine.php';
require_once __DIR__.'/admin/class-aaa-wfuim-admin-master.php';
require_once __DIR__.'/admin/class-aaa-wfuim-admin-tables-list.php';
require_once __DIR__.'/admin/class-aaa-wfuim-admin-table-edit.php';
require_once __DIR__.'/aaa-wfuim-assets-loader.php';

/** Ensure schema & boot engine */
add_action('plugins_loaded', function(){
    if ( \AAA_WFUIM_Registry::enabled() ) {
        foreach (\AAA_WFUIM_Registry::tables() as $t) { \AAA_WFUIM_Schema::ensure_table($t); }
        \AAA_WFUIM_Engine::boot();
    }
}, 8);

add_action('switch_blog', function(){
    if ( \AAA_WFUIM_Registry::enabled() ) {
        foreach (\AAA_WFUIM_Registry::tables() as $t) { \AAA_WFUIM_Schema::ensure_table($t); }
    }
}, 10);

/** Capture UID early for reliable purge on logout */
add_action('init', function(){ $GLOBALS['aaa_wfuim_last_uid'] = get_current_user_id(); }, 1);

/** Public helpers (global reindex API) */
if ( ! function_exists('wfuim_reindex_all') ) {
    function wfuim_reindex_all(){ do_action('aaa_wfuim_reindex_all'); }
    function wfuim_reindex_table($slug){ do_action('aaa_wfuim_reindex_table', $slug); }
    function wfuim_reindex_object($entity, $object_id){ do_action('aaa_wfuim_reindex_object', $entity, (int)$object_id); }
}
