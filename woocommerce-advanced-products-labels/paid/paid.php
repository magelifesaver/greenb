<?php
class BeRocket_products_label_paid extends BeRocket_plugin_variations {
    public $plugin_name = 'products_label';
    public $version_number = 20;

    public $jqueryui_loaded = false;

    function __construct() {
        parent::__construct();

        add_filter('bapl_products_label_free', '__return_false');
        add_action('init', array($this, 'init'), $this->version_number);
        add_filter('berocket_advanced_label_editor_conditions_list', array( $this, 'condition_types'), $this->version_number);
        add_filter('brfr_data_berocket_advanced_label_editor', array(__CLASS__, 'berocket_advanced_label_editor'), $this->version_number);
        add_filter('brfr_berocket_advanced_label_editor_custom_explanation', array(__CLASS__, 'custom_explanation'), $this->version_number);
        add_filter('berocket_apl_label_show_label_style', array(__CLASS__, 'label_show_label_style'), $this->version_number, 2);
        add_action('wp_ajax_br_label_get_attribute_values', array(__CLASS__, 'get_attribute_values') );
        add_filter('brfr_berocket_advanced_label_editor_attribute_values', array(__CLASS__, 'section_attribute_values'), $this->version_number, 4);
        add_filter('brfr_berocket_advanced_label_editor_attribute_type_set', array(__CLASS__, 'section_attribute_type_set'), $this->version_number, 4);

        add_filter('berocket_apl_label_show_text', array(__CLASS__, 'label_show_text'), $this->version_number, 3);
        add_filter( 'berocket_apl_label_show_span_extra', array( $this, 'span_extra' ), 10, 2 );
        add_filter( 'berocket_apl_label_show_div_extra', array( $this, 'div_extra' ), 10, 2 );

        add_filter('berocket_labels_products_column_text', array(__CLASS__, 'products_column_text'), $this->version_number, 2);
        add_filter('berocket_apl_content_type_with_before_after', array($this, 'content_type_with_before_after'), $this->version_number, 1);
        add_filter('berocket_apl_label_show_div_class', array(__CLASS__, 'label_show_div_class'), $this->version_number, 3);
        add_filter('berocket_apl_label_show_div_style', array(__CLASS__, 'label_show_div_style'), $this->version_number, 2);
        add_filter('berocket_custom_post_br_labels_default_settings', array(__CLASS__, 'custom_post_default_settings'), $this->version_number);
        add_filter('berocket_labels_templates_hide', array(__CLASS__, 'paid_templates_hide'), $this->version_number );
        add_filter('berocket_labels_templates', array(__CLASS__, 'paid_templates'), $this->version_number );
        add_filter( 'berocket_labels_tooltip_content', array( $this, 'build_tooltip_content' ), $this->version_number );
        // add_filter('brapl_set_label_to_product', array($this, 'do_not_display_label_in_title'), 10, 3);

        add_filter('berocket_framework_item_content_range', array($this, 'ranges'), 10, 6);
        add_filter('berocket_framework_item_content_switch', array($this, 'switches'), 10, 6);

        add_filter('berocket_labels_templates_rotate', array($this, 'paid_templates_rotate'), 1, 1 );

        add_filter( 'berocket_label_adjust_options', array( $this, 'adjust_options' ), 1, 2 );
        add_filter( 'berocket_apl_label_show_text', array( $this, 'execute_shortcodes' ), 5, 3 );
        add_filter( 'brapl_i1_styles', array( $this, 'background_image' ), 1, 2 );
        add_filter( 'berocket_apl_label_show_label_style', array( $this, 'background_image' ), 1, 2 );

        add_filter( 'berocket_label_scale_option', array( $this, 'scale' ), 1, 2 );
        add_filter( 'berocket_apl_label_show_label_style', array( $this, 'shadow' ), 1, 2 );
        add_filter( 'brapl_timer_styles', array( $this, 'timer_shadow' ), 1, 2 );
        add_action('BeRocket_products_label_style_generate_each', array($this, 'javascript_include'), 10, 2);
        //CONDITIONS
        add_filter('brapl_condition_date_time_html', array( $this, 'type_condition_date_time'), 10, 3);
        add_filter('brapl_condition_date_time_check', array( $this, 'check_type_condition_date_time'), 10, 3);
    }
    public function type_condition_date_time($html, $name, $options) {
        if( ! $this->jqueryui_loaded ) {
            wp_enqueue_script( 'brpal-jquery-datepicker', plugins_url( 'js/jquery.datetimepicker.full.min.js', BeRocket_products_label_file ), array( 'jquery' ), BeRocket_products_label_version );
            wp_enqueue_style('brpal-jquery-datepicker', plugins_url( 'css/jquery.datetimepicker.min.css', BeRocket_products_label_file ), "", BeRocket_products_label_version );
            $this->jqueryui_loaded = true;
        }
        $def_options = array('from' => '', 'to' => '');
        $options = array_merge($def_options, $options);
        $now = new DateTime('now', wp_timezone());
        $html .= '<div>'
        . '<label>' . __('From', 'BeRocket_products_label_domain') 
        . '<input class="brapl_type_condition_date_time_input" type="text"' . (empty($options['is_example']) ? '' : 'data-') . 'name="' . $name . '[from]" value="' . esc_html($options['from']) . '"></label>'
        . '<label>' . __('To', 'BeRocket_products_label_domain') 
        . '<input class="brapl_type_condition_date_time_input" type="text"' . (empty($options['is_example']) ? '' : 'data-') . 'name="' . $name . '[to]" value="' . esc_html($options['to']) . '"></label>'
        . '<script>jQuery(document).ready(function() {if( typeof(jQuery(document).datetimepicker) == "function" ) {jQuery(".br_conditions .brapl_type_condition_date_time_input").not(".brapl_jqueryui_ready").datetimepicker({ format:"Y/m/d H:i", mask: true }).addClass("brapl_jqueryui_ready"); }});</script></div>'
        . '<p>' . __('Server Time', 'BeRocket_products_label_domain')  . ': ' . $now->format('Y/m/d H:i') . '</p>';
        return $html;
    }
    public function check_type_condition_date_time($show, $condition, $additional) {
        $now = new DateTime('now', wp_timezone());
        $now = $now->getTimestamp();
        try {
            $from = ( empty($condition['from']) || $condition['from'] == '____/__/__ __:__' ) ? false : new DateTime($condition['from'], wp_timezone());
        } catch (Exception $e) {
            $from = false;
        }
        try {
            $to = ( empty($condition['to']) || $condition['to'] == '____/__/__ __:__' ) ? false : new DateTime($condition['to'], wp_timezone());
        } catch (Exception $e) {
            $to = false;
        }
        $show = ( $from == FALSE || $from->getTimestamp() <= $now ) && ( $to == FALSE || $to->getTimestamp() >= $now );
        return $show;
    }
    // public function background_attribute( $br_label ) {
    //     return ( $br_label['content_type'] == 'attribute' && $br_label['attribute_type'] == 'name' ) ? $br_label['color'] : '';
    // }

    public function background_image( $styles, $br_label ) {
        if ( empty( $br_label[ 'image' ] ) ) return $styles;
        return $styles . " background-image:url({$br_label['image']}); background-size: cover;";
    }

    public function execute_shortcodes( $text, $br_label, $product ) {
        if ( !empty( $br_label['content_type'] ) && $br_label['content_type'] == 'custom' ) {
            $text = str_replace( '\"', '"', $text );
            $text = str_replace( "\'", "'", $text );
            global $shortcode_tags;
            $shortcodes_to_exclude = apply_filters( 'berocket_labels_shortcodes_list', false );
            $temp_shortcodes = array();
            foreach ( $shortcodes_to_exclude as $shortcode ) {
                if( isset($shortcode_tags[$shortcode]) ) {
                    $temp_shortcodes[$shortcode] = $shortcode_tags[$shortcode];
                    unset($shortcode_tags[$shortcode]);
                }
            }
            $text = do_shortcode($text);
            foreach ( $temp_shortcodes as $shortcode_tag => $shortcode ) {
                $shortcode_tags[$shortcode_tag] = $shortcode;
            }
        }

        return $text;
    }

    public function init() {
        if ( !is_admin() ) {
            $style = '
            @media (min-width: 1025px) {
                .berocket_hide_on_device_desktop:not(.berocket_alabel_id_demo) {display:none!important;}
            }
            @media (min-width: 768px) and (max-width: 1024px) {
                .berocket_hide_on_device_tablet:not(.berocket_alabel_id_demo) {display:none!important;}
            }
            @media (max-width: 767px) {
                .berocket_hide_on_device_mobile:not(.berocket_alabel_id_demo) {display:none!important;}
            }
            ';
            wp_add_inline_style('berocket_products_label_style', $style);            
        }

        $options = apply_filters( 'berocket_labels_get_base_options', false );
        if( apply_filters('bapf_paid_the_title_labels', empty( $options['disable_labels'] ) && !is_admin()) ) {
            add_filter('the_title', array($this, 'product_title'), 100, 2);
        }
    }

    public function adjust_options( $br_label, $label_id = false ) {
		if(! empty($br_label['template']) && $br_label['template'] == 'image-1000' && ! empty($br_label['custom_image_size']) ) {
			$custom_image_size = explode('*', $br_label['custom_image_size']);
			$width = intval($custom_image_size[0]);
			$height = intval($custom_image_size[1]);
			$ration = max($width / 80, $height / 80);
			$br_label['image_width'] = intval($width / $ration);
			$br_label['image_height'] = intval($height / $ration);
		}
        $size_multiplier = empty( $br_label['size_multiplier'] ) ? 1 : floatval( $br_label['size_multiplier'] );
        if ( $size_multiplier == 1 ) return $br_label;

        $scale_options = array(
            'image_height',
            'image_width',
        );
        if ( !empty( $br_label['margin_scale'] ) ) {
            $scale_options[] = 'bottom_margin';
            $scale_options[] = 'left_margin';
            $scale_options[] = 'right_margin';
            $scale_options[] = 'top_margin';
        }
        if ( !empty( $br_label['padding_top_scale'] ) ) {
            $scale_options[] = 'padding_top';
        }
        if ( !empty( $br_label['padding_horizontal_scale'] ) ) {
            $scale_options[] = 'padding_horizontal';
        }
        if ( !empty( $br_label['font_size_scale'] ) ) {
            $scale_options[] = 'font_size';
        }
        if ( !empty( $br_label['line_height_scale'] ) ) {
            $scale_options[] = 'line_height';
        }
        foreach( $scale_options as $option ) {
            if( !empty($br_label[$option]) and
                (
                    !empty($br_label[$option."_units"]) and $br_label[$option."_units"] == 'px'
                    or strpos($option, 'margin') !== false and $br_label["margin_units"] == 'px'
                )
            ) {
                $br_label[$option] = round( floatval( $br_label[$option] ) * $size_multiplier, 1 );
            }
        }

        foreach( array(
            'span_custom_css', 
            'i1_custom_css', 
            'i2_custom_css',
            'i3_custom_css', 
            'i4_custom_css',
            'b_custom_css', 
                ) as $custom_css ) {

                        
                $styles = explode( ';', $br_label[$custom_css] );
                foreach ( $styles as $i => $style ) {
                    $style = explode( ':', $style );

                    if ( in_array( trim( $style[0] ), 
                        array( 
                            'line-height', 
                            'margin-top', 
                            'margin-right', 
                            'margin-left', 
                            'margin-bottom', 
                            'line-height', 
                            'top', 
                            'bottom', 
                            'right', 
                            'left', 
                            'width', 
                            'height', 
                            'border-width', 
                            'border-top-width', 
                            'border-bottom-width', 
                            'border-left-width', 
                            'border-right-width', 
                                ) ) ) {

                        if ( strpos( ($number = $style[1]), 'px') === false ) continue;

                        $number = str_replace( 'px', '', $number );
                        $styles[$i] = $style[0] . ': ' . str_replace( $number, 
                            round( floatval( $number ) * $size_multiplier, 1 ), $style[1] );
                    }
                }
                $br_label[$custom_css] = implode( ';', $styles );
        }
        return $br_label;
    }

    public static function custom_post_default_settings($default_settings) {
        $default_settings += array(
            'attribute'                => '',
            'attribute_type'           => 'name',
            'border_width'             => 0,
            'border_color'             => '#FFFFFF',
            'font_size_scale'          => 1,
            'first_attribute'          => 1,
            'image_height_scale'       => 1,
            'image_width_scale'        => 1,
            'line_height_scale'        => 1,
            'margin_scale'             => 1,
            'opacity'                  => 1,
            'padding_horizontal_scale' => 1,
            'padding_top_scale'        => 1,
            'rotate'                   => '0deg',
            'shadow_blur'              => 2,
            'shadow_color'             => '#777777',
            'shadow_opacity'           => 0.7,
            'shadow_shift_down'        => 5,
            'shadow_shift_right'       => 5,
            'shadow_use'               => '',
            'size_multiplier'          => 1,
            'mobile_multiplier'        => 1,
            'tooltip_image'            => '',
        ); 
        return $default_settings;
    }

    function button($html, $field_item, $field_name, $value, $class, $extra) {
        $value = htmlentities($value);
        $html .= 
        "<span class='br_label_be_for'>{$field_item['label_be_for']}</span>
            <input type='button' name='$field_name' $class $extra value='$value' />
        <span class='br_label_for'>{$field_item['label_for']}</span>";

        return $html;
    }

    function ranges($html, $field_item, $field_name, $value, $class, $extra) {
        $value = htmlentities($value);
        $html .= 
        "<span class='br_label_be_for'>{$field_item['label_be_for']}</span>
        <input type='range' name='$field_name' $class $extra value='$value' />
        <span class='br_label_for'>{$field_item['label_for']}</span>";

        return $html;
    }

    function switches($html, $field_item, $field_name, $value, $class, $extra) {
        $value = htmlentities($value);
        $html .= 
        "<span class='br_label_be_for'>{$field_item['label_be_for']}</span>
        <label class='br_switch'>
            <input type='checkbox' name='$field_name' $class $extra value='$value' />
            <span class='br_slider'></span>
        </label>
        <span class='br_label_for'>{$field_item['label_for']}</span>";

        return $html;
    }

    public function condition_types($conditions) {
        $conditions[] = 'condition_page_id';
        $conditions[] = 'condition_page_woo_search';
        $conditions[] = 'condition_date_time';
        return $conditions;
    }

