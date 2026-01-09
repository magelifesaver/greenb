<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/url-cleaner/class-ddd-dt-url-cleaner.php';
require_once DDD_DT_DIR . 'includes/modules/pagination-redirect/class-ddd-dt-pagination-redirect.php';
require_once DDD_DT_DIR . 'includes/modules/page-click-manager/class-ddd-dt-page-click-manager.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-troubleshooter.php';
require_once DDD_DT_DIR . 'includes/modules/atum-log-viewer/class-ddd-dt-atum-log-viewer.php';
require_once DDD_DT_DIR . 'includes/modules/debug-log-manager/class-ddd-dt-debug-log-manager.php';
require_once DDD_DT_DIR . 'includes/modules/product-debugger/class-ddd-dt-product-debugger.php';
require_once DDD_DT_DIR . 'includes/modules/order-debugger/class-ddd-dt-order-debugger.php';

class DDD_DT_Modules {
    public static function init() {
        DDD_DT_Logger::init();

        DDD_DT_URL_Cleaner::init();
        DDD_DT_Pagination_Redirect::init();
        DDD_DT_Page_Click_Manager::init();
        DDD_DT_Troubleshooter::init();
        DDD_DT_ATUM_Log_Viewer::init();
        DDD_DT_Debug_Log_Manager::init();
        DDD_DT_Product_Debugger::init();
        DDD_DT_Order_Debugger::init();
    }

    public static function is_custom_options_ready(): bool {
        return DDD_DT_Options::custom_table_exists();
    }
}
