<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Makers\WoocommercePostsA4BarcodesMaker;
use UkrSolution\ProductLabelsPrinting\Api\PostsData;
use UkrSolution\ProductLabelsPrinting\BarcodeTemplates\BarcodeTemplatesController;
use UkrSolution\ProductLabelsPrinting\BarcodeTemplates\BarcodeView;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Generators\BarcodeImage;
use UkrSolution\ProductLabelsPrinting\Helpers\UserFieldsMatching;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;
use UkrSolution\ProductLabelsPrinting\Makers\ManualA4BarcodesMaker;
use UkrSolution\ProductLabelsPrinting\Makers\TestA4BarcodesMaker;
use UkrSolution\ProductLabelsPrinting\POS\POS_Orders;
use UkrSolution\ProductLabelsPrinting\Updater\Updater;

class Core
{
    protected $config;
    protected $customTemplatesController;
    protected $dimensions;
    protected $updater;
    protected $woocommerceBarcodesMakerInstance;

    public function __construct()
    {
        $this->customTemplatesController = new BarcodeTemplatesController();
        $this->dimensions = new Dimensions();

        $this->updater = new Updater();
        add_filter('site_transient_update_plugins', array($this, 'disablePluginUpdates'));

        $ajaxPrefix = "";

        add_action('init', function() {
            $this->config = require Variables::$A4B_PLUGIN_BASE_PATH . 'config/config.php';
            $this->woocommerceBarcodesMakerInstance = new WoocommercePostsA4BarcodesMaker(array());
        }, 1);

        add_action('admin_menu', array($this, 'addMenuPages'), 9);
        add_action('admin_menu', array($this, 'adminEnqueueScripts'), 9);
        add_action('admin_enqueue_scripts', array($this, 'adminAllEnqueueScripts'), 9);
        add_filter('plugin_row_meta', array($this, 'pluginRowMeta'), 10, 2);

        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_values', array($this, 'getBarcodesByValues'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_test', array($this, 'getBarcodesTest'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_latest_version', array($this, 'getLatestVersion'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_all_algorithms', array($this, 'getAllAlgorithms'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_active_template', array($this, 'getActiveTemplate'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_all_templates', array($this, 'getAllTemplates'));

        $woocommerceModel = new WooCommerce();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes', array($woocommerceModel, 'getBarcodes'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_products', array($woocommerceModel, 'getBarcodesByProducts'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_dokan_items', array($woocommerceModel, 'getBarcodesByProducts'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_categories', array($woocommerceModel, 'getCategories'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_attributes', array($woocommerceModel, 'getAttributes'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_local_attributes', array($woocommerceModel, 'getLocalAttributes'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_check_custom_field', array($woocommerceModel, 'countProductsByCustomField'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_orders', array($woocommerceModel, 'getBarcodesByOrders'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_order_products', array($woocommerceModel, 'getBarcodesByOrderProducts'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_order_items', array($woocommerceModel, 'getBarcodesByOrderProducts'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_by_atum_order_items', array($woocommerceModel, 'getBarcodesByAtumPoOrderProducts'));

        $preview = new Preview();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_preview_barcode', array($preview, 'getBarcode'));

        $flamingoModel = new Flamingo();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_flamingo_inbound', array($flamingoModel, 'getBarcodes'));
        add_action('wp_dropdown_cats', array($flamingoModel, 'addImportButton'));

        $wcCouponsModel = new WoocemmerceCoupons();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_wc_coupons', array($wcCouponsModel, 'getBarcodes'));
        add_action('restrict_manage_posts', array($wcCouponsModel, 'addImportButton'));

        $atumInventoryPoModel = new AtumInventoryPo();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_atum_po', array($atumInventoryPoModel, 'getBarcodes'));
        add_action('atum/atum_order/after_item_meta', array($atumInventoryPoModel, 'addOrderItemsImport'));
        add_action('atum/list_table/after_nav_filters', array($atumInventoryPoModel, 'addImportButton'));
        add_action('atum/atum_order/add_action_buttons', array($atumInventoryPoModel, 'orderItemActionButton'));
        add_action('atum/atum_order/add_line_buttons', array($atumInventoryPoModel, 'orderItemActionButton'));

        $users = new Users();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_barcodes_wp_users', array($users, 'getBarcodes'));
        add_action('restrict_manage_users', array($users, 'addImportButton'));

        $formatsModel = new Formats();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_delete_format', array($formatsModel, 'deleteFormat'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_format', array($formatsModel, 'saveFormat'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_all_formats', array($formatsModel, 'getAllFormats'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_formats_by_paper', array($formatsModel, 'getFormatsByPaper'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_format', array($formatsModel, 'getFormat'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_all_paper_formats', array($formatsModel, 'getAllPaperFormats'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_paper_format', array($formatsModel, 'savePaperFormat'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_delete_paper_format', array($formatsModel, 'deletePaperFormat'));

        $productsModel = new Products();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_products_search', array($productsModel, 'search'));

        add_action('restrict_manage_posts', array($productsModel, 'addImportButton'));
        add_action('media_buttons', array($productsModel, 'mediaButtons'));
        add_action('woocommerce_variation_header', array($productsModel, 'variationButtons'));
        add_action('wp_ajax_a4barcode_get_variations_by_product_id', array($productsModel, 'getVariationByProduct'));

        $profilesModel = new Profiles();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_profile', array($profilesModel, 'saveProfile'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_update_profile', array($profilesModel, 'updateProfile'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_delete_profile', array($profilesModel, 'deleteProfile'));

        $metaBoxes = new MetaBoxes();
        add_action('add_meta_boxes', array($metaBoxes, 'orderPagePrint'));
        add_action('add_meta_boxes', array($metaBoxes, 'atumPOPagePrint'));

        $customField = UserSettings::getoption('customField', false);
        if ($customField) {
            $productsModel->customFields();
        }

        $productsModel->productsPageColumns();

        add_filter('post_row_actions', array($productsModel, "post_row_actions"), 10, 2);

        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_digital_template_changes', array($this->customTemplatesController, 'updateDigital'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_template_changes', array($this->customTemplatesController, 'update'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_delete_template', array($this->customTemplatesController, 'delete'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_copy_template', array($this->customTemplatesController, 'copy'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_create_template', array($this->customTemplatesController, 'createNewTemplate'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_set_active_template', array($this->customTemplatesController, 'setactive'));

        $settings = new Settings();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_change_uol', array($settings, 'changeUol'));
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_update_user_settings', array($settings, 'updateUserSettings')); 
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_update_user_field_matching', array($settings, 'updateUserFieldMatching')); 
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_update_template_field_matching', array($settings, 'updateTemplateFieldMatching')); 
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_clear_template_field_matching', array($settings, 'clearTemplateFieldMatching')); 
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_import_settings', array($settings, 'saveImportSettings')); 
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_save_session', array($settings, 'saveSession')); 
        add_action('wp_ajax_a4_barcode' . $ajaxPrefix . '_disable_jszip', array($settings, 'disableJszip')); 

        $barcodesApi = new \UkrSolution\ProductLabelsPrinting\Api\Barcodes();
        add_action('wp_ajax_label_printing_generate_barcodes_by_codes', array($barcodesApi, 'generateBarcodesByCodes'));
        $postsData = new PostsData();
        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_get_ids_bulk_list', array($postsData, 'getIdsBulkList')); 


        $orders = new Orders();
        add_action('restrict_manage_posts', array($orders, 'addImportButton'));
        add_action('woocommerce_order_list_table_restrict_manage_orders', array($orders, 'addImportButton'));

        $disableCreationOrderItems = UserSettings::getOption('disableCreationOrderItems', '');

        if(!$disableCreationOrderItems) {
            add_action('woocommerce_after_order_itemmeta', array($orders, 'addOrderItemsImport'));
            add_action('woocommerce_order_item_add_action_buttons', array($orders, 'orderItemActionButton'));
        }

        $categories = new Categories();
        add_action('bulk_actions-edit-product_cat', array($categories, 'addImportButton'));

        new PostFilters();

        add_action('init', function () {
            $Integration = new Integration();
            $Integration->init();
        });

        add_action('init', array($this, "parsePrintRequest"));


        add_action('wp_ajax_a4barcode' . $ajaxPrefix . '_dimensionsGet_get', array($this->dimensions, 'get'));

    }

    public function addMenuPages()
    {
        $icons = str_replace("class/", "", \plugin_dir_url(__FILE__)) . "assets/icons/";

        $icon = 'dashicons-tag';
        add_menu_page(
            __('Label Printing', 'wpbcu-barcode-generator'),
            __('Label Printing', 'wpbcu-barcode-generator'),
            'read',
            'wpbcu-barcode-generator',
            array($this, 'emptyPage'),
            $icon
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('Open Preview', 'wpbcu-barcode-generator'),
            __('Open Preview', 'wpbcu-barcode-generator'),
            'read',
            'wpbcu-barcode-generator',
            array($this, 'emptyPage')
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('Create Manually', 'wpbcu-barcode-generator'),
            __('Create Manually', 'wpbcu-barcode-generator'),
            'read',
            'wpbcu-barcode-generator&m=1',
            array($this, 'emptyPage')
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('From "Products" page', 'wpbcu-barcode-generator'),
            __('From "Products" page', 'wpbcu-barcode-generator'),
            'read',
            'edit.php?post_type=product#b=products'
        );
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_submenu_page(
                'wpbcu-barcode-generator',
                __('From "Category" page', 'wpbcu-barcode-generator'),
                __('From "Category" page', 'wpbcu-barcode-generator'),
                'read',
                'edit-tags.php?taxonomy=product_cat&post_type=product#b=categories'
            );
        }
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('From "Orders" page', 'wpbcu-barcode-generator'),
            __('From "Orders" page', 'wpbcu-barcode-generator'),
            'read',
            'edit.php?post_type=shop_order#b=orders'
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('From "Bulk list"', 'wpbcu-barcode-generator'),
            __('From "Bulk list"', 'wpbcu-barcode-generator'),
            'read',
            'wpbcu-import-list',
            array($this, 'emptyPage')
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('Label Templates', 'wpbcu-barcode-generator'),
            __('Label Templates', 'wpbcu-barcode-generator'),
            'manage_options',
            'wpbcu-barcode-templates-edit',
            array($this, 'pageBarcodeTemplates')
        );
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('Settings', 'wpbcu-barcode-generator'),
            __('Settings', 'wpbcu-barcode-generator'),
            'manage_options',
            'wpbcu-barcode-settings',
            array($this, 'settingsPage')
        );
        if ('PREMIUM-OLD' !== Variables::$A4B_PLUGIN_PLAN) {
            add_submenu_page(
                'wpbcu-barcode-generator',
                __('Support', 'wpbcu-barcode-generator'),
                '<span class="a4barcode_support">' . __('Support', 'wpbcu-barcode-generator') . '</span>',
                'read',
                'wpbcu-barcode-generator-support',
                array($this, 'emptyPage')
            );
        }
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('FAQ', 'wpbcu-barcode-generator'),
            '<span class="a4barcode_faq">' . __('FAQ', 'wpbcu-barcode-generator') . '</span>',
            'read',
            'wpbcu-barcode-generator-faq',
            array($this, 'emptyPage')
        );
        add_submenu_page(
            '',
            __('Barcode-Generator Page', 'wpbcu-barcode-generator'),
            __('Barcode-Generator Page', 'wpbcu-barcode-generator'),
            'read',
            'wpbcu-barcode-generator-print',
            array($this, 'emptyPage')
        );


    }

    public function shortcodesPage()
    {
        echo '<div><a href="#" id="barcode-shortcodes-section"></a></div>';

    }

    public function settingsPage()
    {
        wp_enqueue_style('codemirror', Variables::$A4B_PLUGIN_BASE_URL . 'assets/chosen/css/chosen.min.css', array(), false);
        wp_enqueue_script('codemirror', Variables::$A4B_PLUGIN_BASE_URL . 'assets/chosen/js/chosen.jquery.min.js', array(), false, true);
        echo '<div><a href="#" id="barcode-settings-section"></a></div>';

    }

    public function settingsInit()
    {
        add_submenu_page(
            'wpbcu-barcode-generator',
            __('Settings', 'wpbcu-barcode-generator'),
            __('Settings', 'wpbcu-barcode-generator'),
            'export',
            'wpbcu-barcode-settings',
            array($this, 'settingsPage')
        );
    }

    public function getBarcodesByValues()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        $post = array();

        foreach (array('format', 'requestTime', 'isUseApi') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        if (isset($_POST['fields'])) {
            $post['fields'] = USWBG_a4bRecursiveSanitizeTextField($_POST['fields']);
        }

        $validationRules = array(
            'format' => 'required',
            'requestTime' => 'string',
            'fields' => 'required|array',
        );

        $data = Validator::create($post, $validationRules, true)->validate();

        $barcodesMaker = new ManualA4BarcodesMaker($data);
        $requestTime = (isset($data['requestTime'])) ? $data['requestTime'] : '';
        $result = $barcodesMaker->make();

        $isUseApi = isset($post["isUseApi"]) && (int)$post["isUseApi"] === 1 ? true : false;
        $Items = new Items();
        $Items->CheckItemsResult($result["listItems"], $data, $isUseApi);

        uswbg_a4bJsonResponse(array_merge($result, ["requestTime" => $requestTime]));
    }

    public function getBarcodesTest()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        $barcodesMaker = new TestA4BarcodesMaker();
        $result = $barcodesMaker->make();
        uswbg_a4bJsonResponse($result);
    }

    public function pluginRowMeta($links, $file)
    {
        if (Variables::$A4B_PLUGIN_BASE_NAME == $file) {
            $rowMeta = ucfirst(strtolower(Variables::$A4B_PLUGIN_PLAN));
            array_splice($links, 1, 0, $rowMeta);
        }

        return (array) $links;
    }

    public function disablePluginUpdates($plugins)
    {
        $pluginCurrentPathFile = plugin_basename(__FILE__);
        $startCutPosition = strpos($pluginCurrentPathFile, '/');
        $pluginDirName = substr($pluginCurrentPathFile, 0, $startCutPosition);
        if ($plugins && isset($plugins->response) && isset($plugins->response[$pluginDirName . '/barcode_generator.php'])) {
            unset($plugins->response[$pluginDirName . '/barcode_generator.php']);
        }

        return $plugins;
    }

    public function adminAllEnqueueScripts()
    {
        if (!is_admin()) {
            return;
        }

        wp_register_style('import_categories_button_business', Variables::$A4B_PLUGIN_BASE_URL . 'templates/actions-assets/style.css', false, '3.4.12');
        wp_enqueue_style('import_categories_button_business');

        wp_enqueue_script('import_buttons_actions_assets', Variables::$A4B_PLUGIN_BASE_URL . 'templates/actions-assets/script.js');
    }

    public function adminEnqueueScripts($isFront = false, $isReturn = false)
    {
        global $wp_version;
        global $current_user;

        wp_enqueue_script("barcode_loader_print", Variables::$A4B_PLUGIN_BASE_URL."assets/js/index-3.4.12-4807abb8.js", array("jquery"), null, true);
wp_enqueue_script("barcode_api_print", Variables::$A4B_PLUGIN_BASE_URL."assets/js/api-3.4.12-4807abb8.js", array("jquery"), null, true);
wp_enqueue_style("barcode_core_css_print", Variables::$A4B_PLUGIN_BASE_URL."public/dist/css/app_business_3.4.12-4807abb8.css", null, null);$appJsPath = Variables::$A4B_PLUGIN_BASE_URL."public/dist/js/app_business_3.4.12-4807abb8.js";
$vendorJsPath = Variables::$A4B_PLUGIN_BASE_URL."public/dist/js/chunk-vendors_business_3.4.12-4807abb8.js";
$jszip = Variables::$A4B_PLUGIN_BASE_URL."assets/js/jszip.min-3.4.12-4807abb8.js";


        $active_template = $this->customTemplatesController->getActiveTemplate();
        $activeDimension = $this->dimensions->getActive();
        $allPaperFormats = array();
        $barcodeSizes = array();
        $importCodes = array();


        $formats = new Formats();
        $allPaperFormats = $formats->getAllPaperFormats(false);
        $jsWindowKey = 'a4bjs';
        $barcodeLoaderScriptSlug = 'barcode_loader_print';
        $shortcodesOrder = $shortcodesProduct = $shortcodesCustom = array();

        $woocommerceModel = new WooCommerce();
        $wcAttributes = $woocommerceModel->getAttributes(false);

        $isBoosterForWC = is_plugin_active("woocommerce-jetpack/woocommerce-jetpack.php");
        if (!$isBoosterForWC) {
            $isBoosterForWC = is_plugin_active("booster-plus-for-woocommerce/booster-plus-for-woocommerce.php");
        }

        $previewProduct = null;

        if ($current_user) {
            try {
                $_pidData = \get_user_meta($current_user->ID, 'usplp_product_preview', true);
                $_pidData = explode(":", $_pidData);

                $_pid = $_pidData[0];
                $type = count($_pidData) == 2 ? $_pidData[1] : "";
                $_post = null;

                if($type == "user") {
                    $_post = \get_user_by('id', $_pid);

                    if ($_post) {
                        $_post = (array)$_post->data;
                        $_post['post_type'] = 'user';
                        $previewProduct = $_post;
                    }
                } else if ($_pid) {
                    $_post = \get_post($_pid);

                    if ($_post) {
                        $previewProduct = array('ID' => $_post->ID, 'post_title' => $_post->post_title, 'post_type' => $_post->post_type);
                    }
                }

                if (!$_pid || !$_post) {
                    $args = array('numberposts' => 'n', 'post_type' => 'product');
                    $recentProducts = \wp_get_recent_posts($args, OBJECT);

                    if ($recentProducts && count($recentProducts)) {
                        $_post = $recentProducts[0];
                        $previewProduct = array('ID' => $_post->ID, 'post_title' => $_post->post_title, 'post_type' => $_post->post_type);
                    }
                }
            } catch (\Throwable $th) {
            }
        }

        $active_template->shortcodes = array();
        $this->woocommerceBarcodesMakerInstance->extractTemplateShortcodes($active_template->template, $active_template->shortcodes);

        $mainConfig = array(
            'pluginUrl' => Variables::$A4B_PLUGIN_BASE_URL,
            'pluginType' => Variables::$A4B_PLUGIN_TYPE,
            'websiteUrl' => get_bloginfo("url"),
            'adminUrl' => get_admin_url(),
            'pluginVersion' => '3.4.12',
            'isWoocommerceActive' => is_plugin_active('woocommerce/woocommerce.php'),
            'isCF7Active' => is_plugin_active('contact-form-7/wp-contact-form-7.php'),
            'isTieredPriceActive' => (is_plugin_active('tier-pricing-table/tier-pricing-table.php') || is_plugin_active('tier-pricing-table-premium/tier-pricing-table.php')),
            'isAtumPoActive' => is_plugin_active('atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php'),
            'isPbetActive' => is_plugin_active('product-batch-expiry-tracking-for-woocommerce/product-batch-expiry-tracking-for-woocommerce.php'),
            'isWcShippingLocalPickupPlusActive' => is_plugin_active('woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php'),
            'isWcBookingActive' => is_plugin_active('woocommerce-bookings/woocommerce-bookings.php'),
            'wt_seq_ordnum' => defined('WT_SEQUENCIAL_ORDNUMBER_VERSION') || is_plugin_active("woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php"),
            'autologin_links' => is_plugin_active('autologin-links/autologin-links.php'),
            'isBarcodeScanner' => is_plugin_active('barcode-scanner-premium/barcode-scanner.php') || is_plugin_active('barcode-scanner-business/barcode-scanner.php') || is_plugin_active('barcode-scanner-basic/barcode-scanner.php'),
            'isDokan' => is_plugin_active('dokan-lite/dokan.php'),
            'appJsPath' => $appJsPath,
            'vendorJsPath' => $vendorJsPath,
            'jszip' => $jszip,
            'activeTemplateData' => $active_template ? $active_template : null,
            'allPaperFormats' => $allPaperFormats,
            'allTemplates' => $this->getAllTemplates(false),
            'shortcodesOrder' => $shortcodesOrder,
            'shortcodesProduct' => $shortcodesProduct,
            'shortcodesCustom' => $shortcodesCustom,
            'rest_root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'activeDimension' => $activeDimension,
            'dimensions' => $this->dimensions->get(false),
            'barcodeTypes' => $this->config['listAlgorithm'],
            'barcodeSizes' => $barcodeSizes,
            'uid' => get_current_user_id(),
            'wp_version' => $wp_version,
            'wc_version' => defined("WC_VERSION") ? WC_VERSION : 0,
            'currentPage' => Variables::getCurrentPage(),
            'ajaxUrl' => get_admin_url() . 'admin-ajax.php',
            'isFront' => $isFront ? 1 : 0,
            'plugins' => $this->checkExternalPlugins(),
            'isCustomSortEnabled' => function_exists('barcodes_products_sort_items_hook'),
            'tab' => isset($_GET["tab"]) ? sanitize_text_field($_GET["tab"]) : "",
            'previewProduct' => $previewProduct,
            'bulkListRaw' => get_user_meta(get_current_user_id(), 'a4b_bulk_list_raw', true),
            'search_attributes' => UserSettings::getoption('search_attributes', ''),
        );

        if ($isReturn) {
        } else {
            wp_localize_script($barcodeLoaderScriptSlug, $jsWindowKey, $mainConfig);
        }

        $generalSettings = UserSettings::getGeneral();

        $userSettings = UserSettings::get();

        $userFieldsMatching = UserFieldsMatching::get();

        $jsL10n = require Variables::$A4B_PLUGIN_BASE_PATH . 'config/jsL10n.php';

        try {
            if (!isset($userSettings['settings_wizard'])) {
                if (isset($generalSettings['defaultProfile']) && $generalSettings['defaultProfile']) {
                    $profiles = new Profiles();
                    $profile = $profiles->getProfile($generalSettings['defaultProfile']);
                    $productProfile = $profiles->getProfile($generalSettings['defaultProductProfile']);
                    $orderProfile = $profiles->getProfile($generalSettings['defaultOrderProfile']);

                    if($profile) {
                        $settings = new Settings();
                        $settings->updateUserSettings(array(
                            "settings_wizard" => 1,
                            "paper_sheet" => array("profileId" => $generalSettings['defaultProfile'], "paperId" => $profile->paperId, "sheetId" => $profile->sheetId)
                        ), false);
                    }
                    if($productProfile) {
                        $settings = new Settings();
                        $settings->updateUserSettings(array(
                            "settings_wizard" => 1,
                            "product_paper_sheet" => array("profileId" => $generalSettings['defaultProductProfile'], "paperId" => $productProfile->paperId, "sheetId" => $productProfile->sheetId)
                        ), false);
                    }
                    if($orderProfile) {
                        $settings = new Settings();
                        $settings->updateUserSettings(array(
                            "settings_wizard" => 1,
                            "order_paper_sheet" => array("profileId" => $generalSettings['defaultOrderProfile'], "paperId" => $orderProfile->paperId, "sheetId" => $orderProfile->sheetId)
                        ), false);
                    }

                    $userSettings = UserSettings::get();
                }
            }
        } catch (\Throwable $th) {
        }

        if ($isReturn) {
            return array(
                $jsWindowKey => $mainConfig,
                "a4barcodesL10n" => $jsL10n,
                "a4barcodesUS" => $userSettings,
                "a4barcodesGS" => $generalSettings,
                "a4barcodesFM" => $userFieldsMatching,
                "a4barcodesATTR" => $wcAttributes,
            );
        } else {
            wp_localize_script($barcodeLoaderScriptSlug, 'a4barcodesL10n', $jsL10n);
            wp_localize_script($barcodeLoaderScriptSlug, 'a4barcodesUS', $userSettings);
            wp_localize_script($barcodeLoaderScriptSlug, 'a4barcodesGS', $generalSettings);
            wp_localize_script($barcodeLoaderScriptSlug, 'a4barcodesFM', $userFieldsMatching);
            wp_localize_script($barcodeLoaderScriptSlug, 'a4barcodesATTR', $wcAttributes);
        }


    }

    public function emptyPage()
    {
    }

    public function pageBarcodeTemplates()
    {
        wp_enqueue_media();
        $this->enqueueTemplatesAssets();
        $this->customTemplatesController->edit();
    }

    public function getAllAlgorithms()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        uswbg_a4bJsonResponse(array(
            'list' => $this->config['listAlgorithm'],
            'success' => array(),
            'error' => array(),
        ));
    }

    public function getLatestVersion()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        global $wp_version;
        $lastReleaseDataFallback = array('url' => '', 'version' => '');

        $lastReleaseDataResponse = wp_remote_get('https://www.ukrsolution.com/CheckUpdates/PrintBarcodeGeneratorForWordpressV3.json');
        $lastReleaseData = is_wp_error($lastReleaseDataResponse)
            ? $lastReleaseDataFallback
            : (json_decode(wp_remote_retrieve_body($lastReleaseDataResponse), true) ?: $lastReleaseDataFallback);

        $barcodes = [
            'isLatest' => (int) version_compare('3.4.12', $lastReleaseData['version'], '>='),
            'latest' => $lastReleaseData['version'], 
            'version' => '3.4.12',
            'downloadUrl' => $lastReleaseData['url'],
            'pluginUrl' => Variables::$A4B_PLUGIN_BASE_URL,
            'type' => strtolower(Variables::$A4B_PLUGIN_PLAN),
            'wp_version' => $wp_version,
            'isWoocommerceActive' => is_plugin_active('woocommerce/woocommerce.php'),
            'active_template' => $this->customTemplatesController->getActiveTemplate(),
        ];

        uswbg_a4bJsonResponse($barcodes);
    }

    public function getActiveTemplate()
    {
        if (!current_user_can('read')) {
            wp_die();
        }

        uswbg_a4bJsonResponse($this->customTemplatesController->getActiveTemplate());
    }

    public function getAllTemplates($isAjax = true)
    {

        if (!current_user_can('read')) {
            wp_die();
        }

        $templates = $this->customTemplatesController->getAllTemplates();
        $embeddingSettings = get_option(Database::$optionSettingsProductsKey, array());

        if (isset($embeddingSettings["template"])) {
            foreach ($templates as &$template) {
                if ($template->id === $embeddingSettings["template"]) {
                    $template->embed = true;
                    break;
                }
            }
        }

        $dimensions = new Dimensions();
        $activeDimension = $dimensions->getActive();

        foreach ($templates as &$template) {
            if ($template->is_base && $template->is_default && $activeDimension) {
                $template->uol_id = $activeDimension;
            }


            $template->shortcodes = array();
            $this->woocommerceBarcodesMakerInstance->extractTemplateShortcodes($template->template, $template->shortcodes);
        }

        if ($isAjax === false) {
            return $templates;
        } else {
            uswbg_a4bJsonResponse($templates);
        }
    }

    public function parseDigitalRequest()
    {
    }

    public function parsePrintRequest()
    {
        if (preg_match('/\/barcodes-print\/(.*?)\/(.*?)\/(.*?).svg(.*?)?$/', $_SERVER["REQUEST_URI"], $m)) {

            $code = isset($m[1]) ? $m[1] : "";

            $algorithm = isset($m[2]) ? $m[2] : "";

            $templateId = isset($m[3]) ? $m[3] : "";

            $algorithm = strtoupper($algorithm);
            $algorithm = str_replace(array("CODE128", "CODE39"), array("C128", "C39"), $algorithm);

            new BarcodeView($code, $algorithm);
            exit;
        }
    }

    protected function enqueueTemplatesAssets()
    {
        if ('BASIC' !== Variables::$A4B_PLUGIN_PLAN) {
            wp_enqueue_script('barcode_template_preview', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/barcode_template_preview-3.4.12-4807abb8.js', array('jquery'), null, true);
            wp_localize_script('barcode_templates', 'a4bBarcodeTemplates', array('pluginUrl' => Variables::$A4B_PLUGIN_BASE_URL));

            wp_enqueue_style('codemirror', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/codemirror.css', array(), false);
            wp_enqueue_script('codemirror', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/codemirror.js', array(), false, true);
            wp_enqueue_script('codemirror_xml', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/mode/xml/xml.js', array('codemirror'), false, true);
            wp_enqueue_script('codemirror_js', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/mode/javascript/javascript.js', array('codemirror'), false, true);
            wp_enqueue_script('codemirror_css', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/mode/css/css.js', array('codemirror'), false, true);
            wp_enqueue_script('codemirror_html', Variables::$A4B_PLUGIN_BASE_URL . 'assets/js/codemirror/mode/htmlmixed/htmlmixed.js', array('codemirror'), false, true);
        }
    }

    private function checkExternalPlugins()
    {
        $alg_wc_ean_title = get_option('alg_wc_ean_title', __('EAN', 'ean-for-woocommerce'));

        $wpm_pgw_label = get_option('wpm_pgw_label', __('EAN', 'product-gtin-ean-upc-isbn-for-woocommerce'));
        $wpm_pgw_label = sprintf(__('%s Code:', 'product-gtin-ean-upc-isbn-for-woocommerce'), $wpm_pgw_label);

        $hwp_gtin_text = get_option('hwp_gtin_text');
        $hwp_gtin_text = (!empty($hwp_gtin_text) ? $hwp_gtin_text : 'GTIN');

        $plugins = array(
            "wc_appointments" => array('status' => is_plugin_active('woocommerce-appointments/woocommerce-appointments.php'), 'label' => 'WooCommerce Appointments'),
            "openpos" => array('status' => is_plugin_active('woocommerce-openpos/woocommerce-openpos.php'), 'label' => 'Product Id'),
            "atum" => array('status' => is_plugin_active('atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php'), 'label' => ''),
            "wordpress_seo" => array('status' => is_plugin_active('wpseo-woocommerce/wpseo-woocommerce.php'), 'label' => ''),
            "license_manager" => array('status' => is_plugin_active('license-manager-for-woocommerce/license-manager-for-woocommerce.php'), 'label' => ''),
            array('key' => '_alg_ean', 'status' => function_exists('alg_wc_ean'), 'label' => 'EAN for WooCommerce', 'fieldLabel' => $alg_wc_ean_title . ' <sup>(EAN for WooCommerce)</sup>'),
            array('key' => '_wpm_gtin_code', 'status' => function_exists('wpm_product_gtin_wc'), 'label' => 'Product GTIN (EAN, UPC, ISBN) for WooCommerce', 'fieldLabel' => $wpm_pgw_label . ' <sup style="border: none; font-weight: normal;" title="Product GTIN (EAN, UPC, ISBN) for WooCommerce">(Product GTIN (EAN, UPC, ISBN))</sup>'),
            array('key' => 'hwp_product_gtin', 'status' => class_exists('Woo_GTIN'), 'label' => 'WooCommerce UPC, EAN, and ISBN', 'fieldLabel' => $hwp_gtin_text . ' <sup>(WooCommerce UPC, EAN, and ISBN)</sup>'),
            array('key' => '_wepos_barcode', 'status' => is_plugin_active('wepos/wepos.php'), 'label' => 'WePOS', 'fieldLabel' => 'Barcode <sup style="border: none; font-weight: normal;">(WePOS)</sup>'),
            array('key' => '_ts_gtin', 'status' => is_plugin_active('woocommerce-germanized/woocommerce-germanized.php'), 'label' => 'GTIN - Germanized for WooCommerce', 'fieldLabel' => 'GTIN <sup>(Germanized for WooCommerce)</sup>'),
            array('key' => '_ts_mpn', 'status' => is_plugin_active('woocommerce-germanized/woocommerce-germanized.php'), 'label' => 'MPN - Germanized for WooCommerce', 'fieldLabel' => 'MPN <sup>(Germanized for WooCommerce)</sup>'),
        );

        foreach ($plugins as &$plugin) {
            if (isset($plugin["fieldLabel"])) {
                $plugin["fieldLabel"] = str_replace('<sup>', '<sup style="border: none; font-weight: normal;">', $plugin["fieldLabel"]);
            }
        }

        return $plugins;
    }
}
