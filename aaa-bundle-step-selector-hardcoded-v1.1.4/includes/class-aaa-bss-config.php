<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-config.php
 *
 * Hardcoded promo configuration (per user request).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_Config {

    // Step 1 products (paid).
    public static function step1_product_ids() {
        return [ 47587, 47588 ];
    }

    // Step 2 products (added to cart, NOT forced free).
    public static function step2_product_ids() {
        return [ 15011, 15018, 15020 ];
    }

    public static function step1_max() {
        return 2;
    }

    public static function step2_max() {
        return 1;
    }

    // v1.1.1: Step 2 is NOT forced free (discount handled elsewhere).
    public static function step2_is_free() {
        return false;
    }

    // Banner image (attachment id and url provided).
    public static function banner_image_id() {
        return 64094;
    }

    public static function banner_image_url() {
        return 'https://lokeydelivery.com/wp-content/uploads/sites/9/2026/01/Godspeed-2-8-for-50-banner.png';
    }

    // Show banner on this archive URL path (match request URI).
    public static function archive_path_matches() {
        return [
            '/flower/filters/brands/godspeed/flower-weight/3-5g',
            '/flower/filters/brands/godspeed/flower-weight/3-5g/',
        ];
    }

    // Show banner on these product IDs.
    public static function product_page_ids() {
        return [
            61433, 60690, 60689, 60688, 60246, 60245, 58702, 58701, 58699,
            56852, 53975, 53973, 47588, 47587,
        ];
    }
}
