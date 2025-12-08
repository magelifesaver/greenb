<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
if (!class_exists('RP_WCDPD_Condition_Product')) {
    require_once(RP_WCDPD_PLUGIN_PATH . 'classes\conditions/rp-wcdpd-condition-product.class.php');
}


class RP_WCDPD_Condition_Product_Category_WooGC extends RP_WCDPD_Condition_Product
{
    protected $key      = 'category';
    protected $contexts = array('product_pricing_product', 'product_pricing_bogo_product', 'product_pricing_group_product', 'cart_discounts_product', 'checkout_fees_product');
    protected $method   = 'list';
    protected $fields   = array(
        'after' => array('product_categories'),
    );
    protected $position = 30;

    // Singleton instance
    protected static $instance = false;

    /**
     * Singleton control
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->hook();
    }

    /**
     * Get label
     *
     * @access public
     * @return string
     */
    public function get_label()
    {
        return __('Product category', 'rp_wcdpd');
    }

    /**
     * Get value to compare against condition
     *
     * @access public
     * @param array $params
     * @return mixed
     */
    public function get_value($params)
    {
        if (!empty($params['item_id'])) {
            
            global $blog_id;
            
            $current_blog_id    =   $blog_id;
            
            if ( isset($params['cart_item']['blog_id'])  &&  $params['cart_item']['blog_id']  !=  $current_blog_id)
                switch_to_blog( $params['cart_item']['blog_id'] );
                
            $response   =   RightPress_Helper::get_wc_product_category_ids_from_product_ids(array($params['item_id']));
            
            if ( isset($params['cart_item']['blog_id'])  &&  $params['cart_item']['blog_id']  !=  $current_blog_id)
                restore_current_blog();
                
            return $response;
            
        }

        return null;
    }




}

RP_WCDPD_Condition_Product_Category::get_instance();
