<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Admin_Tables_List {
    public static function init(){
        add_action('admin_menu', [__CLASS__,'menu']);
        add_action('admin_post_wfuim_delete_table', [__CLASS__,'delete_table']);
        add_action('admin_post_wfuim_repair_table', [__CLASS__,'repair_table']);
        add_action('admin_post_wfuim_reindex_table', [__CLASS__,'reindex_table']);
    }
    public static function menu(){
        add_submenu_page('wfuim-master', __('All Tables','aaa-wfuim'), __('All Tables','aaa-wfuim'),
            \AAA_WFUIM_Capabilities::CAP, 'wfuim-tables', [__CLASS__,'render']);
        add_submenu_page('wfuim-master', __('Add New','aaa-wfuim'), __('Add New','aaa-wfuim'),
            \AAA_WFUIM_Capabilities::CAP, 'wfuim-table-edit', ['AAA_WFUIM_Admin_Table_Edit','render']);
    }
    public static function render(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        $tables = \AAA_WFUIM_Registry::tables();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Index Tables','aaa-wfuim');?></h1>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=wfuim-table-edit'));?>"><?php esc_html_e('Add New','aaa-wfuim');?></a>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wfuim_reindex_table&slug=_all'), 'wfuim_reindex_table','wfuim_reindex_table_nonce'));?>"><?php esc_html_e('Reindex ALL Tables','aaa-wfuim');?></a>
            </p>
            <table class="widefat striped">
                <thead><tr><th><?php _e('Name');?></th><th><?php _e('Slug');?></th><th><?php _e('Entity');?></th><th><?php _e('Status');?></th><th><?php _e('Actions');?></th></tr></thead>
                <tbody>
                <?php if (!$tables){ echo '<tr><td colspan="5">'.esc_html__('No tables yet.','aaa-wfuim').'</td></tr>'; } ?>
                <?php foreach($tables as $slug=>$t){ ?>
                    <tr>
                        <td><?php echo esc_html($t['name']);?></td>
                        <td><code><?php echo esc_html($slug);?></code></td>
                        <td><?php echo esc_html($t['entity']);?></td>
                        <td><?php echo !empty($t['enabled'])? 'Enabled':'Disabled';?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wfuim-table-edit&slug='.$slug));?>"><?php _e('Edit');?></a> |
                            <form style="display:inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                                <?php wp_nonce_field('wfuim_repair_table','wfuim_repair_table_nonce'); ?>
                                <input type="hidden" name="action" value="wfuim_repair_table"/>
                                <input type="hidden" name="slug" value="<?php echo esc_attr($slug);?>"/>
                                <button class="link-button"><?php _e('Repair Table');?></button>
                            </form> |
                            <form style="display:inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                                <?php wp_nonce_field('wfuim_reindex_table','wfuim_reindex_table_nonce'); ?>
                                <input type="hidden" name="action" value="wfuim_reindex_table"/>
                                <input type="hidden" name="slug" value="<?php echo esc_attr($slug);?>"/>
                                <button class="link-button"><?php _e('Reindex Table');?></button>
                            </form> |
                            <form style="display:inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('Delete this definition?');">
                                <?php wp_nonce_field('wfuim_delete_table','wfuim_delete_table_nonce'); ?>
                                <input type="hidden" name="action" value="wfuim_delete_table"/>
                                <input type="hidden" name="slug" value="<?php echo esc_attr($slug);?>"/>
                                <button class="link-button delete"><?php _e('Delete');?></button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    public static function delete_table(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        check_admin_referer('wfuim_delete_table','wfuim_delete_table_nonce');
        $slug = sanitize_title($_POST['slug'] ?? '');
        $all = \AAA_WFUIM_Registry::tables(); unset($all[$slug]); \AAA_WFUIM_Registry::save_tables($all);
        wp_safe_redirect(admin_url('admin.php?page=wfuim-tables&deleted=1')); exit;
    }
    public static function repair_table(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        check_admin_referer('wfuim_repair_table','wfuim_repair_table_nonce');
        $slug = sanitize_title($_POST['slug'] ?? '');
        $t = \AAA_WFUIM_Registry::table($slug);
        if ($t) \AAA_WFUIM_Schema::ensure_table($t);
        wp_safe_redirect(admin_url('admin.php?page=wfuim-tables&repaired=1')); exit;
    }
    public static function reindex_table(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        check_admin_referer('wfuim_reindex_table','wfuim_reindex_table_nonce');
        $slug = sanitize_title($_POST['slug'] ?? '');
        if ($slug === '_all') { \AAA_WFUIM_Engine::reindex_all(); }
        else { \AAA_WFUIM_Engine::reindex_table($slug); }
        wp_safe_redirect(admin_url('admin.php?page=wfuim-tables&reindex=1')); exit;
    }
}
AAA_WFUIM_Admin_Tables_List::init();
