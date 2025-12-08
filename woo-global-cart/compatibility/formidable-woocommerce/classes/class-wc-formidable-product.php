<?php

/**
 * This class handles all of the front end implementation on the product page
 */
class WooGC_WC_Formidable_Product extends WC_Formidable_Product {
 
    /**
     * Convert FP submission into cart data
     * Adds this meta to both the cart data & order data
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     * @since 1.0
     */
    public function add_fp_submission_data_as_meta( $item_data, $cart_item ) {

        // continue if there's some Formidable forms data to process
        if ( empty( $cart_item['_formidable_form_data'] ) ) {
            return $item_data;
        }

        
        switch_to_blog( $cart_item['blog_id'] );
        // get form submission
        $submission = FrmEntry::getOne( $cart_item['_formidable_form_data'], true );
        
        // make sure there's some form data to process
        if ( empty( $submission ) ) {
            restore_current_blog();
            return $item_data;
        }

        // get all of the fields
        $all_fields = FrmField::get_all_for_form( $submission->form_id, '', 'include' );

        $all_fields = apply_filters( 'wc_fp_cart_fields', $all_fields, $submission->form_id );

        // loop through each field and add data to item in cart
        foreach ( $all_fields as $field ) {

            // get field data
            $submitted_value = FrmProEntryMetaHelper::get_post_or_meta_value( $submission, $field );

            $this->maybe_add_child_values( $field, $submitted_value, $submission );

            // hide irrelevant fp field values from appearing in the cart
            if ( $this->should_display_fp_option_in_cart( $field, $submitted_value ) ) {

                // get the label from the saved values
                $displayed_value = $submitted_value;
                if ( is_callable( 'FrmFieldFactory::get_field_object' ) ) {
                    if ( $field->type !== 'date' ) {
                        $displayed_value = apply_filters( 'frm_display_value_custom', $displayed_value, $field, array() );
                    }

                    $field_obj = FrmFieldFactory::get_field_object( $field );
                    $displayed_value = $field_obj->get_display_value( $displayed_value, array( 'show_image' => true ) );
                } else {
                    $displayed_value = apply_filters( 'frm_display_value_custom', $displayed_value, $field, array() );
                }

                // get submitted field value
                $field_calc_value = $this->get_fp_field_calc_value( $field, $submitted_value, compact( 'all_fields' ) );

                // format the submitted values to be a bit easier on the eyes
                $display = $this->display_option_in_cart( $displayed_value, $field_calc_value, $field );

                $cart_values = array(
                    'name'    => '<strong>' . $field->name . '</strong>',
                    'value'   => $field_calc_value,
                    'display' => $display,
                );

                $cart_values = $this->apply_deprecated_item_data_filter( $cart_values, $field->name );

                $item_data[] = apply_filters( 'wc_fp_cart_item_data', $cart_values, array( 'field' => $field ) );

            }
        }
        restore_current_blog();
        return $item_data;
    }
    
    
    
    private function maybe_add_child_values( $field, $value, &$submission ) {
        if ( empty( $value ) ) {
            return;
        }

        $is_repeater = FrmField::is_repeating_field( $field );
        if ( $field->type === 'form' || $is_repeater ) {
            foreach ( $value as $child_id ) {
                $child_entry = FrmEntry::getOne( $child_id, true );
                if ( $child_entry ) {
                    foreach ( $child_entry->metas as $id => $child_value ) {
                        if ( isset( $submission->metas[ $id ] ) ) {
                            $submission->metas[ $id ] = (array) $submission->metas[ $id ];
                            $submission->metas[ $id ][] = $child_value;
                        } else {
                            $submission->metas[ $id ] = $child_value;
                        }
                    }
                }
            }
        }
    }
    
        /**
     * Apply deprecated filter
     *
     * @since 1.04
     */
    private function apply_deprecated_item_data_filter( $cart_values, $field_name ) {
        $cart_values = apply_filters( 'wc_fp_addons_new_item_data', $cart_values, $field_name, $cart_values['value'], $cart_values['display'] );

        if ( has_filter( 'wc_fp_addons_new_item_data' ) ) {
            _deprecated_function( 'The wc_fp_addons_new_item_data filter', '1.04', 'the wc_fp_cart_item_data filter' );
        }

        return $cart_values;
    }
   
}


?>