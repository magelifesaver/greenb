<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Admin_Table_Edit {
    public static function init(){ add_action('admin_post_wfuim_save_table', [__CLASS__, 'save']); }

    public static function render(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        $slug = sanitize_title($_GET['slug'] ?? '');
        $is_edit = (bool) $slug;
        $table = $is_edit ? \AAA_WFUIM_Registry::table($slug) : \AAA_WFUIM_Registry::new_table_skeleton('New Index','user');
        if ($is_edit) $table['slug'] = $slug;
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit? esc_html__('Edit Index Table','aaa-wfuim') : esc_html__('Add New Index Table','aaa-wfuim'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                <?php wp_nonce_field('wfuim_save_table','wfuim_save_table_nonce'); ?>
                <input type="hidden" name="action" value="wfuim_save_table"/>
                <input type="hidden" name="old_slug" value="<?php echo esc_attr($slug); ?>"/>

                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Table Name'); ?></label></th>
                        <td><input name="name" id="name" class="regular-text" value="<?php echo esc_attr($table['name']);?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php _e('Slug'); ?></label></th>
                        <td><input name="slug" id="slug" class="regular-text" value="<?php echo esc_attr($table['slug']);?>" <?php disabled($is_edit);?>/></td>
                    </tr>
                    <tr>
                        <th><label for="entity"><?php _e('Entity'); ?></label></th>
                        <td>
                            <select name="entity" id="entity">
                                <?php foreach(['user'=>'User','order'=>'Order','product'=>'Product'] as $k=>$v){ ?>
                                    <option value="<?php echo esc_attr($k);?>" <?php selected($k,$table['entity']);?>><?php echo esc_html($v);?></option>
                                <?php } ?>
                            </select>
                            <label><input type="checkbox" name="enabled" value="1" <?php checked(!empty($table['enabled']));?>/> <?php _e('Enabled');?></label>
                        </td>
                    </tr>

                    <?php if ($table['entity']==='user'){ ?>
                    <tr>
                        <th><?php _e('Session mode'); ?></th>
                        <td>
                            <label>
                              <input type="checkbox" name="session_only" value="1" <?php checked(!empty($table['session_only']));?>/>
                              <?php _e('Session-only (index on login, purge on logout; ignore all other triggers & API)'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('User-only options'); ?></th>
                        <td>
                            <label><input type="checkbox" name="index_on_login" value="1" <?php checked(!empty($table['index_on_login']));?>/> <?php _e('Index on login'); ?></label><br/>
                            <label><input type="checkbox" name="purge_on_logout" value="1" <?php checked(!empty($table['purge_on_logout']));?>/> <?php _e('Purge on logout'); ?></label>
                        </td>
                    </tr>
                    <?php } ?>

                    <tr>
                        <th><?php _e('Triggers'); ?></th>
                        <td>
                            <?php
                            $defaults = \AAA_WFUIM_Entities::default_triggers($table['entity']);
                            $selected = (array)($table['triggers'] ?? []);
                            $choices = array_unique(array_merge($defaults, $selected));
                            foreach ($choices as $hook){
                                echo '<label style="display:inline-block;margin:0 12px 6px 0"><input type="checkbox" name="triggers[]" value="'.esc_attr($hook).'" '.checked(in_array($hook,$selected,true),true,false).'/> '.esc_html($hook).'</label>';
                            }
                            ?>
                        </td>
                    </tr>

                    <?php if ($table['entity']==='user'){ ?>
                    <tr>
                        <th><?php _e('Coordinate keys (CSV)'); ?></th>
                        <td>
                            <label><?php _e('Latitude'); ?> <input type="text" name="lat_keys" class="regular-text" value="<?php echo esc_attr($table['lat_keys']);?>"/></label><br/>
                            <label><?php _e('Longitude'); ?> <input type="text" name="lng_keys" class="regular-text" value="<?php echo esc_attr($table['lng_keys']);?>"/></label>
                            <p class="description"><?php _e('Ignored in session-only mode if you don’t use computed lat/lng columns.'); ?></p>
                        </td>
                    </tr>
                    <?php } ?>
                </table>

                <h2><?php _e('Columns'); ?></h2>
                <button class="button" id="wfuim-add-col"><?php _e('Add Column'); ?></button>
                <div id="wfuim-cols" class="wfuim-sort">
                    <?php foreach ((array)$table['columns'] as $c){ echo self::col_row($c); } ?>
                </div>

                <?php submit_button(__('Save Table','aaa-wfuim')); ?>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=wfuim-tables'));?>">&larr; <?php _e('Back to All Tables'); ?></a></p>
            </form>
        </div>
        <?php
    }

    protected static function col_row($c){
        $col=esc_attr($c['col']??''); $src=esc_attr($c['source']??'meta'); $key=esc_attr($c['key']??'');
        $type=esc_attr($c['type']??'VARCHAR(190)'); $prim=!empty($c['primary']); $idx=!empty($c['index']); $uniq=!empty($c['unique']);
        ob_start(); ?>
        <div class="wfuim-row wfuim-col">
            <span class="dashicons dashicons-move wfuim-handle"></span>
            <input type="text" name="col[col][]" placeholder="column_name" value="<?php echo $col;?>" />
            <select name="col[source][]">
                <?php foreach(['core','meta','computed'] as $s){ echo '<option value="'.$s.'" '.selected($s,$src,false).'>'.$s.'</option>'; } ?>
            </select>
            <input type="text" name="col[key][]" placeholder="key or computed token" value="<?php echo $key;?>"/>
            <select name="col[type][]">
                <?php $types=['VARCHAR(190)','VARCHAR(200)','TEXT','INT(11)','BIGINT(20) UNSIGNED','DECIMAL(12,6)','DECIMAL(18,6)','DATETIME','TINYINT(1)','BOOLEAN'];
                foreach($types as $t){ echo '<option value="'.$t.'" '.selected($t,$type,false).'>'.$t.'</option>'; } ?>
            </select>
            <label><input type="checkbox" name="col[primary][]" value="1" <?php checked($prim);?>/> <?php _e('Primary');?></label>
            <label><input type="checkbox" name="col[index][]" value="1" <?php checked($idx);?>/> <?php _e('Index');?></label>
            <label><input type="checkbox" name="col[unique][]" value="1" <?php checked($uniq);?>/> <?php _e('Unique');?></label>
            <a href="#" class="wfuim-remove">×</a>
        </div>
        <?php return ob_get_clean();
    }

    public static function save(){
        if ( ! \AAA_WFUIM_Capabilities::can_manage() ) wp_die('Forbidden');
        check_admin_referer('wfuim_save_table','wfuim_save_table_nonce');

        $old = sanitize_title($_POST['old_slug'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? 'New Index');
        $slug = sanitize_title($_POST['slug'] ?? $name);
        $entity = in_array($_POST['entity'] ?? 'user',['user','order','product'],true)? $_POST['entity'] : 'user';

        $session_only = !empty($_POST['session_only']) ? 1 : 0;

        // If session-only (user), force triggers to login/logout only
        $triggers = array_values(array_filter(array_map('sanitize_text_field', $_POST['triggers'] ?? [])));
        if ($entity==='user' && $session_only) {
            $triggers = ['wp_login','wp_logout_purge'];
        }

        $t = [
            'name'=>$name,
            'slug'=>$slug,
            'entity'=>$entity,
            'enabled'=> isset($_POST['enabled'])?1:0,
            'session_only'=> $session_only,
            'index_on_login'=> isset($_POST['index_on_login'])?1:0,
            'purge_on_logout'=> isset($_POST['purge_on_logout'])?1:0,
            'triggers'=> $triggers,
            'custom_hooks'=> array_values(array_filter(array_map('sanitize_text_field', $_POST['custom_hooks'] ?? []))),
            'lat_keys'=> sanitize_text_field($_POST['lat_keys'] ?? ''),
            'lng_keys'=> sanitize_text_field($_POST['lng_keys'] ?? ''),
            'meta_trigger_whitelist' => '', // ignored when session_only; keeping key harmlessly
            'columns'=>[]
        ];

        $cols = $_POST['col'] ?? [];
        $N = count($cols['col'] ?? []);
        for ($i=0; $i<$N; $i++){
            $c = [
                'col' => sanitize_key($cols['col'][$i] ?? ''),
                'source' => sanitize_text_field($cols['source'][$i] ?? 'meta'),
                'key' => sanitize_text_field($cols['key'][$i] ?? ''),
                'type'=> sanitize_text_field($cols['type'][$i] ?? 'VARCHAR(190)'),
                'primary'=> !empty($cols['primary'][$i]),
                'index'=> !empty($cols['index'][$i]),
                'unique'=> !empty($cols['unique'][$i]),
            ];
            if ($c['col']) $t['columns'][] = $c;
        }
        if ( ! $t['columns'] ) $t['columns'] = \AAA_WFUIM_Entities::default_columns($entity);

        $all = \AAA_WFUIM_Registry::tables();
        if ($old && $old !== $slug) unset($all[$old]);
        $all[$slug] = $t; \AAA_WFUIM_Registry::save_tables($all);

        \AAA_WFUIM_Schema::ensure_table($t);
        wp_safe_redirect(admin_url('admin.php?page=wfuim-table-edit&slug='.$slug.'&saved=1')); exit;
    }
}
AAA_WFUIM_Admin_Table_Edit::init();
