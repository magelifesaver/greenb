<?php
class BeRocket_products_label_tripwire extends BeRocket_plugin_variations {
    public $plugin_name = 'products_label';
    public $version_number = 10;
    public $types;
    function __construct() {
        $this->defaults = array(
        
        );
        parent::__construct();
        add_filter('berocket_advanced_label_editor_conditions_list', array( $this, 'condition_types'), $this->version_number);
        add_filter('brfr_data_berocket_advanced_label_editor', array($this, 'berocket_advanced_label_editor'), $this->version_number);
        add_filter('berocket_apl_label_show_text', array($this, 'label_show_text'), $this->version_number, 3);
        add_filter('berocket_labels_products_column_text', array($this, 'products_column_text'), $this->version_number, 2);
        add_filter('berocket_apl_content_type_with_before_after', array($this, 'content_type_with_before_after'), $this->version_number, 1);
    }

    public function content_type_with_before_after($types) {
        $types[] = 'sale_end';
        return $types;
    }
    public function condition_types($conditions) {
        $conditions[] = 'condition_product_attribute';
        $conditions[] = 'condition_product_saleprice';
        $conditions[] = 'condition_product_regularprice';
        $conditions[] = 'condition_product_stockquantity';
        $conditions[] = 'condition_single_product';
        return $conditions;
    }
    public function berocket_advanced_label_editor($data) {
        $general = 'General';
        $style   = 'Style';


        $data[$general]['text_before']['items']['text_before']['class'] .= ' berocket_label_sale_end';
        $data[$general]['text_after']['items']['text_after']['class'] .= ' berocket_label_sale_end';
        $data[$general]['content_type']['options'][] = array('value' => 'sale_end', 'text' => __('Time left for discount', 'BeRocket_products_label_domain'));
        $data[$general]['text']['class'] = $data[$general]['text']['class'].' berocket_label_image';
        $data[$style]['font_color']['class'] = $data[$style]['font_color']['class'].' berocket_label_image';
        $data[$general] = berocket_insert_to_array(
            $data[$general],
            'content_type_description',
            array(
                'content_type_image' => array(
                    "type"     => "image",
                    "label"    => __('Background Image', 'BeRocket_products_label_domain'),
                    "class"    => 'berocket_label_ berocket_label_sale_p berocket_label_price berocket_label_stock_status berocket_label_sale_end berocket_label_sale_val berocket_label_custom',
                    "name"     => "image",
                    "value"    => '',
                ),
                'img_title' => array(
                    "type"     => "text",
                    "label"    => __('Image Title', 'BeRocket_products_label_domain'),
                    "class"    => '',
                    "name"     => "img_title",
                    "value"    => '',
                ),
            )
        );
        return $data;
    }

    public function label_show_text( $text, $br_label, $product ) {
        if ( $br_label[ 'content_type' ] == 'sale_p' && ! empty( $br_label[ 'image' ] ) && ! empty( $text ) && $br_label[ 'text' ] === false ) {
            $text = false;
        } elseif ( $br_label[ 'content_type' ] == 'sale_end' ) {
            $text = '';

            if ( $product == 'demo' ) {
                $date = strtotime( '+12 hours +30 minutes', current_time( 'timestamp' ) );
            } else {
                $product_post = br_wc_get_product_post( $product );
                $date = get_post_meta( $product_post->ID, '_sale_price_dates_to', true );
            }

            if ( $date ) {
                $date_end   = new DateTime( date( 'Y-m-d H:i', $date ) );
                $date_today = new DateTime( date( 'Y-m-d H:i' ) );
                $dates      = date_diff( $date_today, $date_end );
                if ( ! $dates->invert ) {
                    if ( $dates->days > 0 ) {
                        $text = $dates->days . " " . _n( 'day', 'days', $dates->days, 'BeRocket_products_label_domain' );
                    } elseif ( $dates->h > 0 && $dates->days == 0 ) {
                        $text = $dates->h . " " . _n( 'hour', 'hours', $dates->h, 'BeRocket_products_label_domain' );
                    } elseif ( $dates->i > 0 && $dates->h == 0 ) {
                        $text = $dates->i . " " . _n( 'minute', 'minutes', $dates->i, 'BeRocket_products_label_domain' );
                    }
                } else {
                    $text = false;
                }
            } else {
                $text = false;
            }
        }

        // if ( $text != false && in_array( $br_label[ 'content_type' ], array( 'sale_p', 'price', 'stock_status', 'sale_end', 'custom' ) ) && ! empty( $br_label[ 'image' ] ) ) {
        //     $text = '<div class="berocket_image_background" style="background-image:url(' . $br_label[ 'image' ] . ');display:block;width:100%;height:100%;background-size:100% 100%;" title="' . $br_label[ 'img_title' ] . '">' . $text . '</div>';
        // }

        return $text;
    }
    public function products_column_text($text, $options) {
        if ( $options['content_type'] == 'sale_end' ) {
            $text = __('Time left for discount', 'BeRocket_products_label_domain');
        }
        return $text;
    }
}
new BeRocket_products_label_tripwire();