    public function content_type_with_before_after($types) {
        $types[] = 'attribute';
        $types[] = 'sale_val';
        $types[] = 'custom';
        return $types;
    }

    public static function products_column_text($text, $options) {
        if( $options['content_type'] == 'attribute' ) {
            $name = '';
            $taxonomy = get_taxonomy($options['attribute']);
            if( ! empty($taxonomy) && isset($taxonomy->label) ) {
                $name = $taxonomy->label;
            }
            $text = __('Product attribute', 'BeRocket_products_label_domain') . '<br><strong>' . $name . '</strong>';
        }
        return $text;
    }

    public function div_extra( $extra_data, $br_label ) {
        if( ! empty( $br_label['mobile_multiplier'] ) && floatval($br_label['mobile_multiplier']) && floatval($br_label['mobile_multiplier']) != 1.0  ) {
            $scale = floatval($br_label['mobile_multiplier']);
            if( strpos($br_label['span_custom_css'], 'scale(-1)') !== FALSE 
            || ( strpos($br_label['span_custom_css'], 'scaleX(-1)') !== FALSE && strpos($br_label['span_custom_css'], 'scaleY(-1)') !== FALSE ) ) {
                $scale = '-' . $scale;
            } elseif( strpos($br_label['span_custom_css'], 'scaleX(-1)') !== FALSE ) {
                $scale = '-' . $scale . ', ' . $scale;
            } elseif( strpos($br_label['span_custom_css'], 'scaleY(-1)') !== FALSE ) {
                $scale = $scale . ', -' . $scale;
            }
            $extra_data[] = 'data-mobilescale="' . $scale . '"';
        }
        return $extra_data;
    }

    public function span_extra( $extra_data, $br_label ) {
        if ( !empty( $br_label['img_title'] ) ) {
            $img_title = esc_url( $br_label['img_title'] );
            $extra_data = " title='$img_title'";
        }

        return $extra_data;
    }

    public static function label_show_text($text, $br_label, $product) {
        if( $br_label['content_type'] == 'attribute' ) {
            if( $product == 'demo' ) {
                $terms = get_terms($br_label['attribute']);
            } else {
                $product_id = br_wc_get_product_id($product);
                $terms = get_the_terms( $product_id, $br_label['attribute'] );
            }
            $text = FALSE;
            if( is_array( $terms ) && ! empty($br_label['attribute_values']) && is_array($br_label['attribute_values']) ) {
                foreach( $terms as $term ) {
                    if( ! empty($br_label['attribute_values_all']) || in_array($term->term_id, $br_label['attribute_values']) ) {
                        if( $text === FALSE ) {
                            $text = array();
                        }
                        if ($br_label['attribute_type'] == 'color' || $br_label['attribute_type'] == 'image') {
                            $meta = get_metadata('berocket_term', $term->term_id, $br_label['attribute_type']);
                            $meta = br_get_value_from_array($meta, 0, '');
                            if( ! empty($_POST['tax_color_set'][$term->term_id]) ) {
                                $meta = $_POST['tax_color_set'][$term->term_id];
                            }
                            $meta = esc_attr($meta);
                            if( $br_label['attribute_type'] == 'color' ) {
                                if( ! empty($meta) ) {
                                    if($meta[0] != '#') {
                                        $meta = '#' . $meta;
                                    }
                                    $text[] = $meta;
                                }
                            } elseif( substr( $meta, 0, 3) == 'fa-' ) {
                                $text[] = '<i class="'.($product == 'demo' ? 'berocket_color_image_term_'.$term->term_id.' ' : '').'fa ' . $meta . '" title="' . $term->name . '"></i>';
                            } elseif ( !empty( $meta ) ) {
                                // if ( empty( $meta ) ) {
                                //     $meta = plugin_dir_url(__FILE__) . '../images/img-placeholder.png';
                                // }
                                $text[] = '<img class="'.($product == 'demo' ? 'berocket_color_image_term_'.$term->term_id.' ' : '').'berocket_widget_icon" src="'.$meta.'" alt="' . $term->name . '" title="' . $term->name . '">';
                            }
                        } else {
                            $text[] = $term->name;
                        }

                        if( ! empty($br_label['first_attribute']) ) {
                            break;
                        }
                    }
                }
            }
            if ( empty( $text ) && $product == 'demo' ) {
                $text = 'SALE';
            }
        } elseif( $br_label['content_type'] == 'sale_val' ) {
            $text = '';
            if( $product == 'demo' || $product->is_on_sale() ) {
                $price_ratio = false;
                if( $product == 'demo' ) {
                    $product_sale = '54.5';
                    $product_regular = '61.25';
                    $price_ratio = $product_regular - $product_sale;
                } else {
                    /*$product_sale = br_wc_get_product_attr($product, 'sale_price');
                    $product_regular = br_wc_get_product_attr($product, 'regular_price');*/
                    $product_sale = $product->get_sale_price('view');
                    $product_regular = $product->get_regular_price('view');
                    $product_sale = wc_get_price_to_display( $product, array( 'price' => $product_sale ) );
                    $product_regular = wc_get_price_to_display( $product, array( 'price' => $product_regular ) );
                    if( ! empty($product_sale) && $product_sale != $product_regular ) {
                        $price_ratio = $product_regular - $product_sale;
                    }
                    if( $product->has_child() ) {
                        foreach($product->get_children() as $child_id) {
                            $child = br_wc_get_product_attr($product, 'child', $child_id);
                            /*$child_sale = br_wc_get_product_attr($child, 'sale_price');
                            $child_regular = br_wc_get_product_attr($child, 'regular_price');*/
                            $child_sale = $child->get_sale_price('view');
                            $child_regular = $child->get_regular_price('view');
                            $child_sale = wc_get_price_to_display( $child, array( 'price' => $child_sale ) );
                            $child_regular = wc_get_price_to_display( $child, array( 'price' => $child_regular ) );
                            if( ! empty($child_sale) && $child_sale != $child_regular ) {
                                $price_ratio2 = $child_regular - $child_sale;
                                if( $price_ratio === false || $price_ratio2 < $price_ratio ) {
                                    $price_ratio = $price_ratio2;
                                }
                            }
                        }
                    }
                }
                if( $price_ratio !== false ) {
                    $text = wc_price($price_ratio);
                    if( ! empty($br_label['discount_minus']) ) {
                        $text = '-'.$text;
                    }
                }
            }
            if( empty($text) ) {
                $text = FALSE;
            }
        } elseif( $br_label['content_type'] == 'custom' ) {
            $replacement = array(
                'sale_p'    => '',
                'sale_val'  => '',
                'sale_end'  => '',
            );
            $preg_replacement = array(
                'sale' => array( 'preg' => "/%sale%(.*?)%sale%/", 'replace' => ''),
                'nsale' => array( 'preg' => "/%nsale%(.*?)%nsale%/", 'replace' => '${1}'),
            );
            if( $product == 'demo' || $product->is_on_sale() ) {
                if( $product == 'demo' ) {
                    $product_sale = '54.5';
                    $product_regular = '61.25';
                    $replacement['sale_p'] = $product_sale / $product_regular;
                    $replacement['sale_val'] = $product_regular - $product_sale;
                    $replacement['sale_end'] = strtotime('+12 hours +30 minutes', current_time('timestamp') );
                    $preg_replacement['sale']['replace'] = '${1}';
                    $preg_replacement['nsale']['replace'] = '';
                } else {
                    $product_id = br_wc_get_product_id($product);
                    /*$product_sale = br_wc_get_product_attr($product, 'sale_price');
                    $product_regular = br_wc_get_product_attr($product, 'regular_price');
                    $product_sale = wc_get_price_to_display($product, array('price' => $product_sale));
                    $product_regular = wc_get_price_to_display($product, array('price' => $product_regular));*/
                    $product_sale = $product->get_sale_price('view');
                    $product_regular = $product->get_regular_price('view');
                    $product_sale = wc_get_price_to_display( $product, array( 'price' => $product_sale ) );
                    $product_regular = wc_get_price_to_display( $product, array( 'price' => $product_regular ) );
                    if( ! empty($product_sale) && ! empty($product_regular) && $product_sale != $product_regular ) {
                        $replacement['sale_p'] = $product_sale / $product_regular;
                        $replacement['sale_val'] = $product_regular - $product_sale;
                        $replacement['sale_end'] = get_post_meta( $product_id, '_sale_price_dates_to', true );
                    }
                    if( $product->has_child() ) {
                        foreach($product->get_children() as $child_id) {
                            $child = br_wc_get_product_attr($product, 'child', $child_id);
                            $child_sale = $child->get_sale_price('view');
                            $child_regular = $child->get_regular_price('view');
                            $child_sale = wc_get_price_to_display( $child, array( 'price' => $child_sale ) );
                            $child_regular = wc_get_price_to_display( $child, array( 'price' => $child_regular ) );
                            if( ! empty($child_sale) && ! empty($child_regular) && $child_sale != $child_regular ) {
                                $price_ratio2 = $child_sale / $child_regular;
                                $price_val2 = $child_regular - $child_sale;
                                if( $replacement['sale_p'] === '' || $price_ratio2 < $replacement['sale_p'] ) {
                                    $replacement['sale_p'] = $price_ratio2;
                                    $replacement['sale_val'] = $price_val2;
                                    $replacement['sale_end'] = get_post_meta( $child_id, '_sale_price_dates_to', true );
                                }
                            }
                        }
                    }
                }
                if( ! empty($replacement['sale_val']) ) {
                    $replacement['sale_val'] = wc_price($replacement['sale_val']);
                } else {
                    $replacement['sale_val'] = '';
                }
                if( ! empty($replacement['sale_p']) ) {
                    $preg_replacement['sale']['replace'] = '${1}';
                    $preg_replacement['nsale']['replace'] = '';

                    $replacement['sale_p'] = ($replacement['sale_p'] * 100);
                    $replacement['sale_p'] = number_format($replacement['sale_p'], 0, '', '');
                    if ( $replacement['sale_p'] == 100 ) $replacement['sale_p'] = 99;
                    if ( $replacement['sale_p'] == 0 ) $replacement['sale_p'] = 1;

                    $price_ratio = $replacement['sale_p'] * 1;
                    $replacement['sale_p'] = (100 - $replacement['sale_p'])."%";
                } else {
                    $replacement['sale_p'] = '';
                }
                if( ! empty($replacement['sale_end']) ) {
                    $date_end = new DateTime(date( 'Y-m-d H:i', $replacement['sale_end'] ), wp_timezone());
                    $date_today = new DateTime(date( 'Y-m-d H:i' ), wp_timezone());
                    $dates = date_diff($date_today, $date_end);
                    if( ! $dates->invert ) {
                        if($dates->days > 0 ) {
                            $replacement['sale_end'] = $dates->days." "._n( 'day', 'days', $dates->days, 'BeRocket_products_label_domain' );
                        } elseif($dates->h > 0 && $dates->days == 0 ) {
                            $replacement['sale_end'] = $dates->h." "._n( 'hour', 'hours', $dates->h, 'BeRocket_products_label_domain' );
                        } elseif($dates->i > 0 && $dates->h == 0) {
                            $replacement['sale_end'] = $dates->i." "._n( 'minute', 'minutes', $dates->i, 'BeRocket_products_label_domain' );
                        }
                    } else {
                        $replacement['sale_end'] = '';
                    }
                } else {
                    $replacement['sale_end'] = '';
                }
            }
            foreach($replacement as $search => $replace) {
                $text = str_replace('%'.$search.'%', $replace, $text);
            }
            foreach($preg_replacement as $replace_data) {
                $text = preg_replace($replace_data['preg'], $replace_data['replace'], $text);
            }

            if( empty($text) ) {
                $text = FALSE;
            }
        }

        if( $text !== false && in_array( $br_label[ 'content_type' ], array( 'sale_val', 'custom' ) ) && ! empty( $br_label[ 'image' ] ) ) {
            $text = '<div class="berocket_image_background" style="background-image:url(' . $br_label[ 'image' ] . ');display:block;width:100%;height:100%;background-size:100% 100%;" title="' . $br_label[ 'img_title' ] . '">' . $text . '</div>';
        }
        return $text;
    }

    public static function get_attribute_values() {
        $attribute_values = array();
        if( ! empty($_POST['attribute']) ) {
            $attribute_name = $_POST['attribute'];
            $terms = get_terms($attribute_name);
            if( is_array($terms) ) {
                foreach($terms as $term) {
                    $attribute_values[] = array('id' => $term->term_id, 'name' => $term->name);
                }
            }
        }
        echo json_encode($attribute_values);
        wp_die();
    }

