<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Admin_Master {
    public static function init(){
        add_action('admin_menu', [__CLASS__,'menu']);
        add_action('admin_post_wfuim_save_master', [__CLASS__,'save_master']);
    }
    public static function menu(){
        add_menu_page(
            __('WF Index','aaa-wfuim'), __('WF Index','aaa-wfuim'),
            \AAA_WFUIM_Capabilities::CAP, 'wfuim-master',
            [__CLASS__,'render_master'], 'dashicons-index-card', 57
        );
    }
    public static function render_master(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        $enabled = \AAA_WFUIM_Registry::enabled();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WF Index Manager – Settings','aaa-wfuim');?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                <?php wp_nonce_field('wfuim_master','wfuim_master_nonce'); ?>
                <input type="hidden" name="action" value="wfuim_save_master"/>
                <label><input type="checkbox" name="enabled" value="1" <?php checked($enabled);?>/>
                    <?php esc_html_e('Enable Indexing (this site)','aaa-wfuim');?></label>
                <?php submit_button(__('Save','aaa-wfuim')); ?>
            </form>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=wfuim-tables'));?>"><?php esc_html_e('Manage Tables','aaa-wfuim');?></a>
            </p>
        </div>
        <?php
    }
    public static function save_master(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        check_admin_referer('wfuim_master','wfuim_master_nonce');
        update_option(\AAA_WFUIM_Registry::OPT_ENABLED, isset($_POST['enabled'])?1:0);
        wp_safe_redirect(admin_url('admin.php?page=wfuim-master&saved=1')); exit;
    }
}
AAA_WFUIM_Admin_Master::init();
