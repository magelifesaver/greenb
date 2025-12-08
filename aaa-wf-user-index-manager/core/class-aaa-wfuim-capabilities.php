<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Capabilities {
    const CAP = 'manage_wfuim';

    public static function add_caps_network_wide($network_wide){
        $add = function(){
            if ( $role = get_role('administrator') ) {
                if ( ! $role->has_cap(self::CAP) ) $role->add_cap(self::CAP);
            }
        };
        if ( is_multisite() && $network_wide ) {
            foreach ( get_sites(['fields'=>'ids']) as $bid ){ switch_to_blog($bid); $add(); restore_current_blog(); }
        } else { $add(); }
    }
    public static function can_manage(){ return current_user_can(self::CAP); }
}