    public static function section_attribute_values($html, $item, $options, $name) {
        $html = '<tr class="class-berocket-label-attribute-values"><th scope="row">' . __( 'Attribute values', 'BeRocket_products_label_domain' ) . '</th><td>';
        $html2 = '<div class="berocket_label_ berocket_label_attribute_values" data-name="' . $name . '" style="overflow: hidden;">';
        $all_selected = true;
        if( ! empty($options['attribute']) ) {
            $terms = get_terms($options['attribute']);
            if( ! isset($options['attribute_values']) || ! is_array($options['attribute_values']) ) {
                $options['attribute_values'] = array();
            }
            foreach($terms as $term) {
                $selected = true;
                if( empty($options['attribute_values_all']) && ! in_array($term->term_id, $options['attribute_values']) ) {
                    $all_selected = false;
                    $selected = false;
                }
                $html2 .= '<div><label><input type="checkbox"' . ($selected ? ' checked' : '') . ' name="' . $name . '[attribute_values][]" value="' . $term->term_id . '">' . $term->name . '</label></div>';
            }
        }

        $html .= '<label><input type="checkbox" name="' . $name . '[attribute_values_all]" value="1" class="brapl_attribute_values_select_all"></checkbox>' . __( 'Select all', 'BeRocket_products_label_domain' ) . '</label>';

        // $html .= '<label><input type="checkbox"' . (! empty($options['attribute_values_all']) ? ' checked' : '') . ' name="' . $name . '[attribute_values_all]" value="1" class="brapl_attribute_values_select_all"></checkbox>' . __( 'Select all', 'BeRocket_products_label_domain' ) . '</label>';

        $attributes = empty( $options['attribute_values'] ) ? '' : implode( ',', $options['attribute_values'] );
        $html .= '<div style="overflow:auto; max-height: 400px;">';
        $html .= $html2;
        $html .= '<script>
        jQuery(document).on(\'change\', \'.berocket_label_content_type\', function() {
            if( jQuery(this).val() == "attribute" ) {
                setTimeout(function() {
                    jQuery(\'.berocket_label_attribute_select\').trigger(\'change\');
                }, 10);
            }
        });
        jQuery(document).on(\'change\', \'.berocket_label_attribute_select\', function() {
            var name = jQuery(\'.berocket_label_attribute_values\').data(\'name\');
            var val = jQuery(this).val();
            var attributes = [' . $attributes . '];
            if ( val == "" ) {
                jQuery(\'.class-berocket-label-attribute-values, .class-berocket-label-attribute-data-set\').hide();
                jQuery(\'.berocket_labels_attribute_type_select, .class-berocket-label-attribute-first-value, .berocket_labels_attribute_type_select\').each(function (){ jQuery(this).parents("tr").hide(); });
            } else {
                jQuery(\'.class-berocket-label-attribute-values\').show().removeClass("berocket_template_hide_not_worked_option");
                jQuery(\'.berocket_labels_attribute_type_select, .class-berocket-label-attribute-first-value\').each(function (){ jQuery(this).parents("tr").show().removeClass("berocket_template_hide_not_worked_option"); });
            }
            jQuery.post(ajaxurl, {action: \'br_label_get_attribute_values\', attribute: jQuery(this).val()}, function(data) {
                // if ( jQuery("[name=\'br_labels[attribute_values][]\']").length > 0 ) return;
                var html = "";
                data.forEach(function(val, index, arr) {
                    var checked = attributes.includes( val.id ) ? "checked" : "";
                    html += \'<div><label><input type="checkbox" \'+checked+\' name="\'+name+\'[attribute_values][]" value="\'+val.id+\'">\'+val.name+\'</label></div>\';
                });
                jQuery(\'.berocket_label_attribute_values\').html(html);
                // jQuery(".brapl_attribute_values_select_all").prop("checked", true);
                jQuery(".berocket_labels_attribute_type_select").trigger("change");
            }, \'json\');

            jQuery(".berocket_labels_attribute_type_select").trigger("change");
        });
        jQuery(document).on("change", ".brapl_attribute_values_select_all", function() {
            jQuery(".berocket_label_attribute_values input").prop("checked", jQuery(this).prop("checked"));
        });
        jQuery(document).on("change", ".berocket_label_attribute_values input", function() {
            jQuery(".brapl_attribute_values_select_all").prop("checked", false);
        });
        </script>';
        $html .= '</div></div></td></tr>';
        return $html;
    }

    public static function section_attribute_type_set($html, $item, $options, $name) {
        $html = '<tr class="class-berocket-label-attribute-data-set"><th scope="row">' . __( 'Attribute data set', 'BeRocket_products_label_domain' ) . '</th><td>';
        $html .= '<div style="overflow:auto; max-height: 400px;"class="berocket_label_ attribute_data_set">';
        if( ! empty($options['attribute']) && ! empty($options['attribute_type']) && in_array($options['attribute_type'], array('image', 'color')) ) {
            $type = $options['attribute_type'];
            $taxonomy_name = $options['attribute'];
            $html .= BeRocket_products_label_better_position::color_list_view( $type, $taxonomy_name );
        }
        $html .= '<script>
        jQuery(".attribute_data_set input").addClass("br_not_change");
        jQuery(document).on("change", ".berocket_labels_attribute_type_select", function() {
            var attribute = jQuery(".berocket_label_attribute_select").val();
            var type = jQuery(".berocket_labels_attribute_type_select").val();
            jQuery(".attribute_data_set").html("");
            if( attribute != "" && (type == "color" || type == "image") ) {
                jQuery(".br_label_backcolor").parents("li").first().hide();
                jQuery(".class-berocket-label-attribute-data-set").show();
                jQuery.post(ajaxurl, {action: "berocket_apl_color_listener", tax_color_set_name: attribute, tax_color_set_type: type}, function(data) {
                    jQuery(".attribute_data_set").html(data);
                    if( type == "color" ) {
                        br_init_colorpick();
                    }
                    jQuery(".attribute_data_set input").addClass("br_not_change");
                });
            } else {
                jQuery(".br_label_backcolor").parents("li").first().show();
                jQuery(".class-berocket-label-attribute-data-set").hide();
            }
        });
        function berocket_use_backround_color_or_no() {
            if( jQuery(".berocket_label_content_type").val() == "attribute" && jQuery(".berocket_labels_attribute_type_select").val() == "color" ) {
                jQuery(".br_label_backcolor_use").parents("tr").first().hide();
                jQuery(".br_label_backcolor").parents("tr").first().hide();
            } else {
                jQuery(".br_label_backcolor_use").parents("tr").first().show();
            }
        }
        jQuery(document).on("change", ".berocket_label_content_type, .berocket_labels_attribute_type_select", berocket_use_backround_color_or_no);
        jQuery(document).ready(berocket_use_backround_color_or_no);
        </script>';
        $html .= '</div></td></tr>';
        return $html;
    }

    public static function label_show_div_class($div_class, $br_label, $product) {
        if( (!is_admin() or wp_doing_ajax()) && !empty($br_label['hide_on_device']) && is_array($br_label['hide_on_device']) && count($br_label['hide_on_device']) ) {
            foreach($br_label['hide_on_device'] as $hide_on_device) {
                $div_class .= ' berocket_hide_on_device_'.$hide_on_device;
            }
        }
        return $div_class;
    }

    public static function label_show_div_style($div_style, $br_label) {
        if( isset($br_label['rotate']) && $br_label['rotate'] != '0deg' ) {
            $div_style .= 'transform:rotate(' . $br_label['rotate'] . ');';
        }
        return $div_style;
    }

    public static function label_show_label_style($label_style, $br_label) {
        if( !empty($br_label['border_color']) ) {
            $label_style .= 'border-color:' . $br_label['border_color'] . ';';
        }
        if( !empty($br_label['border_width']) && $br_label['border_width'] != 0 ) {
            $label_style .= 'border-style: solid; border-width:'.$br_label['border_width'].'px;';
        }

        if( !empty($br_label['opacity']) && $br_label['opacity'] != 1 ) {
            $label_style .= 'filter: alpha(opacity=' . ($br_label['opacity']*100) . ');
                -moz-opacity: '.$br_label['opacity'].';
                -khtml-opacity: '.$br_label['opacity'].';
                opacity: '.$br_label['opacity'].';';
        }

        return $label_style;
    }

    public function build_tooltip_content( $br_label ) {
        $tooltip_content = empty( $br_label['tooltip_content'] ) ? '' 
            : "<span class='berocket_tooltip_text'>{$br_label['tooltip_content']}</span>";
        $tooltip_content .= empty( $br_label['tooltip_image'] ) ? '' 
            : "<img class='berocket_tooltip_image' src='{$br_label['tooltip_image']}'/>";
        $br_label['tooltip_content'] = $tooltip_content;

        return $br_label;
    }

    public static function berocket_advanced_label_editor($data) {
        $default_settings = static::custom_post_default_settings( array() );

        $attributes = get_object_taxonomies( 'product', 'objects');
        $product_attributes = array();
        $product_attributes[] = array('value' => '', 'text' => __('--- Choose attribute ---', 'BeRocket_products_label_domain'));
        
        foreach( $attributes as $attribute ) {
            if( ! in_array($attribute->name, array('product_type', 'product_visibility') ) ) {
                $attribute_i = array();
                $attribute_i['value'] = $attribute->name;
                $attribute_i['text'] = $attribute->label;
                $product_attributes[] = $attribute_i;
            }
        }

        $data['General'] = berocket_insert_to_array(
            $data['General'],
            'content_type',
            array(
                'hide_on_device' => array(
                    "label"    => __('Hide On', 'BeRocket_products_label_domain'),
                    "items"    => array(
                        array(
                            "type"     => "checkbox",
                            "label_for"=> __('Mobile', 'BeRocket_products_label_domain'),
                            "name"     => array('hide_on_device', 'mobile'),
                            "class"    => 'berocket_hide_on_device br_not_change',
                            "value"    => "mobile"
                        ),
                        array(
                            "type"     => "checkbox",
                            "label_for"=> __('Tablet', 'BeRocket_products_label_domain'),
                            "name"     => array('hide_on_device', 'tablet'),
                            "class"    => 'berocket_hide_on_device br_not_change',
                            "value"    => "tablet"
                        ),
                        array(
                            "type"     => "checkbox",
                            "label_for"=> __('Desktop', 'BeRocket_products_label_domain'),
                            "name"     => array('hide_on_device', 'desktop'),
                            "class"    => 'berocket_hide_on_device br_not_change',
                            "value"    => "desktop"
                        )
                    ),
                ),
            ), true
        );

        $data['General'] = berocket_insert_to_array(
            $data['General'],
            'text',
            array(
                'custom_explanation_section' => array(
                    "label"    => __('Custom explanation', 'BeRocket_products_label_domain'),
                    "section"  => "custom_explanation",
                ),
            )
        );

        $data['General'] += array(
            'attribute' => array(
                "type"     => "selectbox",
                "options"  => $product_attributes,
                "class"    => 'berocket_label_ berocket_label_attribute berocket_label_attribute_select br_not_change',
                "label"    => __('Attribute', 'BeRocket_products_label_domain'),
                "name"     => "attribute",
                "value"    => $default_settings['attribute'],
            ),
            'attribute_values' => array(
                "section" => "attribute_values",
            ),
            'first_attribute' => array(
                "type"     => "checkbox",
                "label"    => __('Display first available value', 'BeRocket_products_label_domain'),
                "class"    => 'berocket_label_ class-berocket-label-attribute-first-value',
                "name"     => "first_attribute",
                "value"    => $default_settings['first_attribute'],
            ),
            'attribute_type' => array(
                "type"     => "selectbox",
                "options"  => array(
                    array('value' => 'name',  'text' => __('Term name', 'BeRocket_products_label_domain')),
                    array('value' => 'color', 'text' => __('Color', 'BeRocket_products_label_domain')),
                    array('value' => 'image', 'text' => __('Image', 'BeRocket_products_label_domain')),
                ),
                "label"    => __('Attribute data type', 'BeRocket_products_label_domain'),
                "class"    => "berocket_label_ berocket_labels_attribute_type_select",
                "name"     => "attribute_type",
                "value"    => $default_settings['attribute_type'],
            ),
            'attribute_type_set' => array(
                "section" => "attribute_type_set",
            ),
        );

        $data['General']['content_type']['options'] = array_merge( $data['General']['content_type']['options'], array(
            array('value' => 'sale_val',  'text' => __('Discount amount', 'BeRocket_products_label_domain')),
            array('value' => 'custom',    'text' => __('Custom discount text', 'BeRocket_products_label_domain')),
            array('value' => 'attribute', 'text' => __('Product attribute', 'BeRocket_products_label_domain')),
        ) );

        $data['General']['discount_minus']['class'] .= ' berocket_label_sale_val';
        $data['General']['text']['class'] .= ' berocket_label_custom';
        $data['General']['text_before']['items']['text_before']['class'] .= 
            ' berocket_label_attribute berocket_label_sale_val berocket_label_sale_val berocket_label_custom';
        $data['General']['text_after']['items']['text_after']['class'] .= 
            ' berocket_label_attribute berocket_label_sale_val berocket_label_sale_val berocket_label_custom';
 
        $data['Position']['rotate'] = array(
            "type"     => "selectbox",
            "options"  => array(
                array('value' => '-90deg', 'text' => __('-90deg', 'BeRocket_products_label_domain')),
                array('value' => '-75deg', 'text' => __('-75deg', 'BeRocket_products_label_domain')),
                array('value' => '-60deg', 'text' => __('-60deg', 'BeRocket_products_label_domain')),
                array('value' => '-45deg', 'text' => __('-45deg', 'BeRocket_products_label_domain')),
                array('value' => '-30deg', 'text' => __('-30deg', 'BeRocket_products_label_domain')),
                array('value' => '-15deg', 'text' => __('-15deg', 'BeRocket_products_label_domain')),
                array('value' => '0deg',   'text' => __('0deg', 'BeRocket_products_label_domain')),
                array('value' => '15deg',  'text' => __('15deg', 'BeRocket_products_label_domain')),
                array('value' => '30deg',  'text' => __('30deg', 'BeRocket_products_label_domain')),
                array('value' => '45deg',  'text' => __('45deg', 'BeRocket_products_label_domain')),
                array('value' => '60deg',  'text' => __('60deg', 'BeRocket_products_label_domain')),
                array('value' => '75deg',  'text' => __('75deg', 'BeRocket_products_label_domain')),
                array('value' => '90deg',  'text' => __('90deg', 'BeRocket_products_label_domain')),
            ),
            "label"    => __('Rotate', 'BeRocket_products_label_domain'),
            "name"     => "rotate",
            "value"    => $default_settings['rotate'],
            "extra"    => ' data-for=".br_alabel" data-style="transform" data-ext="rotate(VAL)"',
            "class"    => "br_js_change",
        );

        $data['Position'] = apply_filters( 'berocket_label_scale_option', $data['Position'], array( 'padding_horizontal', 'padding_top' ) );

        $data['Position']['type']['options'][] = array('value' => 'in_title', 'text' => __('In title', 'BeRocket_products_label_domain'));

        $data['Style'] = berocket_insert_to_array(
            $data['Style'],
            'border_radius',
            array(
                'border_width' => array(
                    "type"  => "number",
                    "label" => __('Border width', 'BeRocket_products_label_domain'),
                    "name"  => "border_width",
                    "extra" => ' min="0" max="20" data-for=".br_alabel>span" data-forsvg="1" data-style="border-width" data-ext="px"',
                    "class" => 'br_js_change',
                    "value" => $default_settings['border_width'],
                    "label_for" => __('px', 'BeRocket_products_label_domain'),
                ),
                'border_color' => array(
                    "type"  => "color",
                    "label" => __('Border color', 'BeRocket_products_label_domain'),
                    "name"  => "border_color",
                    "class" => 'br_js_change',
                    "extra" => " data-for='.br_alabel>span' data-forsvg='1' data-style='border-color' data-ext=''",
                    "value" => $default_settings['border_color'],
                ),
                'size_multiplier' => array(
                    "label" => __('Size multiplier', 'BeRocket_products_label_domain'),
                    "items" => array(
                        array(
                            "type"  => "range",
                            "name"  => "size_multiplier",
                            "extra" => " id='size_multiplier' min='0.1' max='3' step='0.1'",
                            "class" => 'br_range',
                            "value" => $default_settings['size_multiplier'],
                        ),
                        array(
                            "type"  => "number",
                            "name"  => "size_multiplier_num",
                            "extra" => " id='size_multiplier_num' min='0.1' max='3' step='0.1'",
                            "class" => 'br_range_num',
                            "value" => $default_settings['size_multiplier'],
                            "label_be_for" => 'x',
                        ),
                    ),
                ),
                'mobile_multiplier' => array(
                    "label" => __('Mobile device multiplier', 'BeRocket_products_label_domain'),
                    "items" => array(
                        array(
                            "type"  => "range",
                            "name"  => "mobile_multiplier",
                            "extra" => " id='mobile_multiplier' min='0.1' max='3' step='0.1'",
                            "class" => 'br_range',
                            "value" => $default_settings['mobile_multiplier'],
                        ),
                        array(
                            "type"  => "number",
                            "name"  => "mobile_multiplier_num",
                            "extra" => " id='mobile_multiplier_num' min='0.1' max='3' step='0.1'",
                            "class" => 'br_range_num',
                            "value" => $default_settings['mobile_multiplier'],
                            "label_be_for" => 'x',
                        ),
                    ),
                ),
                'opacity' => array(
                    "label" => __('Opacity', 'BeRocket_products_label_domain'),
                    "items" => array(
                        array(
                            "type"  => "range",
                            "name"  => "opacity",
                            "extra" => " id='opacity' min='0' max='1' step='0.1' data-for='.br_alabel>span' data-style='opacity' data-ext=''",
                            "class" => 'br_range',
                            "value" => $default_settings['opacity'],
                        ),
                        array(
                            "type"  => "number",
                            "name"  => "opacity_num",
                            "extra" => " id='opacity_num' min='0' max='1' step='0.1' data-for='.br_alabel>span' data-style='opacity' data-ext=''",
                            "class" => 'br_range_num',
                            "value" => $default_settings['opacity'],
                        ),
                    ),
                ),
                'shadow_use' => array(
                    "type"     => "checkbox",
                    "label"    => __('Use shadow effect', 'BeRocket_products_label_domain'),
                    "name"     => "shadow_use",
                    "value"    => '1',
                    'class'    => 'br_use_options',
                    "extra"    => 'id="br_shadow_use" data-for=".br_alabel > span" data-style="use:box-shadow" data-ext=""',
                    "selected" => false,
                ),
                'shadow_shift_right' => array(
                    "label"    => __('Shadow shift right', 'BeRocket_products_label_domain'),
                    "items"    => array(
                        array(
                            "type"     => "range",
                            "name"     => "shadow_shift_right",
                            "extra"    => " id='shadow_shift_right' min='-50' max='50'",
                            "class"    => 'br_label_style_option br_range br_shadow_option',
                            "value"    => $default_settings['shadow_shift_right'],
                        ),
                        array(
                            "type"     => "number",
                            "name"     => "shadow_shift_right_num",
                            "extra"    => " id='shadow_shift_right_num' min='-50' max='50'",
                            "class"    => 'br_label_style_option br_range_num br_shadow_option',
                            "value"    => $default_settings['shadow_shift_right'],
                        ),
                    ),
                ),
                'shadow_shift_down' => array(
                    "label"    => __('Shadow shift down', 'BeRocket_products_label_domain'),
                    "items"    => array(
                        array(
                            "type"     => "range",
                            "name"     => "shadow_shift_down",
                            "extra"    => " id='shadow_shift_down' min='-50' max='50'",
                            "class"    => 'br_label_style_option br_range br_shadow_option',
                            "value"    => $default_settings['shadow_shift_down'],
                        ),
                        array(
                            "type"     => "number",
                            "name"     => "shadow_shift_down_num",
                            "extra"    => " id='shadow_shift_down_num' min='-50' max='50'",
                            "class"    => 'br_label_style_option br_range_num br_shadow_option',
                            "value"    => $default_settings['shadow_shift_down'],
                        ),
                    ),
                ),
                'shadow_blur' => array(
                    "label"    => __('Shadow blur', 'BeRocket_products_label_domain'),
                    "items"    => array(
                        array(
                            "type"     => "range",
                            "name"     => "shadow_blur",
                            "extra"    => " id='shadow_blur' min='0' max='50'",
                            "class"    => 'br_label_style_option br_range br_shadow_option',
                            "value"    => $default_settings['shadow_blur'],
                        ),
                        array(
                            "type"     => "number",
                            "name"     => "shadow_blur_num",
                            "extra"    => " id='shadow_blur_num' min='0' max='50'",
                            "class"    => 'br_label_style_option br_range_num br_shadow_option',
                            "value"    => $default_settings['shadow_blur'],
                        ),
                    ),
                ),
                'shadow_opacity' => array(
                    "label"    => __('Shadow opacity', 'BeRocket_products_label_domain'),
                    "items"    => array(
                        array(
                            "type"     => "range",
                            "name"     => "shadow_opacity",
                            "extra"    => " id='shadow_opacity' min='0' max='1' step='0.1'",
                            "class"    => 'br_label_style_option br_range br_shadow_option',
                            "value"    => $default_settings['shadow_opacity'],
                        ),
                        array(
                            "type"     => "number",
                            "name"     => "shadow_opacity",
                            "extra"    => " id='shadow_opacity_num' min='0' max='1' step='0.1'",
                            "class"    => 'br_label_style_option br_range_num br_shadow_option',
                            "value"    => $default_settings['shadow_opacity'],
                        ),
                    ),
                ),
                'shadow_color' => array(
                    "type"     => "color",
                    "label"    => __('Shadow color', 'BeRocket_products_label_domain'),
                    "name"     => "shadow_color",
                    "class"    => 'br_label_style_option br_shadow_option',
                    "extra"    => " data-for='.br_alabel>span' data-style='' data-ext=''",
                    "value"    => $default_settings['shadow_color'],
                ),
            )
        );

        $data['Style']['font_color']['class'] .= ' berocket_label_sale_val berocket_label_attribute berocket_label_custom';

        $data['Style'] = apply_filters( 'berocket_label_scale_option', $data['Style'], 
            array( 'font_size', 'image_height', 'image_width', 'line_height' ) );
        
        $data['Tooltip'] = berocket_insert_to_array(
            $data['Tooltip'],
            'tooltip_content',
            array( 'tooltip_image' => array(
                "type"     => "image",
                "label"    => __('Tooltip image', 'BeRocket_products_label_domain'),
                "name"     => "tooltip_image",
                "class"    => 'berocket_image_value',
                "extra"    => ' data-for=".br_alabel>span" data-style="tooltip_image" data-ext=""',
                "value"    => $default_settings['tooltip_image'],
            ))
        );

        return $data;
    }

    public static function custom_explanation() {
        $html = '<tr>';
        $html .= '<th>'.__('Replacements', 'BeRocket_products_label_domain').'</th><td>';
        $html .= '<ul class="berocket_label_ berocket_label_custom">
                    <li><strong>%sale_p%</strong> - '.__('Discount percentage', 'BeRocket_products_label_domain').'</li>
                    <li><strong>%sale_val%</strong> - '.__('Discount amount', 'BeRocket_products_label_domain').'</li>
                    <li><strong>%sale_end%</strong> - '.__('Time left for discount', 'BeRocket_products_label_domain').'</li>
                    <li><strong>%sale%</strong><small>ANYTEXT</small><strong>%sale%</strong> - '.__('Display ANYTEXT only if product with discount', 'BeRocket_products_label_domain').'</li>
                    <li><strong>%nsale%</strong><small>ANYTEXT</small><strong>%nsale%</strong> - '.__('Display ANYTEXT only if product without discount', 'BeRocket_products_label_domain').'</li>
                </ul>';
        $html .= '</td></tr>';
        return $html;
    }

    public static function paid_templates_hide( $templates_hide = array() ) {

        $templates_hide['css'] += array(
            6 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            7 => array(
                'border_color',
                'border_width',
                'border_radius',
                'image_height',
                'image_width',
                'img_title',
            ),
            24 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image',
                'image_width',
                'img_title',
                'top_padding',
            ),
            25 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            26 => array(
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            8 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            28 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            29 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            17 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            18 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'top_padding',
            ),
            9 => array(
                'border_radius',
                'image_height',
                'image_width',
                'img_title',
            ),
            10 => array(
                'image_height',
                'image_width',
                'img_title',
            ),
            21 => array(
                'border_color',
                'border_radius',
                'border_width',
                'img_title',
                'top_padding',
            ),
            22 => array(
                'image_height',
                'image_width',
                'border_color',
                'border_radius',
                'border_width',
                'img_title',
                'top_padding',
            ),
            11 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'rotate',
            ),
            12 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'rotate',
            ),
            13 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'rotate',
            ),
            14 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
            ),
            27 => array(
                'border_color',
                'border_radius',
                'border_width',
                'img_title',
            ),
            15 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title'
            ),
            16 => array(
                'border_radius',
                'color',
                'color_use',
                'image',
                'image_height',
                'image_width',
                'img_title',
            ),
            30 => array(
                'img_title',
            ),
        );
        
        $templates_hide['image'] += array(
            1 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'line_height',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            2 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'line_height',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'text',
                'top_padding',
                'text_after',
                'text_before',
            ),
            3 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            4 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'font_size',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            5 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_before',
                'text_after',
                'top_padding',
            ),
            6 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            7 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            8 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            9 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            10 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            11 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            12 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'rotate',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            13 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'rotate',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
            14 => array(
                'attribute',
                'attribute_type',
                'attribute_values_all',
                'border_color',
                'border_radius',
                'border_width',
                'color',
                'color_use',
                'content_type',
                'discount_minus',
                'first_attribute',
                'font_color',
                'font_family',
                'font_size',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'rotate',
                'text',
                'text_after',
                'text_before',
                'top_padding',
            ),
        );

        $templates_hide['image'][1000] = array_merge( $templates_hide['image'][1000], 
            array(
                'image_height',
                'image_width',
            )
        );

        $templates_hide['advanced'] = array(
            1 => array(
                'border_color',
                'bottom_padding',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'left_padding',
                'right_padding',
                'rotate',
                'top_padding',
                'text_after_nl',
                'text_before',
            ),
            2 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'rotate',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
            3 => array(
                'border_color',
                'bottom_padding',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'left_padding',
                'right_padding',
                'top_padding',
            ),
            4 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
            5 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
            6 => array(
                'border_color',
                'border_radius',
                'content_type',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_before',
                'text_after_nl',
                'text_before_nl',
            ),
            7 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image_height',
                'image_width',
                'img_title',
                'bottom_padding',
                'left_padding',
                'line_height',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before',
                'text_before_nl',
            ),
            8 => array(
                'border_color',
                'border_radius',
                'border_width',
                'image',
                'image_height',
                'image_width',
                'img_title',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before',
                'text_before_nl',
            ),
            9 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
            10 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before',
                'text_before_nl',
            ),
            11 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'rotate',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
            12 => array(
                'border_color',
                'border_radius',
                'border_width',
                'content_type',
                'image_height',
                'image_width',
                'img_title',
                'line_height',
                'bottom_padding',
                'left_padding',
                'right_padding',
                'top_padding',
                'text_after_nl',
                'text_before_nl',
            ),
        );

        return $templates_hide;
    }

    public function paid_templates_rotate( $templates_rotate = array() ) {
        $templates_rotate += array( 
            'css-28' => array(
                'elements' => array(
                    'i1_custom_css' => array(
                        'left: auto;',
                        'right: 100%;',
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'css-18' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'css-11' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: rotate(45deg) scaleX(-1);',
                        '-moz-transform: rotate(45deg) scaleX(-1);',
                        '-ms-transform: rotate(45deg) scaleX(-1);',
                        '-o-transform: rotate(45deg) scaleX(-1);',
                        '-webkit-transform: rotate(45deg) scaleX(-1);',
                    ),
                ),
            ),
            'css-12' => array(
                'styles' => array(
                    'rotate' => '-90deg',
                ),
            ),
            'css-13' => array(
                'styles' => array(
                    'rotate' => '-90deg',
                ),
            ),
            'css-14' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'padding-left: 0;',
                        'padding-right: 10%;',
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'css-15' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'image-12' => array(
                'styles' => array(
                    'padding_horizontal' => '-12',
                    'padding_top'        => '0',
                    'rotate'             => '-90deg',
                ),
            ),
            'image-13' => array(
                'styles' => array(
                    'padding_horizontal' => '-12',
                    'padding_top'        => '0',
                    'rotate'             => '-90deg',
                ),
            ),
            'image-14' => array(
                'styles' => array(
                    'padding_horizontal' => '-9',
                    'padding_top'        => '-1',
                    'rotate'             => '-90deg',
                ),
            ),
            'advanced-1' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'advanced-2' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'advanced-3' => array(
                'elements' => array(
                    'i2_custom_css' => array(
                        'transform: rotate(-90deg);',
                        '-moz-transform: rotate(-90deg);',
                        '-ms-transform: rotate(-90deg);',
                        '-o-transform: rotate(-90deg);',
                        '-webkit-transform: rotate(-90deg);',
                    ),
                ),
            ),
            'advanced-7' => array(
                'elements' => array(
                    'i1_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'i3_custom_css' => array(
                        'left: auto;',
                        'right: 0;',
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1) rotate(243deg);',
                        '-ms-transform: scaleX(-1) rotate(243deg);',
                        '-o-transform: scaleX(-1) rotate(243deg);',
                        '-webkit-transform: scaleX(-1) rotate(243deg);',
                    ),
                    'i4_custom_css' => array(
                        'left: auto;',
                        'right: 0;',
                        'transform: scaleX(-1) rotate(243deg);',
                        '-moz-transform: scaleX(-1) rotate(243deg);',
                        '-ms-transform: scaleX(-1) rotate(243deg);',
                        '-o-transform: scaleX(-1) rotate(243deg);',
                        '-webkit-transform: scaleX(-1) rotate(243deg);',
                    ),
                ),
            ),
            'advanced-8' => array(
                'elements' => array(
                    'i2_custom_css' => array(
                        'transform: skew(0,-15deg);',
                        '-moz-transform: skew(0,-15deg);',
                        '-ms-transform: skew(0,-15deg);',
                        '-o-transform: skew(0,-15deg);',
                        '-webkit-transform: skew(0,-15deg);',
                    ),
                ),
            ),
            'advanced-10' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
            'advanced-11' => array(
                'elements' => array(
                    'span_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                    'b_custom_css' => array(
                        'transform: scaleX(-1);',
                        '-moz-transform: scaleX(-1);',
                        '-ms-transform: scaleX(-1);',
                        '-o-transform: scaleX(-1);',
                        '-webkit-transform: scaleX(-1);',
                    ),
                ),
            ),
        );

        return $templates_rotate;
    }

    public function product_title( $title, $id = false ) {
        if ( is_admin() || is_product() || $id === false ) {
            return $title;
        }

        global $product;
        if( ! empty($product) && is_a($product, 'wc_product') && $product->get_id() == $id ) {
            $BeRocket_products_label = BeRocket_products_label::getInstance();
            $labels_array = $BeRocket_products_label->get_product_labels_ids( array(), $product );
            $in_title_labels = array();
            foreach ( $labels_array as $label ) {
                $br_label = $BeRocket_products_label->custom_post->get_option($label);
                if ( $br_label['type'] != 'in_title' ) continue;
                $br_label = apply_filters( 'berocket_label_adjust_options', $br_label );
                $in_title_labels[$br_label['position']][] = $BeRocket_products_label->show_label_on_product($br_label, $product, $label, 'return');
            }
            $left_labels = empty( $in_title_labels['left'] ) ? '' : implode( $in_title_labels['left'] );
            $right_labels = empty( $in_title_labels['right'] ) ? '' : implode( $in_title_labels['right'] );
            $title = $left_labels.$title.$right_labels;
        }
        return $title;
    }
    // public function do_not_display_label_in_title($dispaly_label, $br_label, $product) {
    //     if( ! empty($br_label['type']) && $br_label['type'] == 'in_title' ) {
    //         return false;
    //     }
    //     return $dispaly_label;
    // }

    public static function paid_templates( $templates = array() ) {
        $templates['css'] += array(
            6 => array(
                'border_radius' => '0',
                'image_height'  => '60',
                'image_width'   => '60',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(25% 0%, 75% 0%, 100% 35%, 50% 100%, 0 35%)',
                    '-webkit-clip-path' => 'polygon(25% 0%, 75% 0%, 100% 35%, 50% 100%, 0 35%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
                'b_custom_css' => array(
                    'margin-top' => '-30%',
                ),
            ),
            7 => array(
                'border_radius' => '0',
                'image_height'  => '60',
                'image_width'   => '60',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array(
                    '-webkit-clip-path' => 'polygon(13% 13%, 30% 13%, 37% 0%, 50% 8%, 62% 0%, 70% 13%, 87% 13%, 87% 30%, 100% 37%, 92% 50%, 100% 62%, 87% 70%, 87% 87%, 70% 87%, 62% 100%, 50% 92%, 37% 100%, 30% 87%, 13% 87%, 13% 70%, 0% 62%, 8% 50%, 0% 37%, 13% 30%)',
                    'clip-path'         => 'polygon(13% 13%, 30% 13%, 37% 0%, 50% 8%, 62% 0%, 70% 13%, 87% 13%, 87% 30%, 100% 37%, 92% 50%, 100% 62%, 87% 70%, 87% 87%, 70% 87%, 62% 100%, 50% 92%, 37% 100%, 30% 87%, 13% 87%, 13% 70%, 0% 62%, 8% 50%, 0% 37%, 13% 30%)',
                    'height' => '100%',
                    'width'  => '100%',
                ),
            ),
            24 => array(
                'font_size'          => '16',
                'image_height'       => '70',
                'image_width'        => '70',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array(
                    'clip-path'         => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    '-webkit-clip-path' => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    'height'            => '70px',
                    'opacity'           => '0.7',
                    'transform'         => 'rotate(40deg)',
                    '-moz-transform'    => 'rotate(40deg)',
                    '-ms-transform'     => 'rotate(40deg)',
                    '-o-transform'      => 'rotate(40deg)',
                    '-webkit-transform' => 'rotate(40deg)',
                    'width'             => '70px',
                ),
                'i2_custom_css' => array(
                    '-webkit-clip-path' => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    'clip-path'         => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    'height'            => '70px',
                    'opacity'           => '0.7',
                    'transform'         => 'rotate(60deg)',
                    '-moz-transform'    => 'rotate(60deg)',
                    '-ms-transform'     => 'rotate(60deg)',
                    '-o-transform'      => 'rotate(60deg)',
                    '-webkit-transform' => 'rotate(60deg)',
                    'width'             => '70px',
                ),
                'i3_custom_css' => array(
                    '-webkit-clip-path' => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    'clip-path'         => 'polygon(32.67949% 15%, 37.14425% 9.67911%, 43.1596% 6.20615%, 50% 5%, 56.8404% 6.20615%, 62.85575% 9.67911%, 67.32051% 15%, 88.97114% 52.5%, 91.34679% 59.02704%, 91.34679% 65.97296%, 88.97114% 72.5%, 84.50639% 77.82089%, 78.49104% 81.29385%, 71.65064% 82.5%, 28.34936% 82.5%, 21.50896% 81.29385%, 15.49361% 77.82089%, 11.02886% 72.5%, 8.65321% 65.97296%, 8.65321% 59.02704%, 11.02886% 52.5%)',
                    'height'  => '70px',
                    'opacity' => '0.7',
                    'width'   => '70px',
                ),
            ),
            25 => array(
                'before_text'         => 'UPTO',
                'border_color'        => '#FFFFFF',
                'border_radius'       => '50',
                'border_radius_units' => '%',
                'border_width'        => '4',
                'content_type'        => 'custom',
                'image_height'        => '75',
                'image_width'         => '75',
                'left_margin'         => '10',
                'right_margin'        => '10',
                'padding_horizontal'  => '10',
                'padding_top'         => '5',
                'shadow_use'          => '1',
                'top_margin'          => '5',
                'text'                => '%sale_p% OFF',
                'text_before'         => 'UPTO',
                'text_before_nl'      => '1',
                'i1_custom_css' => array(
                    'background'          => 'transparent !important',
                    'border-style'        => 'solid',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-width' => '0',
                    'border-left-color'   => 'transparent !important',
                    'border-left-width'   => '6px',
                    'border-right-color'  => 'transparent !important',
                    'border-right-width'  => '6px',
                    'border-top-width'    => '18px',
                    'bottom'              => '-17px',
                    'display'             => 'block',
                    'left'                => '50%',
                    'transform'           => 'translateX(-50%)',
                    '-moz-transform'      => 'translateX(-50%)',
                    '-ms-transform'       => 'translateX(-50%)',
                    '-o-transform'        => 'translateX(-50%)',
                    '-webkit-transform'   => 'translateX(-50%)',
                    'width'               => '0',
                    'z-index'             => '99',
                ),
                'i2_custom_css' => array(
                    'background'          => 'transparent !important',
                    'border-style'        => 'solid',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-width' => '0',
                    'border-left-color'   => 'transparent !important',
                    'border-left-width'   => '10px',
                    'border-right-color'  => 'transparent !important',
                    'border-right-width'  => '10px',
                    'border-top-color'    => '#FFFFFF !important',
                    'border-top-width'    => '22px',
                    'display'             => 'block',
                    'width'               => '0',
                    'z-index'             => '9',
                    'bottom'              => '-22px',
                    'left'                => '50%',
                    'transform'           => 'translateX(-50%)',
                    '-moz-transform'      => 'translateX(-50%)',
                    '-ms-transform'       => 'translateX(-50%)',
                    '-o-transform'        => 'translateX(-50%)',
                    '-webkit-transform'   => 'translateX(-50%)',
                ),
                'i3_custom_css' => array(
                    'background'          => 'transparent !important',
                    'border-style'        => 'solid',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-width' => '0',
                    'border-left-color'   => 'transparent !important',
                    'border-left-width'   => '10px',
                    'border-right-color'  => 'transparent !important',
                    'border-right-width'  => '10px',
                    'border-top-color'    => 'rgba(119, 119, 119, 0.5) !important',
                    'border-top-width'    => '20px',
                    'bottom'              => '-22px',
                    'display'             => 'block',
                    'left'                => '26px',
                    'width'               => '0',
                    'z-index'             => '0',
                ),
            ),
            26 => array(
                'border_radius'       => '50',
                'border_radius_units' => '%',
                'image_height'        => '70',
                'image_width'         => '70',
                'left_margin'         => '15',
                'right_margin'        => '15',
                'padding_horizontal'  => '15',
                'padding_top'         => '15',
                'top_margin'          => '15',
                'span_custom_css' => array(
                    'border-width' => '0.7em',
                ),
                'i4_custom_css' => array(
                    'background'  => 'transparent !important',
                    'border-radius' => '50%',
                    'border-style' => 'solid',
                    'border-width' => '0.7em',
                    'box-sizing' => 'content-box',
                    'width' => '106%',
                    'height' => '106%',
                    'left' => '50%',
                    'top' => '50%',
                    'transform' => 'translateX(-50%) translateY(-50%)',
                    '-moz-transform' => 'translateX(-50%) translateY(-50%)',
                    '-ms-transform' => 'translateX(-50%) translateY(-50%)',
                    '-webkit-transform' => 'translateX(-50%) translateY(-50%)',
                    '-o-transform' => 'translateX(-50%) translateY(-50%)',
                ),
            ),
            8 => array(
                'border_radius' => '0',
                'image_height'  => '60',
                'image_width'   => '52',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(0 0, 100% 0, 100% 100%, 50% 60%, 0 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 100% 100%, 50% 60%, 0 100%)',
                    'width'             => '100%',
                    'height'            => '100%',
                ),
                'b_custom_css' => array(
                    'margin-top' => '-42%',
                ),
            ),
            28 => array(
                'content_type'       => 'sale_p',
                'image_height'       => '70',
                'image_width'        => '55',
                'left_margin'        => '20',
                'right_margin'       => '20',
                'top_margin'         => '-10',
                'padding_horizontal' => '20',
                'position'           => 'left',
                'text_before'        => '-',
                'text_after'         => 'OFF',
                'text_after_nl'      => 1,
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array(
                    'border-style'        => 'solid',
                    'background-color'    => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-width' => '0',
                    'border-right-color'  => 'transparent !important',
                    'border-right-width'  => '0',
                    'border-left-width'   => '10px',
                    'border-top-color'    => 'transparent!important',
                    'border-top-width'    => '10px',
                    'filter'              => 'brightness(80%)',
                    '-webkit-filter'      => 'brightness(80%)',
                    'height'              => '0',
                    'right'               => '-10px',
                    'top'                 => '0',
                    'width'               => '0',
                    ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(0 0, 100% 0, 99% 100%, 50% 68%, 0 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 99% 100%, 50% 68%, 0 100%)',
                    'width'             => '100%',
                    'height'            => '100%',
                ),
                'b_custom_css' => array(
                    'margin-top' => '-40%',
                ),
            ),
            29 => array(
                'border_radius'      => '5',
                'content_type'       => 'custom',
                'image_height'       => '50',
                'image_width'        => '60',
                'left_margin'        => '10',
                'right_margin'       => '10',
                'top_margin'         => '10',
                'padding_horizontal' => '10',
                'padding_top'        => '10',
                'position'           => 'left',
                'text'               => 'SALE',
                'text_before'        => 'FLASH',
                'text_before_nl'     => 1,
                'span_custom_css' => array(
                ),
                'i4_custom_css' => array(
                    'bottom'            => '-20px',
                    'clip-path'         => 'polygon(0% 0%, 100% 0, 100% 70%, 50% 85%, 0 70%)',
                    '-webkit-clip-path' => 'polygon(0% 0%, 100% 0, 100% 70%, 50% 85%, 0 70%)',
                    'width'             => '100%',
                    'height'            => '66px',
                ),
            ),
            17 => array(
                'color' => '#661d0b',
                'image_height'       => '140',
                'image_width'        => '40',
                'right_margin'       => '10',
                'left_margin'        => '10',
                'top_margin'         => '0',
                'padding_horizontal' => '10',
                'padding_top'        => '0',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array(
                    'background-image' => 'none !important',
                    'bottom' => '22%',
                    'clip-path'         => 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
                    '-webkit-clip-path' => 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
                    'filter'            => 'brightness(350%)',
                    '-webkit-filter'    => 'brightness(350%)',
                    'height'            => '16%',
                    'left'              => '50%',
                    'transform'         => 'translateX(-50%)',
                    '-moz-transform'    => 'translateX(-50%)',
                    '-ms-transform'     => 'translateX(-50%)',
                    '-o-transform'      => 'translateX(-50%)',
                    '-webkit-transform' => 'translateX(-50%)',
                    'width'             => '50%',
                    'z-index'           => '99',
                ),
                'i3_custom_css' => array(
                    'background-image' => 'none !important',
                    'clip-path'         => 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
                    '-webkit-clip-path' => 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
                    'filter'            => 'brightness(350%)',
                    '-webkit-filter'    => 'brightness(350%)',
                    'height'            => '16%',
                    'left'              => '50%',
                    'transform'         => 'translateX(-50%)',
                    '-moz-transform'    => 'translateX(-50%)',
                    '-ms-transform'     => 'translateX(-50%)',
                    '-o-transform'      => 'translateX(-50%)',
                    '-webkit-transform' => 'translateX(-50%)',
                    'top'               => '7%',
                    'width'             => '50%',
                    'z-index'           => '99',
                ),
                'i4_custom_css' => array( // template-i 
                    'clip-path'         => 'polygon(0 0, 100% 0, 99% 100%, 50% 85%, 0 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 99% 100%, 50% 85%, 0 100%)',
                    'width'  => '100%',
                    'height' => '100%',
                ),
                'b_custom_css' => array(
                    'position'          => 'absolute',
                    'left'              => '49%',
                    'top'               => '43%',
                    'transform'         => 'translateX(-50%) translateY(-50%) rotate(-90deg)',
                    '-moz-transform'    => 'translateX(-50%) translateY(-50%) rotate(-90deg)',
                    '-ms-transform'     => 'translateX(-50%) translateY(-50%) rotate(-90deg)',
                    '-o-transform'      => 'translateX(-50%) translateY(-50%) rotate(-90deg)',
                    '-webkit-transform' => 'translateX(-50%) translateY(-50%) rotate(-90deg)',
                    'width'             => '60px',
                ),
            ),
            18 => array(
                'image_height'       => '40',
                'image_width'        => '160',
                'bottom_margin'      => '16',
                'left_margin'        => '10',
                'right_margin'       => '10',
                'top_margin'         => '10',
                'padding_horizontal' => '10',
                'padding_top'        => '10',
                'position'           => 'left',
                'text'               => 'SUMMER SALE',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array( // template-i 
                    'height'            => '40px',
                    'filter'            => 'brightness(30%)',
                    '-webkit-filter'    => 'brightness(30%)',
                    'right'             => '0',
                    'top'               => '5px',
                    'transform'         => 'rotate(90deg) skew(15deg)',
                    '-moz-transform'    => 'rotate(90deg) skew(15deg)',
                    '-ms-transform'     => 'rotate(90deg) skew(15deg)',
                    '-o-transform'      => 'rotate(90deg) skew(15deg)',
                    '-webkit-transform' => 'rotate(90deg) skew(15deg)',
                    'width'             => '40px',
                ),
                'i3_custom_css' => array(
                    'clip-path'         => 'polygon(0 0, 0 88%, 55% 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 0 88%, 55% 100%)',
                    'height'            => '59px',
                    'right'             => '0',
                    'top'               => '-2px',
                    'width'             => '40px',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 5% 50%, 0% 0%)',
                    '-webkit-clip-path' => 'polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 5% 50%, 0% 0%)',
                    'height'            => '100%',
                    'left'              => '0',
                    'top'               => '0',
                    'width'             => '100%',
                    'z-index'           => '100',
                ),
            ),
            9 => array(
                'image_height'      => '50',
                'image_width'       => '50',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array(
                    'clip-path'         => 'polygon(0 100%, 50% 0, 100% 100%, 50% 78%)',
                    '-webkit-clip-path' => 'polygon(0 100%, 50% 0, 100% 100%, 50% 78%)',
                    'height'            => '70px',
                    'left'              => '3px',
                    'top'               => '0',
                    'width'             => '46px',
                ),
                'i4_custom_css' => array(
                    'border-radius' => '50%',
                    'height'        => '100%',
                    'width'         => '100%',
                    'z-index'       => '100',
                ),
            ),
            10 => array(
                'border_radius' => '0',
                'image_height'  => '50',
                'image_width'   => '50',
                'rotate'        => '45deg',
                'span_custom_css' => array(
                    'box-sizing' => 'content-box'
                ),
                'b_custom_css' => array(
                    'transform' => 'rotate(-45deg)',
                    '-moz-transform' => 'rotate(-45deg)',
                    '-ms-transform' => 'rotate(-45deg)',
                    '-webkit-transform' => 'rotate(-45deg)',
                    '-o-transform' => 'rotate(-45deg)',
                ),
            ),
            21 => array(
                'content_type'       => 'custom',
                'image_height'       => '60',
                'image_width'        => '60',
                'left_margin'        => '5   ',
                'right_margin'       => '5',
                'top_margin'         => '10',
                'padding_horizontal' => '5',
                'padding_top'        => '10',
                'line_height'        => '1.4',
                'line_height_units'  => 'em',
                'font_size'          => '20',
                'text'               => '%sale_p%',
                'span_custom_css' => array(
                    'background' => 'none !important',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(89.14214% 35.85786%, 92.32051% 40%, 94.31852% 44.82362%, 95% 50%, 94.31852% 55.17638%, 92.32051% 60%, 89.14214% 64.14214%, 64.14214% 89.14214%, 60% 92.32051%, 55.17638% 94.31852%, 50% 95%, 44.82362% 94.31852%, 40% 92.32051%, 35.85786% 89.14214%, 10.85786% 64.14214%, 7.67949% 60%, 5.68148% 55.17638%, 5% 50%, 5.68148% 44.82362%, 7.67949% 40%, 10.85786% 35.85786%, 35.85786% 10.85786%, 40% 7.67949%, 44.82362% 5.68148%, 50% 5%, 55.17638% 5.68148%, 60% 7.67949%, 64.14214% 10.85786%)',
                    '-webkit-clip-path' => 'polygon(89.14214% 35.85786%, 92.32051% 40%, 94.31852% 44.82362%, 95% 50%, 94.31852% 55.17638%, 92.32051% 60%, 89.14214% 64.14214%, 64.14214% 89.14214%, 60% 92.32051%, 55.17638% 94.31852%, 50% 95%, 44.82362% 94.31852%, 40% 92.32051%, 35.85786% 89.14214%, 10.85786% 64.14214%, 7.67949% 60%, 5.68148% 55.17638%, 5% 50%, 5.68148% 44.82362%, 7.67949% 40%, 10.85786% 35.85786%, 35.85786% 10.85786%, 40% 7.67949%, 44.82362% 5.68148%, 50% 5%, 55.17638% 5.68148%, 60% 7.67949%, 64.14214% 10.85786%)',
                    'height'   => '100%',
                    'overflow' => 'hidden',
                    'width'    => '100%',
                ),
                'i1_custom_class' => 'br-labels-css-21-i1',
            ),
            22 => array(
                'content_type'       => 'custom',
                'image_height'       => '80',
                'image_width'        => '80',
                'left_margin'        => '5',
                'right_margin'       => '5',
                'top_margin'         => '10',
                'padding_horizontal' => '5',
                'padding_top'        => '10',
                'text'               => 'OFFER',
                'text_before'        => 'WINTER',
                'text_before_nl'     => '1',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array(
                    'background' => '#FFFFFF !important',
                    'clip-path'         => 'polygon(45% 1.33975%, 46.5798% 0.60307%, 48.26352% 0.15192%, 50% 0%, 51.73648% 0.15192%, 53.4202% 0.60307%, 55% 1.33975%, 89.64102% 21.33975%, 91.06889% 22.33956%, 92.30146% 23.57212%, 93.30127% 25%, 94.03794% 26.5798%, 94.48909% 28.26352%, 94.64102% 30%, 94.64102% 70%, 94.48909% 71.73648%, 94.03794% 73.4202%, 93.30127% 75%, 92.30146% 76.42788%, 91.06889% 77.66044%, 89.64102% 78.66025%, 55% 98.66025%, 53.4202% 99.39693%, 51.73648% 99.84808%, 50% 100%, 48.26352% 99.84808%, 46.5798% 99.39693%, 45% 98.66025%, 10.35898% 78.66025%, 8.93111% 77.66044%, 7.69854% 76.42788%, 6.69873% 75%, 5.96206% 73.4202%, 5.51091% 71.73648%, 5.35898% 70%, 5.35898% 30%, 5.51091% 28.26352%, 5.96206% 26.5798%, 6.69873% 25%, 7.69854% 23.57212%, 8.93111% 22.33956%, 10.35898% 21.33975%)',
                    '-webkit-clip-path' => 'polygon(45% 1.33975%, 46.5798% 0.60307%, 48.26352% 0.15192%, 50% 0%, 51.73648% 0.15192%, 53.4202% 0.60307%, 55% 1.33975%, 89.64102% 21.33975%, 91.06889% 22.33956%, 92.30146% 23.57212%, 93.30127% 25%, 94.03794% 26.5798%, 94.48909% 28.26352%, 94.64102% 30%, 94.64102% 70%, 94.48909% 71.73648%, 94.03794% 73.4202%, 93.30127% 75%, 92.30146% 76.42788%, 91.06889% 77.66044%, 89.64102% 78.66025%, 55% 98.66025%, 53.4202% 99.39693%, 51.73648% 99.84808%, 50% 100%, 48.26352% 99.84808%, 46.5798% 99.39693%, 45% 98.66025%, 10.35898% 78.66025%, 8.93111% 77.66044%, 7.69854% 76.42788%, 6.69873% 75%, 5.96206% 73.4202%, 5.51091% 71.73648%, 5.35898% 70%, 5.35898% 30%, 5.51091% 28.26352%, 5.96206% 26.5798%, 6.69873% 25%, 7.69854% 23.57212%, 8.93111% 22.33956%, 10.35898% 21.33975%)',
                    'height' => '88px',
                    'left'   => '-4px', 
                    'top'    => '-4px', 
                    'width'  => '88px', 
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(45% 1.33975%, 46.5798% 0.60307%, 48.26352% 0.15192%, 50% 0%, 51.73648% 0.15192%, 53.4202% 0.60307%, 55% 1.33975%, 89.64102% 21.33975%, 91.06889% 22.33956%, 92.30146% 23.57212%, 93.30127% 25%, 94.03794% 26.5798%, 94.48909% 28.26352%, 94.64102% 30%, 94.64102% 70%, 94.48909% 71.73648%, 94.03794% 73.4202%, 93.30127% 75%, 92.30146% 76.42788%, 91.06889% 77.66044%, 89.64102% 78.66025%, 55% 98.66025%, 53.4202% 99.39693%, 51.73648% 99.84808%, 50% 100%, 48.26352% 99.84808%, 46.5798% 99.39693%, 45% 98.66025%, 10.35898% 78.66025%, 8.93111% 77.66044%, 7.69854% 76.42788%, 6.69873% 75%, 5.96206% 73.4202%, 5.51091% 71.73648%, 5.35898% 70%, 5.35898% 30%, 5.51091% 28.26352%, 5.96206% 26.5798%, 6.69873% 25%, 7.69854% 23.57212%, 8.93111% 22.33956%, 10.35898% 21.33975%)',
                    '-webkit-clip-path' => 'polygon(45% 1.33975%, 46.5798% 0.60307%, 48.26352% 0.15192%, 50% 0%, 51.73648% 0.15192%, 53.4202% 0.60307%, 55% 1.33975%, 89.64102% 21.33975%, 91.06889% 22.33956%, 92.30146% 23.57212%, 93.30127% 25%, 94.03794% 26.5798%, 94.48909% 28.26352%, 94.64102% 30%, 94.64102% 70%, 94.48909% 71.73648%, 94.03794% 73.4202%, 93.30127% 75%, 92.30146% 76.42788%, 91.06889% 77.66044%, 89.64102% 78.66025%, 55% 98.66025%, 53.4202% 99.39693%, 51.73648% 99.84808%, 50% 100%, 48.26352% 99.84808%, 46.5798% 99.39693%, 45% 98.66025%, 10.35898% 78.66025%, 8.93111% 77.66044%, 7.69854% 76.42788%, 6.69873% 75%, 5.96206% 73.4202%, 5.51091% 71.73648%, 5.35898% 70%, 5.35898% 30%, 5.51091% 28.26352%, 5.96206% 26.5798%, 6.69873% 25%, 7.69854% 23.57212%, 8.93111% 22.33956%, 10.35898% 21.33975%)',
                    'height'  => '100%', 
                    'width'   => '100%', 
                    'z-index' => '100',
                ),
            ),
            11 => array(
                'border_radius'      => '0',
                'image_height'       => '88',
                'image_width'        => '88',
                'right_margin'       => '0',
                'bottom_margin'      => '0',
                'top_margin'         => '0',
                'left_margin'        => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'span_custom_css' => array(
                    'background-color' => 'transparent !important',
                    'overflow'         => 'hidden',
                ),
                'i1_custom_css' => array(
                    'bottom'            => '38px',
                    'height'            => '100px',
                    'left'              => '38px',
                    'transform'         => 'rotate(45deg)',
                    '-moz-transform'    => 'rotate(45deg)',
                    '-ms-transform'     => 'rotate(45deg)',
                    '-webkit-transform' => 'rotate(45deg)',
                    '-o-transform'      => 'rotate(45deg)',
                    'width'             => '100px',
                ),
                'b_custom_css' => array(
                    'background-color'  => 'transparent !important',
                    'display'           => 'block',
                    'left'              => '12px',
                    'height'            => '22px',
                    'position'          => 'absolute',
                    'text-align'        => 'center',
                    'top'               => '12px',
                    'transform'         => 'rotate(45deg)',
                    '-moz-transform'    => 'rotate(45deg)',
                    '-ms-transform'     => 'rotate(45deg)',
                    '-webkit-transform' => 'rotate(45deg)',
                    '-o-transform'      => 'rotate(45deg)',
                    'width'             => '105px',
                    'z-index'           => '14',
                ),
            ),
            12 => array(
                'image_height'       => '100',
                'image_width'        => '100',
                'left_margin'        => '-4',
                'right_margin'       => '-4',
                'top_margin'         => '-4',
                'bottom_margin'      => '-4',
                'padding_horizontal' => '-4',
                'padding_top'        => '-4',
                'line_height'        => '22',
                'line_height_units'  => 'px',
                'span_custom_css' => array(
                    'background-color' => 'transparent !important',
                    'overflow'         => 'hidden',
                ),
                'i1_custom_css' => array(
                    'height'            => '22px',
                    'left'              => '12px',
                    'position'          => 'absolute',
                    'top'               => '12px',
                    'text-align'        => 'center',
                    'transform'         => 'rotate(45deg)',
                    '-moz-transform'    => 'rotate(45deg)',
                    '-ms-transform'     => 'rotate(45deg)',
                    '-webkit-transform' => 'rotate(45deg)',
                    '-o-transform'      => 'rotate(45deg)',
                    'width'             => '130px',
                    'z-index'           => '14',
                ),
                'i2_custom_css' => array(
                    'background-color'    => 'transparent !important',
                    'border-bottom-width' => '4px',
                    'border-bottom-style' => 'solid',
                    'border-left-width'   => '4px',
                    'border-left-style'   => 'solid',
                    'border-left-color'   => 'transparent !important',
                    'display'             => 'block',
                    'filter'              => 'brightness(50%)',
                    '-webkit-filter'      => 'brightness(50%)',
                    'left'                => '34px',
                    'position'            => 'absolute',
                    'top'                 => '0',
                    'width'               => '11px',
                    'z-index'             => '12',
                ),
                'i3_custom_css' => array(
                    'background-color' => 'transparent !important',
                    'border-bottom-width' => '4px',
                    'border-bottom-style' => 'solid',
                    'border-bottom-color' => 'transparent !important',
                    'border-left-width'   => '4px',
                    'border-left-style'   => 'solid',
                    'bottom'              => '34px',
                    'display'             => 'block',
                    'filter'              => 'brightness(50%)',
                    '-webkit-filter'      => 'brightness(50%)',
                    'height'              => '11px',
                    'position'            => 'absolute',
                    'right'               => '0px',
                    'z-index'             => '12',
                ),
                'b_custom_css' => array(
                    'background-color'  => 'transparent !important',
                    'display'           => 'block',
                    'left'              => '12px',
                    'height'            => '22px',
                    'position'          => 'absolute',
                    'text-align'        => 'center',
                    'top'               => '12px',
                    'transform'         => 'rotate(45deg)',
                    '-moz-transform'    => 'rotate(45deg)',
                    '-ms-transform'     => 'rotate(45deg)',
                    '-webkit-transform' => 'rotate(45deg)',
                    '-o-transform'      => 'rotate(45deg)',
                    'width'             => '130px',
                    'z-index'           => '14',
                ),
            ),
            13 => array(
                'image_height'       => '100',
                'image_width'        => '100',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '0',
                'bottom_margin'      => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'line_height'        => '40',
                'line_height_units'  => 'px',
                'span_custom_css' => array(
                    'background-color' => 'transparent !important',
                    'overflow'      => 'hidden',
                ),
                'i1_custom_css' => array(
                    'border'              => '0',
                    'box-sizing'          => 'border-box',
                    'height'              => '37px',
                    'position'            => 'absolute',
                    'right'               => '-40px',
                    'top'                 => '14px',
                    'transform'           => 'rotate(45deg)',
                    '-moz-transform'      => 'rotate(45deg)',
                    '-ms-transform'       => 'rotate(45deg)',
                    '-webkit-transform'   => 'rotate(45deg)',
                    '-o-transform'        => 'rotate(45deg)',
                    'width'               => '145px',
                ),
                'b_custom_css' => array(
                    'background-color'  => 'transparent !important',
                    'box-sizing'        => 'border-box',
                    'position'          => 'absolute',
                    'height'            => '38px',
                    'right'             => '-40px',
                    'top'               => '14px',
                    'transform'         => 'rotate(45deg)',
                    '-moz-transform'    => 'rotate(45deg)',
                    '-ms-transform'     => 'rotate(45deg)',
                    '-webkit-transform' => 'rotate(45deg)',
                    '-o-transform'      => 'rotate(45deg)',
                    'width'             => '145px',
                ),
            ),
            14 => array(
                'image_height'       => '36',
                'image_width'        => '134',
                'left_margin'        => '-6',
                'right_margin'       => '-6',
                'top_margin'         => '10',
                'padding_horizontal' => '-6',
                'padding_top'        => '10',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array(
                    'background-color'   => 'transparent !important',
                    'border-right-color' => 'transparent !important',
                    'border-right-style' => 'solid',
                    'border-right-width' => '6px',
                    'border-top-style'   => 'solid',
                    'border-top-width'   => '6px',
                    'bottom'             => '-6px',
                    'filter'             => 'brightness(50%)',
                    '-webkit-filter'     => 'brightness(50%)',
                    'height'             => '0',
                    'position'           => 'absolute',
                    'right'              => '0',
                    'width'              => '0',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(0 0, 100% 0, 100% 100%, 15% 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 100% 100%, 15% 100%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
                'b_custom_css' => array(
                    'padding-left' => '10%',
                ),
            ),
            27 => array(
                'image_height'       => '36',
                'image_width'        => '134',
                'left_margin'        => '10',
                'right_margin'       => '10',
                'top_margin'         => '10',
                'padding_horizontal' => '10',
                'padding_top'        => '10',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array(
                    'clip-path'         => 'polygon(10% 0, 100% 0%, 90% 100%, 0% 100%)',
                    '-webkit-clip-path' => 'polygon(10% 0, 100% 0%, 90% 100%, 0% 100%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
            ),
            15 => array(
                'bottom_margin'      => '5',
                'image_height'       => '45',
                'image_width'        => '45',
                'left_margin'        => '5',
                'right_margin'       => '5',
                'top_margin'         => '5',
                'padding_horizontal' => '5',
                'padding_top'        => '5',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array(
                    'border-bottom-left-radius'  => '0px',
                    'border-bottom-right-radius' => '50%',
                    'border-top-left-radius'     => '50%',
                    'border-top-right-radius'    => '50%',
                    'height'                     => '45px',
                    'line-height'                => '45px',
                    'width'                      => '45px',
                ),
            ),
            16 => array(
                'border_color'      => BeRocket_advanced_labels_custom_post::$base_color,
                'border_width'      => '3',
                'image_width'       => '40',
                'left_margin'       => '10',
                'right_margin'      => '10',
                'bottom_margin'     => '0',
                'top_margin'        => '0',
                'top_margin'        => '0',
                'padding_top'       => '0',
                'padding_horizontal'=> '10',
                'span_custom_css' => array(
                    'background-color' => 'transparent !important',
                    'border-left' => 'none !important',
                    'border-right' => 'none !important',
                    'border-top' => 'none !important',
                ),
            ),
            30 => array(
                'border_color'       => '#777777',
                'border_width'       => '1',
                'image_height'       => '35',
                'image_width'        => '75',
                'left_margin'        => '-5',
                'right_margin'       => '-5',
                'padding_horizontal' => '-5',
                'padding_top'        => '-10',
                'shadow_use'         => '1',
                'shadow_blur'        => '0',
            ),
        );

        $templates['image'] = array(
            1000 => $templates['image'][1000],
            1 => array(
                'image_height'       => '80',
                'image_width'        => '56',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-1.png', __DIR__) . ')',
                ),
            ),
            2 => array(
                'image_height'       => '80',
                'image_width'        => '53',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-2.png', __DIR__) . ')',
                ),
            ),
            3 => array(
                'image_height'       => '80',
                'image_width'        => '57',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '-9',
                'padding_horizontal' => '0',
                'padding_top'        => '-12',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-3.png', __DIR__) . ')',
                ),
            ),
            4 => array(
                'image_height'       => '80',
                'image_width'        => '80',
                'left_margin'        => '-15',
                'right_margin'       => '-15',
                'top_margin'         => '-15',
                'padding_horizontal' => '-15',
                'padding_top'        => '-15',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-4.png', __DIR__) . ')',
                ),
            ),
            5 => array(
                'image_height'       => '80',
                'image_width'        => '78',
                'left_margin'        => '-15',
                'right_margin'       => '-15',
                'top_margin'         => '-15',
                'padding_horizontal' => '-15',
                'padding_top'        => '-15',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-5.png', __DIR__) . ')',
                ),
            ),
            6 => array(
                'image_height'       => '42',
                'image_width'        => '80',
                'left_margin'        => '-23',
                'right_margin'       => '-23',
                'top_margin'         => '-7',
                'padding_horizontal' => '-20',
                'padding_top'        => '-10',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-6.png', __DIR__) . ')',
                ),
            ),
            7 => array(
                'image_height'       => '105',
                'image_width'        => '40',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '-12',
                'padding_horizontal' => '0',
                'padding_top'        => '-10',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-7.png', __DIR__) . ')',
                ),
            ),
            8 => array(
                'image_height'       => '120',
                'image_width'        => '45',
                'left_margin'        => '2',
                'right_margin'       => '2',
                'top_margin'         => '-12',
                'padding_horizontal' => '2',
                'padding_top'        => '-10',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-8.png', __DIR__) . ')',
                ),
            ),
            9 => array(
                'image_height'       => '55',
                'image_width'        => '80',
                'left_margin'        => '-26',
                'right_margin'       => '-26',
                'top_margin'         => '-26',
                'padding_horizontal' => '-25',
                'padding_top'        => '-27',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-9.png', __DIR__) . ')',
                ),
            ),
            10 => array(
                'image_height'       => '50',
                'image_width'        => '120',
                'left_margin'        => '-26',
                'right_margin'       => '-26',
                'top_margin'         => '6',
                'padding_horizontal' => '-25',
                'padding_top'        => '6',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-10.png', __DIR__) . ')',
                ),
            ),
            11 => array(
                'image_height'       => '44',
                'image_width'        => '80',
                'left_margin'        => '-26',
                'right_margin'       => '-26',
                'top_margin'         => '6',
                'padding_horizontal' => '-25',
                'padding_top'        => '6',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-11.png', __DIR__) . ')',
                ),
            ),
            12 => array(
                'image_height'       => '132',
                'image_width'        => '132',
                'right_margin'       => '-4',
                'top_margin'         => '-4',
                'left_margin'        => '-4',
                'bottom_margin'      => '-4',
                'padding_horizontal' => '-6',
                'padding_top'        => '-6',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-12.png', __DIR__) . ')',
                ),
            ),
            13 => array(
                'image_height'       => '132',
                'image_width'        => '132',
                'right_margin'       => '-4',
                'top_margin'         => '-4',
                'left_margin'        => '-4',
                'bottom_margin'      => '-4',
                'padding_horizontal' => '-6',
                'padding_top'        => '-6',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-13.png', __DIR__) . ')',
                ),
            ),
            14 => array(
                'image_height'       => '100',
                'image_width'        => '100',
                'right_margin'       => '-5',
                'top_margin'         => '-5',
                'left_margin'        => '-5',
                'bottom_margin'      => '-5',
                'padding_horizontal' => '-6',
                'padding_top'        => '-5',
                'span_custom_css' => array(
                    'background-image' => 'url(' . plugins_url('images/templates/image-14.png', __DIR__) . ')',
                ),
            ),
        );

        $templates['advanced'] = array(
            1 => array(
                'content_type'       => 'custom',
                'image_height'       => '70',
                'image_width'        => '80',
                'left_margin'        => '-6',
                'right_margin'       => '-6',
                'top_margin'         => '5',
                'padding_horizontal' => '-6',
                'padding_top'        => '5',
                'text'               => '%sale_p%',
                'text_after'         => 'off',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array( //span i.template-i
                    'height'            => '100%',
                    'clip-path'         => 'polygon(17% 67%, 100% 52%, 100% 100%, 21% 83%)',
                    '-webkit-clip-path' => 'polygon(17% 67%, 100% 52%, 100% 100%, 21% 83%)',
                    'filter'            => 'brightness(50%)',
                    '-webkit-filter'    => 'brightness(50%)',
                    'width'             => '100%',
                ),
                'i3_custom_css' => array( //span i.template-i-after
                    'background-color'    => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-style' => 'solid',
                    'border-bottom-width' => '6px',
                    'border-left-style'   => 'solid',
                    'border-left-width'   => '6px',
                    'bottom'              => '-5px',
                    'filter'              => 'brightness(50%)',
                    '-webkit-filter'      => 'brightness(50%)',
                    'height'              => '0px',
                    'right'               => '0',
                    'width'               => '0px',
                    'z-index'             => '12',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'clip-path'         => 'polygon(0 0, 100% 17%, 100% 100%, 21% 83%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 17%, 100% 100%, 21% 83%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
            ),
            2 => array(
                'content_type'       => 'custom',
                'image_height'       => '50',
                'image_width'        => '100',
                'left_margin'        => '-5',
                'right_margin'       => '-6',
                'padding_horizontal' => '-6',
                'padding_top'        => '5',
                'top_margin'         => '5',
                'text'               => '%sale_p%',
                'text_before'        => 'off',
                'text_after'         => 'Save %sale_val%',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array( //span i.template-i
                    'background-image'  => 'none !important',
                    'clip-path'         => 'polygon(100% 70%, 100% 100%, 16% 100%, 6% 70%)',
                    '-webkit-clip-path' => 'polygon(100% 70%, 100% 100%, 16% 100%, 6% 70%)',
                    'filter'            => 'brightness(50%)',
                    '-webkit-filter'    => 'brightness(50%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
                'i2_custom_css' => array( //span i.template-span-before
                    'background-color'    => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-style' => 'solid',
                    'border-bottom-width' => '6px',
                    'border-left-style'   => 'solid',
                    'border-left-width'   => '6px',
                    'top'                 => '100%',
                    'filter'              => 'brightness(50%)',
                    '-webkit-filter'      => 'brightness(50%)',
                    'right'               => '0',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'clip-path'         => 'polygon(100% 0%, 100% 100%, 16% 99%, 0% 50%, 16% 0)',
                    '-webkit-clip-path' => 'polygon(100% 0%, 100% 100%, 16% 99%, 0% 50%, 16% 0)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
            ),
            3 => array(
                'content_type'       => 'custom',
                'image_height'       => '70',
                'image_width'        => '70',
                'left_margin'        => '-20',
                'right_margin'       => '-20',
                'padding_horizontal' => '-20',
                'padding_top'        => '-20',
                'text'               => '%sale_p%',
                'text_after'         => 'off',
                'text_after_nl'      => '1',
                'text_before'        => 'SALE',
                'text_before_nl'     => '1',
                'top_margin'         => '-20',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array( //span i.template-i
                    'background-image'  => 'none !important',
                    'clip-path'         => 'polygon(50% 0%, 0% 100%, 100% 40%)',
                    '-webkit-clip-path' => 'polygon(50% 0%, 0% 100%, 100% 40%)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'border-radius' => '50%',
                    'height'        => '100%',
                    'width'         => '100%',
                    'z-index'       => '100',
                ),
            ),
            4 => array(
                'color'              => '#661d0b',
                'content_type'       => 'custom',
                'image_height'       => '75',
                'image_width'        => '66',
                'bottom_margin'      => '0',
                'left_margin'        => '5',
                'right_margin'       => '5',
                'top_margin'         => '0',
                'padding_horizontal' => '5',
                'padding_top'        => '0',
                'text'               => '%sale_p%',
                'text_after'         => 'save %sale_val%',
                'text_before'        => 'discount',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'clip-path'         => 'polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 100% 100%, 50% 85%, 0 100%)',
                    'height'            => '100%',
                    'left'              => '8px',
                    'width'             => '50px',
                ),
                'i1_custom_css' => array( //span i.template-i
                    'clip-path'         => 'polygon(100% 0%, 90% 50%, 100% 100%, 0 100%, 10% 50%, 0 0)',
                    '-webkit-clip-path' => 'polygon(100% 0%, 90% 50%, 100% 100%, 0 100%, 10% 50%, 0 0)',
                    'filter'            => 'brightness(200%)',
                    '-webkit-filter'    => 'brightness(200%)',
                    'height'            => '20px',
                    'bottom'            => '39%',
                    'width'             => '100%',
                ),
                'b_custom_css'   => array(
                    'left'              => '50%',
                    'transform'         => 'translateX(-50%)',
                    '-moz-transform'    => 'translateX(-50%)',
                    '-ms-transform'     => 'translateX(-50%)',
                    '-o-transform'      => 'translateX(-50%)',
                    '-webkit-transform' => 'translateX(-50%)',
                    'width'             => '74%',
                ),
            ),
            5 => array(
                'bottom_margin'      => '0',
                'content_type'       => 'custom',
                'image_height'       => '71',
                'image_width'        => '66',
                'left_margin'        => '5',
                'right_margin'       => '5',
                'padding_horizontal' => '5',
                'padding_top'        => '0',
                'text'               => '%sale_p%',
                'text_after'         => 'save %sale_val%',
                'text_before'        => 'discount',
                'top_margin'         => '0',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array( //span i.template-i
                    'clip-path'         => 'polygon(100% 0%, 90% 50%, 100% 100%, 0 100%, 10% 50%, 0 0)',
                    '-webkit-clip-path' => 'polygon(100% 0%, 90% 50%, 100% 100%, 0 100%, 10% 50%, 0 0)',
                    'filter'            => 'brightness(50%)',
                    '-webkit-filter'    => 'brightness(50%)',
                    'height'            => '20px',
                    'bottom'            => '30%',
                    'width'             => '100%',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'clip-path'         => 'polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%)',
                    'height'            => '100%',
                    'left'              => '8px',
                    'width'             => '50px',
                ),
                'b_custom_css'   => array(
                    'left'              => '50%',
                    'transform'         => 'translateX(-50%)',
                    '-moz-transform'    => 'translateX(-50%)',
                    '-ms-transform'     => 'translateX(-50%)',
                    '-o-transform'      => 'translateX(-50%)',
                    '-webkit-transform' => 'translateX(-50%)',
                    'width'             => '74%',
                ),
            ),
            6 => array(
                'bottom_margin'      => '0',
                'content_type'       => 'custom',
                'image_height'       => '60',
                'image_width'        => '65',
                'left_margin'        => '10',
                'right_margin'       => '10',
                'top_margin'         => '0',
                'padding_horizontal' => '10',
                'padding_top'        => '0',
                'text'               => '%sale_p%',
                'text_after'         => 'on sale',
                'text_before'        => '',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array( //span i.template-i
                    'border-style'        => 'solid',
                    'border-bottom-width' => '16px',
                    'border-left-width'   => '26px',
                    'border-right-width'  => '25px',
                    'bottom'              => '11%',
                    'height'              => '0',
                    'left'                => '7px',
                    'width'               => '0',
                    'z-index'             => '100',
                ),
                'i2_custom_css' => array( //span i.template-i-after
                    'background'         => 'transparent !important',
                    'border-left-color'  => 'transparent !important',
                    'border-left-style'  => 'solid',
                    'border-left-width'  => '6px',
                    'border-right-color' => 'transparent !important',
                    'border-right-style' => 'solid',
                    'border-right-width' => '6px',
                    'border-top-style'   => 'solid',
                    'border-top-width'   => '10px',
                    'bottom'             => '11px',
                    'filter'             => 'brightness(80%)',
                    '-webkit-filter'     => 'brightness(80%)',
                    'left'               => '-3px',
                    'right'              => '-3px',
                    'width'              => 'auto',
                ),
                'i3_custom_css' => array( //span i.template-span-before
                    'background'          => 'transparent !important',
                    'border-bottom-style' => 'solid',
                    'border-bottom-width' => '10px',
                    'border-left-color'   => 'transparent !important',
                    'border-left-style'   => 'solid',
                    'border-left-width'   => '6px',
                    'border-right-color'  => 'transparent !important',
                    'border-right-style'  => 'solid',
                    'border-right-width'  => '6px',
                    'bottom'              => '3px',
                    'filter'              => 'brightness(80%)',
                    '-webkit-filter'      => 'brightness(80%)',
                    'left'                => '-3px',
                    'right'               => '-3px',
                    'width'               => 'auto',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'background-color' => '#777 !important',
                    'height'           => '60px',
                    'left'             => '7px',
                    'right'            => '7px',
                    'top'              => '0',
                    'width'            => 'auto',
                    'z-index'          => '99',
                ),
            ),
            7 => array(
                'content_type'       => 'custom',
                'image_height'       => '65',
                'image_width'        => '65',
                'bottom_margin'      => '0',
                'right_margin'       => '5',
                'left_margin'        => '5',
                'top_margin'         => '5',
                'padding_horizontal' => '5',
                'padding_top'        => '5',
                'text_before'        => '',
                'text'               => '%sale_p%',
                'text_after'         => 'save<br>%sale_val%',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),

                'i1_custom_css' => array( //span i.template-span-before
                    'border-radius' => '20%',
                    'height'        => '65px',
                    'left'          => '0',
                    'overflow'      => 'hidden',
                    'right'         => '0',
                    'top'           => '0',
                    'width'         => 'auto',
                    'z-index'       => '99',
                ),
                'i3_custom_class' => 'br-labels-advanced-7-i3',
                'i3_custom_css' => array( //span i.template-i-before
                    'background-color'    => 'transparent !important',
                    'border-style'        => 'solid',
                    'border-top-color'    => 'transparent !important',
                    'border-right-color'  => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-top-width'    => '33px',
                    'border-left-width'   => '9px',
                    'border-left-color'   => '#FFF !important',
                    'bottom'              => '-21px',
                    'left'                => '11px',
                    'opacity'             => '0.3',
                    'transform'           => 'rotate(243deg)',
                    '-moz-transform'      => 'rotate(243deg)',
                    '-ms-transform'       => 'rotate(243deg)',
                    '-o-transform'        => 'rotate(243deg)',
                    '-webkit-transform'   => 'rotate(243deg)',
                ),
                'i4_custom_css' => array( //span i.template-i-after
                    'background-color'    => 'transparent !important',
                    'border-style'        => 'solid',
                    'border-top-color'    => 'transparent !important',
                    'border-right-color'  => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-top-width'    => '33px',
                    'border-left-width'   => '9px',
                    'bottom'              => '-21px',
                    'left'                => '11px',
                    'transform'           => 'rotate(243deg)',
                    '-moz-transform'      => 'rotate(243deg)',
                    '-ms-transform'       => 'rotate(243deg)',
                    '-o-transform'        => 'rotate(243deg)',
                    '-webkit-transform'   => 'rotate(243deg)',
                ),
                'b_custom_css'    => array(
                    'line-height' => '0.5em;',
                ),
            ),
            8 => array(
                'content_type'       => 'custom',
                'bottom_margin'      => '0',
                'image_height'       => '95',
                'image_width'        => '75',
                'left_margin'        => '5',
                'right_margin'       => '5',
                'top_margin'         => '5',
                'padding_horizontal' => '5',
                'padding_top'        => '5',
                'text'               => '%sale_p%',
                'text_after'         => 'save<br>%sale_val%',
                'text_before'        => '',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                    'overflow'   => 'hidden'
                ),
                'i2_custom_css' => array( //span i.template-i
                    'border-radius'     => '10%',
                    'bottom'            => '0',
                    'height'            => '100px',
                    'left'              => '0',
                    'right'             => '0',
                    'top'               => '-16px',
                    'transform'         => 'skew(0,15deg)',
                    '-moz-transform'    => 'skew(0,15deg)',
                    '-ms-transform'     => 'skew(0,15deg)',
                    '-o-transform'      => 'skew(0,15deg)',
                    '-webkit-transform' => 'skew(0,15deg)',
                    'width'             => 'auto',
                ),
                'i2_custom_class' => 'br-labels-advanced-8-i2',
            ),
            9 => array(
                'content_type'       => 'custom',
                'image_height'       => '85',
                'image_width'        => '80',
                'bottom_margin'      => '0',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'top_margin'         => '0',
                'padding_horizontal' => '0',
                'padding_top'        => '0',
                'text'               => '%sale%price off%sale%',
                'text_after'         => 'save %sale_val%',
                'text_before'        => 'Sale %sale_p%',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i2_custom_css' => array( //span i.template-i
                    'bottom'            => '-12%',
                    'clip-path'         => 'polygon(0 0, 100% 0, 100% 75%, 50% 100%, 0 75%)',
                    '-webkit-clip-path' => 'polygon(0 0, 100% 0, 100% 75%, 50% 100%, 0 75%)',
                    'height'            => '30px',
                    'width'             => '100%',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'background-color' => '#fafafa !important',
                    'height'           => '71px',
                    'left'             => '0',
                    'right'            => '0',
                    'top'              => '0',
                    'width'            => 'auto',
                ),
            ),
            10 => array(
                'content_type'       => 'custom',
                'bottom_margin'      => '0',
                'image_height'       => '85',
                'image_width'        => '80',
                'left_margin'        => '0',
                'right_margin'       => '0',
                'shadow_use'         => '1',
                'shadow_shift_right' => '-5',
                'top_margin'         => '-14',
                'padding_horizontal' => '0',
                'padding_top'        => '-14',
                'text'               => '%sale_p%',
                'text_after'         => 'off',
                'text_before'        => '',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i3_custom_css' => array( //span i.template-i-after
                    'clip-path'         => 'polygon(100% 57%, 100% 100%, 36% 88%, 27% 66%)',
                    '-webkit-clip-path' => 'polygon(100% 57%, 100% 100%, 36% 88%, 27% 66%)',
                    'height'            => '100%',
                    'width'             => '100%',
                    'z-index'           => '99',
                ),
                'i4_custom_css' => array( //span i.template-i-before
                    'background-color'  => '#000000 !important',
                    'height'            => '100%',
                    'clip-path'         => 'polygon(100% 18%, 100% 100%, 36% 88%, 0 0)',
                    '-webkit-clip-path' => 'polygon(100% 18%, 100% 100%, 36% 88%, 0 0)',
                    'width'             => '100%',
                    'z-index'           => '99',
                ),
            ),
            11 => array(
                'content_type'       => 'custom',
                'image_height'       => '50',
                'image_width'        => '100',
                'left_margin'        => '-6',
                'right_margin'       => '-6',
                'top_margin'         => '-5',
                'position'           => 'left',
                'padding_horizontal' => '-6',
                'padding_top'        => '-5',
                'text'               => '%sale_p%',
                'text_after'         => 'Save %sale_val%',
                'text_before'        => 'off',
                'span_custom_css' => array(
                    'background' => 'transparent !important',
                ),
                'i1_custom_css' => array( //span i.template-i-before
                    'background-color'    => 'transparent !important',
                    'border-bottom-color' => 'transparent !important',
                    'border-bottom-style' => 'solid',
                    'border-bottom-width' => '6px',
                    'border-left-style'   => 'solid',
                    'border-left-width'   => '6px',
                    'bottom'              => '-5px',
                    'filter'              => 'brightness(120%)',
                    '-webkit-filter'      => 'brightness(120%)',
                    'right'               => '0',
                ),
                'i4_custom_css' => array( //span i.template-i
                    'clip-path'         => 'polygon(100% 0%, 100% 100%, 16% 99%, 0% 50%, 16% 0)',
                    '-webkit-clip-path' => 'polygon(100% 0%, 100% 100%, 16% 99%, 0% 50%, 16% 0)',
                    'height'            => '100%',
                    'width'             => '100%',
                ),
            ),
            12 => array(
                'border_radius'       => '50',
                'border_radius_units' => '%',
                'bottom_margin'       => '5',
                'content_type'        => 'custom',
                'image_height'        => '120',
                'image_width'         => '120',
                'left_margin'         => '5',
                'right_margin'        => '5',
                'top_margin'          => '5',
                'padding_horizontal'  => '5',
                'padding_top'         => '5',
                'text'                => '%sale%%sale_p% off%sale%',
                'text_after'          => 'Save %sale_val%',
                'text_before'         => 'launch offer',
                'i1_custom_css' => array( //span i.template-i-before
                    'background-color'  => '#444 !important',
                    'background-image' => 'none !important',
                    'height'            => '16px',
                    'left'              => '20px',
                    'top'               => '60px',
                    'transform'         => 'skew(-30deg)',
                    '-moz-transform'    => 'skew(-30deg)',
                    '-ms-transform'     => 'skew(-30deg)',
                    '-o-transform'      => 'skew(-30deg)',
                    '-webkit-transform' => 'skew(-30deg)',
                    'width'             => '80px',
                ),
                'i2_custom_css' => array( //span i.template-i
                    'background-color' => '#444 !important',
                    'background-image' => 'none !important',
                    'height'           => '2px',
                    'right'            => '8px',
                    'top'              => '60px',
                    'width'            => '88px',
                ),
                'i3_custom_css' => array( //span i.template-i-after
                    'background-color' => '#444 !important',
                    'background-image' => 'none !important',
                    'height'           => '2px',
                    'left'             => '8px',
                    'top'              => '74px',
                    'width'            => '88px',
                ),
                'b_custom_css'   => array(
                    'border-radius' => '50%',
                ),
            ),
        );
        return $templates;
    }

    public function shadow( $styles, $br_label ) {
        if( empty( $br_label['shadow_use'] ) ) return $styles;

        list( $r, $g, $b ) = sscanf( $br_label['shadow_color'], "#%02x%02x%02x" );
        $shadow = "{$br_label['shadow_shift_right']}px {$br_label['shadow_shift_down']}px {$br_label['shadow_blur']}px rgba($r, $g, $b, {$br_label['shadow_opacity']})";

        return $styles .
            " filter: drop-shadow($shadow);
            -webkit-filter: drop-shadow($shadow);";
    }

    public function timer_shadow( $styles, $br_label ) {
        if ( empty( $br_label['timer_shadow'] ) ) return $styles;

        return $this->shadow( $styles, $br_label );
    }

    public function scale( $option, $properties ) {
        foreach ( $properties as $property ) {
            array_unshift( $option[$property]['items'],
                array(
                    "type"      => "checkbox",
                    "label_for" => __('Scale', 'BeRocket_products_label_domain'),
                    "name"      => "{$property}_scale",
                    "class"     => 'br_option_scale',
                    "value"     => '1',
                    "selected"  => true,
                ) );
        }
        return $option;
    }
    public function javascript_include($label_id, $br_label) {
        if( ! empty( $br_label['mobile_multiplier'] ) && floatval($br_label['mobile_multiplier']) && floatval($br_label['mobile_multiplier']) != 1.0  ) {
            wp_enqueue_script( 'berocket_tippy');
        }
    }
}
new BeRocket_products_label_paid();
