<?php

    class WOOGC_WCML_Orders extends WCML_Orders {
        
        var $woocommerce_wpml;
        var $sitepress;
        
        public function __construct( &$woocommerce_wpml, &$sitepress ){
            $this->woocommerce_wpml = $woocommerce_wpml;
            $this->sitepress = $sitepress;
            
            global $WooGC;

            $WooGC->functions->remove_class_filter ( 'woocommerce_order_get_items', 'WCML_Orders', 'woocommerce_order_get_items', 10);
            
            add_filter( 'woocommerce_order_get_items', array( $this, 'woocommerce_order_get_items' ), 10, 2 );
        }


        /**
         * @param WC_Order_Item[] $items
         * @param WC_Order        $order
         *
         * @return WC_Order_Item[]
         */
        public function woocommerce_order_get_items( $items, $order ) {

            $translate_order_items = is_admin() || is_view_order_page() || is_order_received_page() || \WCML\Rest\Functions::isRestApiRequest();
            /**
             * This filter hook allows to override if we need to translate order items.
             *
             * @since 4.11.0
             *
             * @param bool            $translate_order_items True if we should to translate order items.
             * @param WC_Order_Item[] $items                 Order items.
             * @param WC_Order        $order                 WC Order.
             */
            $translate_order_items = apply_filters( 'wcml_should_translate_order_items', $translate_order_items, $items, $order );

            if ( $items && $translate_order_items ) {

                $language_to_filter = $this->get_order_items_language_to_filter( $order );

                $this->adjust_order_item_in_language( $items, $language_to_filter );
            }

            return $items;
        }

        /**
         * @param array       $items
         * @param string|bool $language_to_filter
         */
        public function adjust_order_item_in_language( $items, $language_to_filter = false ) {

            if ( ! $language_to_filter ) {
                $language_to_filter = $this->sitepress->get_current_language();
            }

            foreach ( $items as $index => $item ) {
                
                do_action( 'woocommerce/cart_loop/start', $item );

                /**
                 * This filter hook allows to override if we need to save adjusted order item.
                 *
                 * @since 4.11.0
                 *
                 * @param bool          $true               True if we should save adjusted order item.
                 * @param WC_Order_Item $item
                 * @param string        $language_to_filter Language to filter.
                 */
                $save_adjusted_item = apply_filters( 'wcml_should_save_adjusted_order_item_in_language', true, $item, $language_to_filter );

                if ( $item instanceof WC_Order_Item_Product ) {
                    if ( 'line_item' === $item->get_type() ) {
                        $item_was_adjusted = $this->adjust_product_item_if_translated( $item, $language_to_filter );
                        if ( $item->get_variation_id() ) {
                            $item_was_adjusted = $this->adjust_variation_item_if_translated( $item, $language_to_filter );
                        }
                        if ( $item_was_adjusted && $save_adjusted_item ) {
                            $item->save();
                        }
                    }
                } elseif ( $item instanceof WC_Order_Item_Shipping ) {
                    $shipping_id = $item->get_method_id();
                    if ( $shipping_id ) {

                        if ( method_exists( $item, 'get_instance_id' ) ) {
                            $shipping_id .= $item->get_instance_id();
                        }
                        
                        if ( isset ( $this->woocommerce_wpml->shipping  ) )
                            {
                                $item->set_method_title(
                                    $this->woocommerce_wpml->shipping->translate_shipping_method_title(
                                        $item->get_method_title(),
                                        $shipping_id,
                                        $language_to_filter
                                    )
                                );
                            }

                        if ( $save_adjusted_item ) {
                            $item->save();
                        }
                    }
                }
                
                do_action( 'woocommerce/cart_loop/end', $item );
            }
        }
        
        
        
        /**
         * @param WC_Order_Item_Product $item
         * @param string                $language_to_filter
         *
         * @return bool
         */
        private function adjust_product_item_if_translated( $item, $language_to_filter ) {

            $product_id            = $item->get_product_id();
            $translated_product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $language_to_filter );
            if ( $product_id && $product_id !== $translated_product_id ) {
                $item->set_product_id( $translated_product_id );
                $item->set_name( get_post( $translated_product_id )->post_title );
                return true;
            }

            return false;
        }
        
        
        /**
         * @param WC_Order_Item_Product $item
         * @param string                $language_to_filter
         *
         * @return bool
         */
        private function adjust_variation_item_if_translated( $item, $language_to_filter ) {

            $variation_id            = $item->get_variation_id();
            $translated_variation_id = apply_filters( 'translate_object_id', $variation_id, 'product_variation', true, $language_to_filter );
            if ( $variation_id && $variation_id !== $translated_variation_id ) {
                $item->set_variation_id( $translated_variation_id );
                $item->set_name( wc_get_product( $translated_variation_id )->get_name() );
                $this->update_attribute_item_meta_value( $item, $translated_variation_id );
                return true;
            }

            return false;
        }
        
        
        /**
         * @param WC_Order $order
         *
         * @return string
         */
        private function get_order_items_language_to_filter( $order ) {

            if ( $this->is_on_order_edit_page() ) {
                $language = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
            } elseif ( $this->is_order_action_triggered_for_customer() ) {
                $language = self::getLanguage( $order->get_id() ) ?: $this->sitepress->get_default_language();
            } else {
                $language = $this->sitepress->get_current_language();
            }

            /**
             * This filter hook allows to override item language to filter.
             *
             * @since 4.11.0
             *
             * @param string   $language Order item language to filter.
             * @param WC_Order $order
             */
            return apply_filters( 'wcml_get_order_items_language', $language, $order );
        }
        
        /**
         * @return bool
         */
        private function is_on_order_edit_page() {
            return isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] );
        }
        
        /**
         * @return bool
         */
        private function is_order_action_triggered_for_customer() {
            return isset( $_GET['action'] ) && wpml_collect(
                [ 'woocommerce_mark_order_complete', 'woocommerce_mark_order_status', 'mark_processing' ]
            )->contains( $_GET['action'] );
        }
        
        
        /**
         * @param WC_Order_Item_Product $item
         * @param int                   $variation_id
         */
        private function update_attribute_item_meta_value( $item, $variation_id ) {
            foreach ( $item->get_meta_data() as $meta_data ) {
                $data            = $meta_data->get_data();
                $attribute_value = get_post_meta( $variation_id, 'attribute_' . $data['key'], true );
                if ( $attribute_value ) {
                    $item->update_meta_data( $data['key'], $attribute_value, isset( $data['id'] ) ? $data['id'] : 0 );
                }
            }
        }
      


    }

    
?>