<?php
/**
 * Defines the WC_Product_Promo class used by the Promo Banner product type.
 *
 * We keep this file intentionally small.  If additional behaviour is needed,
 * create a new module rather than extending this one.  The promo product is
 * virtual, not purchasable and has no price.  Titles and add‑to‑cart
 * mechanisms are suppressed.  It still supports categories, brands and other
 * taxonomies that WooCommerce uses for organisation.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Product_Promo' ) ) {
    class WC_Product_Promo extends WC_Product {
        /**
         * Return the product type identifier.
         *
         * @return string
         */
        public function get_type() {
            return 'promo';
        }

        /**
         * Promo banners are virtual (no shipping).
         *
         * @return bool
         */
        public function is_virtual() {
            return true;
        }

        /**
         * Promo products can't be purchased.
         *
         * @return bool
         */
        public function is_purchasable() {
            return false;
        }

        /**
         * No price for promos.  Returning empty string keeps WooCommerce UI
         * clean and prevents price display.
         *
         * @param  string $context
         * @return string
         */
        public function get_price( $context = 'view' ) {
            return '';
        }

        /**
         * No regular price.
         *
         * @param  string $context
         * @return string
         */
        public function get_regular_price( $context = 'view' ) {
            return '';
        }

        /**
         * No sale price.
         *
         * @param  string $context
         * @return string
         */
        public function get_sale_price( $context = 'view' ) {
            return '';
        }

        /**
         * Override title – return empty string so loop output doesn't duplicate
         * the name of the banner (the visual is enough).
         *
         * @return string
         */
        public function get_title() {
            return '';
        }

        /**
         * Remove add to cart text – not applicable to promos.
         *
         * @return string
         */
        public function add_to_cart_text() {
            return '';
        }

        /**
         * Remove add to cart URL – not applicable to promos.
         *
         * @return string
         */
        public function get_add_to_cart_url() {
            return '';
        }
    }
}
