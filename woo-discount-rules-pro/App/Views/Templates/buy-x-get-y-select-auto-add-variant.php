<?php
/**
 * Auto add variation option
 *
 * This template can be overridden by copying it to yourtheme/woo-discount-rules-pro/buy-x-get-y-select-auto-add-variant.php.
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}?>
<div class="awdr-select-free-variant-product-toggle"><?php _e('Change Variant', 'woo-discount-rules-pro') ?></div>
<div class="awdr-select-variant-product">
<?php
foreach ($available_products as $available_product) { //parent_id
    if ($available_product != $customer_product_choice['chosen']) {
        $product_variation = new WC_Product_Variation( $available_product );
        $is_available = \WDRPro\App\Rules\BOGO::isVariantPurchasableForBXGY($product_variation, 1, $customer_product_choice['parent_product_id'], $available_product);
        if($is_available){
            // get variation featured image
            $variation_image = $product_variation->get_image(array( 50, 50));
            // get variation name with attributes
            // Fix - variation name issue, if variation contains more than two attributes
            $variation_name_include_attributes = \WDRPro\App\Helpers\CoreMethodCheck::getTitleOfProduct($product_variation);
            ?>
            <div class="awdr_free_product_variants">
            <span class="awdr_change_product" data-pid="<?php echo esc_attr($available_product); ?>"
                  data-rule_id="<?php echo esc_attr($customer_product_choice['matched_rule_identification']); ?>"
                  data-parent_id="<?php echo esc_attr($customer_product_choice['parent_product_id']); ?>"><span class="awdr_variation_image"><?php echo $variation_image; ?></span><span class="awdr-product-name"><?php echo $variation_name_include_attributes; ?></span></span>
            </div>
            <?php
        }
    }
}
?>
</div>

