<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/class-ddd-dt-options.php';
require_once DDD_DT_DIR . 'includes/class-ddd-dt-logger.php';
require_once DDD_DT_DIR . 'includes/class-ddd-dt-migrator.php';
require_once DDD_DT_DIR . 'includes/admin/class-ddd-dt-admin.php';
require_once DDD_DT_DIR . 'includes/admin/class-ddd-dt-admin-save.php';
require_once DDD_DT_DIR . 'includes/modules/class-ddd-dt-modules.php';

class DDD_DT_Bootstrap {
    public static function init() {
        DDD_DT_Modules::init();

        if ( is_admin() ) {
            DDD_DT_Admin::init();
        }
    }

    public static function activate() {
        DDD_DT_Migrator::activate();
        DDD_DT_Logger::schedule_prune();
    }

    public static function deactivate() {
        DDD_DT_Logger::unschedule_prune();
    }
}
