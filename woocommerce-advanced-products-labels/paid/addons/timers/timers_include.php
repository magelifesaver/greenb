<?php
class BeRocket_products_label_timers_class {
    private static $deafult_hooks = array( 
        'shop_hook' => 'woocommerce_shop_loop_item_title',
        'product_hook' => 'woocommerce_single_product_summary',
    );
    public $labels_timer = array();
    public $default_settings = array();

    function __construct() {
        $this->default_settings = array(
            'timer_opacity'       => 'use',
            'timer_shadow'        => 'use',
            'timer_leading_zeros' => 'use',
            'timer_margin_bottom' => '15',
            'timer_margin_left'   => '0',
            'timer_margin_right'  => '0',
            'timer_margin_top'    => '15',
            'timer_margin_units'  => 'px',
            'timer_template'      => 'large-1',
            'timer_use'           => '',
        );

        add_filter( 'brfr_data_products_label', array(__CLASS__, 'settings_page' ), 30 );
        add_filter( 'brfr_tabs_info_products_label', array(__CLASS__, 'settings_tabs' ), 30 );
        $this->init_hooks();
        if( is_admin() ) {
            $this->admin_init();
        }
        add_filter( 'berocket_apl_load_admin_edit_scripts', array( $this, 'admin_scripts' ), 15 );

        add_filter( 'brfr_data_berocket_advanced_label_editor', array( $this, 'berocket_timers_fields' ) );
        add_filter( 'berocket_apl_show_label_on_product_html', array( $this, 'show_compact_timer' ), 10, 3 );
        add_action( "berocket_apl_set_label", array ( $this, 'variation_label' ) );

        add_filter( 'brfr_berocket_advanced_label_editor_timer_templates', array( $this, 'section_timer_templates' ), 10, 4 );

        add_action( "wp_ajax_br_timer_label_data", array ( $this, 'timer_label_data' ) );
        add_action( "wp_ajax_nopriv_br_timer_label_data", array ( $this, 'timer_label_data' ) );

        add_shortcode( 'br-wapl-timers', array( $this, 'show_timer_shortcode' ) );
        
        add_action('bapl_show_all_large_timers', array($this, 'show_all_large_timers'), 10, 1);
        $BeRocket_products_label = BeRocket_products_label::getInstance();
        $BeRocket_products_label->active_addons['timers'] = $this; 
        add_filter('brapl_condition_date_time_html', array( $this, 'type_condition_date_time'), 20, 3);
    }

    public function init_hooks() {
        $options = apply_filters( 'berocket_labels_get_base_options', false );

        if( !empty($options['timer_shop_hook']) ) {
            add_action( $options['timer_shop_hook'], array( $this, 'show_timer_hook' ), 10 );
        }
        if( !empty($options['timer_product_hook']) ) {
            add_action( $options['timer_product_hook'], array( $this, 'show_timer_hook' ), 10 );
        }
        wp_enqueue_style( 'br_labels_timers_css', plugins_url( 'css/frontend.css', __FILE__ ) );
        wp_enqueue_script( 'br_labels_timers', plugins_url( 'js/frontend.js', __FILE__ ), array( 'jquery' ) );
        wp_localize_script( 'br_labels_timers', 'brlabelsHelper',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    public function admin_scripts() {
        wp_enqueue_script( 'br_labels_timers', plugins_url( 'js/frontend.js', __FILE__ ), array( 'jquery' ) );
            wp_localize_script( 'br_labels_timers', 'brlabelsHelper',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                )
            );
        wp_enqueue_style( 'br_labels_timers_admin_css', plugins_url( 'css/admin.css', __FILE__ ) );
        wp_enqueue_script( 'br_labels_timers_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
    }
    public function admin_init() {
        add_action( 'berocket_apl_set_label_end', array( $this, 'show_large_timers' ), 10, 1 );
    }

    public static function settings_tabs($tabs) {
        $tabs = berocket_insert_to_array(
            $tabs,
            'Addons',
            array(
                'Timers' => array(
                    'icon'  => 'clock-o',
                    'name' => __( 'Timers', 'BeRocket_products_label_domain' ),
                ),
            )
        );
        return $tabs;
    }
    public static function settings_page($data) {
        $BeRocket_products_label = BeRocket_products_label::getInstance();
        $options = apply_filters( 'berocket_labels_get_base_options', false );
        $timer_shop_hooks = array(
            array('value' => 'woocommerce_before_shop_loop_item',
                'text' => __('Before product', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_before_shop_loop_item_title',
                'text' => __('After product image', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_shop_loop_item_title',
                'text' => __('After product title', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_after_shop_loop_item_title',
                'text' => __('Before &laquo;Add to cart&raquo; button', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_after_shop_loop_item',
                'text' => __('After &laquo;Add to cart&raquo; button', 'BeRocket_products_label_domain')),
        );
        $timer_shop_hook = br_get_value_from_array($options, array('timer_shop_hook'));
        $timer_shop_hooks = $BeRocket_products_label->add_additional_hooks($timer_shop_hooks, $timer_shop_hook, 'content', 'product');
        $timer_shop_hooks = apply_filters('berocket_apl_settings_shop_hook_array', $timer_shop_hooks);

        $timer_product_hooks = array(
            array('value' => 'woocommerce_before_single_product',
                'text' => __('Before product', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_product_thumbnails',
                'text' => __('After product image', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_single_product_summary',
                'text' => __('Before product description', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_before_add_to_cart_form',
                'text' => __('After product description', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_before_add_to_cart_button',
                'text' => __('Before &laquo;Add to cart&raquo; button', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_after_add_to_cart_button',
                'text' => __('After &laquo;Add to cart&raquo; button', 'BeRocket_products_label_domain')),
            array('value' => 'woocommerce_after_single_product_summary',
                'text' => __('After product', 'BeRocket_products_label_domain')),
        );
        $timer_product_hook = br_get_value_from_array($options, array('timer_product_hook'));
        $timer_product_hooks = $BeRocket_products_label->add_additional_hooks($timer_product_hooks, $timer_product_hook, 'content', 'single-product');
        $timer_product_hooks = apply_filters('berocket_apl_settings_single_hook_array_image', $timer_product_hooks);

        $data['Timers'] = array(
            'timer_shop_hook' => array(
                "type"     => "selectbox",
                "options"  => $timer_shop_hooks,
                "label"    => __('Timer Shop Hook', 'BeRocket_products_label_domain'),
                "label_for"=> __('Place for the timers (large version) on the shop page.', 'BeRocket_products_label_domain'),
                "name"     => "timer_shop_hook",
                "value"     => self::$deafult_hooks['shop_hook'],
            ),
            'timer_product_hook' => array(
                "type"     => "selectbox",
                "options"  => $timer_product_hooks,
                "label"    => __('Timer Product Hook', 'BeRocket_products_label_domain'),
                "label_for"=> __('Place for the timer (large version) on the product page.', 'BeRocket_products_label_domain'),
                "name"     => "timer_product_hook",
                "value"     => self::$deafult_hooks['product_hook'],
            ),
            'load_timer_ajax' => array(
                "type"     => "checkbox",
                "label"    => __('Load timers via AJAX', 'BeRocket_products_label_domain'),
                "name"     => "load_timer_ajax",
                "value"    => "1",
                "label_for"=> __('Fix issues with caching plugins', 'BeRocket_products_label_domain'),
            )
        );
        return $data;
    }

    public function variation_label() {
        if( ! empty($_REQUEST['variation_id']) && ! empty($_REQUEST['action']) && $_REQUEST['action'] == 'variation_label' ) {
            $variation = intVal( sanitize_text_field( $_REQUEST['variation_id'] ) );
            do_action('bapl_show_all_large_timers', $variation);
        }
    }

    public function show_timer_hook() {
        do_action('bapl_show_all_large_timers');
    }

    public function show_timer_shortcode() {
        ob_start();
        do_action('bapl_show_all_large_timers');
        return ob_get_clean();
    }

    public function show_all_large_timers( $product_id = false ) {
        if ( empty( $product_id ) ) {
            global $product;
        } else {
            $product = wc_get_product( $product_id );
        }
        $labels_ids = apply_filters( 'berocket_labels_get_product_labels_ids', array(), $product );
        echo "<div class='br_timer_hook'>";
        echo "<div class='br_timer_container'>";
        foreach ( $labels_ids as $label_id ) {
            $br_label = apply_filters( 'berocket_label_custom_get_options', array(), $label_id );
            if( ! isset($br_label['data']) || apply_filters('brapl_check_label_on_post', $label_id, $br_label['data'], $product) ) {
                $timer = $this->get_timer( $br_label );
                if ( !$timer || $timer['type'] == 'compact' ) continue;
                $br_label['label_id'] = $label_id;
                echo $this->show_timer( $br_label, $product );  
            }            
        }
        echo "</div>";
        echo "</div>";
    }

    public function show_compact_timer( $html, $br_label, $product ) {
        if ( !empty( $br_label['timer_use'] ) ) {
            $timer = $this->get_timer( $br_label );
            if ( $timer && $timer['type'] == 'compact' ) {
                if ( $br_label['content_type'] == 'sale_end' ) {
                    $new_html = array(
                        'open_div' => $html['open_div'],
                        'close_div' => $html['close_div'],
                    );
                    $html = $new_html;
                }
                $html = berocket_insert_to_array($html, 'close_div', array(
                    'timer' => $this->show_timer($br_label, $product)
                ), true);
            } elseif ( $timer ) {
                if ( $br_label['content_type'] == 'sale_end' ) {
                    $html = array();
                }
            }
        }
        return $html;
    }

    public function show_large_timers($product) {
        if( is_string($product) && $product == 'demo' ) {
            $br_label = $_POST['br_labels'];
            $timer = $this->get_timer( $br_label );
            if ( $timer && $timer['type'] == 'large' ) {
                echo $this->show_timer( $br_label, 'demo' );
            }
        }
    }

    public function berocket_timers_fields( $data ) {
        $data['General'] += array(
            'timer_use' => array(
                "type"      => "checkbox",
                "label"     => __('Use timer', 'BeRocket_products_label_domain'),
                "label_for" => __('For the products with sale price dates', 'BeRocket_products_label_domain'),
                "class"     => 'br_use_options',
                "extra"     => 'id="br_timer_use"',
                "name"      => "timer_use",
                "value"     => 'use',
                'select'    => false,
            ),
            'timer_template' => array(
                'section' => 'timer_templates',
                "label"     => __('Timer template', 'BeRocket_products_label_domain'),
                "class"     => 'br_label_style_option br_timer_option',
                "name"      => "timer_template",
                "value"     => $this->default_settings['timer_template'],
            ),
            'timer_margins' => array(
                "label" => __('Margins', 'BeRocket_products_label_domain'),
                "items" => array(
                    array(
                        "type"  => "number",
                        "name"  => "timer_margin_top",
                        "class" => 'br_label_style_option br_js_change br_timer_margin br_timer_option',
                        "extra" => " data-large-style='margin-top' data-compact-style='margin-top'",
                        "value" => $this->default_settings['timer_margin_top'],
                        "label_be_for" => __('Top', 'BeRocket_products_label_domain'),
                    ),
                    array(
                        "type"  => "number",
                        "name"  => "timer_margin_right",
                        "class" => 'br_label_style_option br_js_change br_timer_margin br_timer_option',
                        "extra" => " data-large-style='margin-right' data-compact-style='right'",
                        "value" => $this->default_settings['timer_margin_right'],
                        "label_be_for" => __('Right', 'BeRocket_products_label_domain'),
                    ),
                    array(
                        "type"  => "number",
                        "name"  => "timer_margin_bottom",
                        "class" => 'br_label_style_option br_js_change br_timer_margin br_timer_option',
                        "extra" => " data-large-style='margin-bottom' data-compact-style='bottom'",
                        "value" => $this->default_settings['timer_margin_bottom'],
                        "label_be_for" => __('Bottom', 'BeRocket_products_label_domain'),
                    ),
                    array(
                        "type"  => "number",
                        "name"  => "timer_margin_left",
                        "class" => 'br_label_style_option br_js_change br_timer_margin br_timer_option',
                        "extra" => " data-large-style='margin-left' data-compact-style='left'",
                        "value" => $this->default_settings['timer_margin_left'],
                        "label_be_for" => __('Left', 'BeRocket_products_label_domain'),
                    ),
                    brapl_select_units('timer_margin', $this->default_settings['timer_margin_units'],
                        'br_label_style_option br_js_change br_timer_margin_units br_timer_option' ),
                ),
            ),
            'timer_format' => array(
                "label" => __('Show leading zeros', 'BeRocket_products_label_domain'),
                "type"  => "checkbox",
                "class" => 'br_label_style_option br_timer_option',
                "name"  => "timer_leading_zeros",
                "value" => $this->default_settings['timer_leading_zeros'],
            ),
            'timer_opacity' => array(
                "label" => __( 'Use opacity', 'BeRocket_products_label_domain' ),
                "type"  => "checkbox",
                "name"  => "timer_opacity",
                "class" => 'br_label_style_option br_timer_option',
                "value" => $this->default_settings['timer_opacity'],
            ),
            'timer_shadow' => array(
                "label"     => __( 'Use shadow', 'BeRocket_products_label_domain' ),
                "label_for" => __( 'To use shadow, check the box <b>"Use shadow effect"</b> for lables on the tab <b>"Styles"</b>', 'BeRocket_products_label_domain' ),
                "type"      => "checkbox",
                "name"      => "timer_shadow",
                "class"     => 'br_label_style_option br_timer_option',
                "value"     => $this->default_settings['timer_shadow'],
            ),
        );

        $data['Style']['color']['class'] .= ' br_timer_setting';
        $data['Style']['font_color']['class'] .= ' br_timer_setting';

        return $data;
    }

    public function section_timer_templates( $html, $item, $options ) {
        $template_types = array( 
            'large' => array(
                'title' => __( 'Large', 'BeRocket_products_label_domain' ),
                'is_active' => '',
            ), 
            'compact' => array(
                'title' => __( 'Compact', 'BeRocket_products_label_domain' ),
                'is_active' => '',
            ),
        );
        if ( empty( $options['timer_template'] ) ) {
            $options['timer_template'] = $this->default_settings['timer_template'];
        }
        $timer = $this->get_timer( $options );
        if ( !$timer ) return $html;

        $template_types[ $timer['type'] ]['is_active'] = 'active';
        $html = "<tr><th>";
        foreach ( $template_types as $type => $info ) {
            $html .= "<div class='br_settings_vtab br_timers_vtab {$info['is_active']}' data-tab='$type-timers'>{$info['title']}</div>";
        }
        $html .= "</th>
                <td class='br_label_timer_templates'>";

        foreach ( $template_types as $type => $info ) {
            $html .= "<div class='br_settings_vtab-content br_settings_timers tab-$type-timers {$info['is_active']}'>
                        <ul class='br_template_select br_timer_template_select'>";
            foreach( $this->get_timer_template( $type ) as $key ) {
                $checked = $options['timer_template'] == "$type-$key" ? 'checked' : '';
                $html .= "
                    <li>
                        <input id='thumb_timer_layout_$type-$key' type='radio' name='br_labels[timer_template]' data-border_radius='3' value='$type-$key' $checked>
                        <label class='br-timer-template-preview br-timer-template-$type-$key {$item['class']}' for='thumb_timer_layout_$type-$key'>
                            <span>
                                <span>
                                </span>
                            </span>
                        </label>
                    </li>";
            }
            $html .= "</ul></div>";
        }
        
        $html .= "</td></tr>";

        return $html;
    }

    private function get_timer_template( $type = 'large', $key = '', $br_label = '', $parent_class = '' ) {
        if ( !empty( $br_label ) ) {
            $background_color = "background-color: {$br_label['color']};";
            $color = "color: {$br_label['font_color']};";
        } else {
            $background_color = $color = '';
        }

        $timer_templates = array(
            'large' => array( 
                1 => array(
                    'base_styles' => 'background-color: transparent !important;',
                    'styles' => 
                        ".$parent_class.timer_template_large-1 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-1 .br_label_timer_item {
                            $background_color
                            $color
                            display: inline-block;
                            height: 3em;
                            margin: 2px;
                            padding: 3px;
                            text-align: center;
                            width: 3em;
                        }
                        .$parent_class.timer_template_large-1 .br_label_timer_item_name {
                            background-color: transparent !important;
                            font-size: 10px;
                            line-height: 10px;
                            text-transform: uppercase;
                        }
                        .$parent_class.timer_template_large-1 .br_label_timer_item_value {
                            background-color: transparent !important;
                            font-size: 16px;
                            line-height: 1.5em;
                            font-weight: bold;
                        }
                        .$parent_class.timer_template_large-1 .br_label_timer_hours,
                        .$parent_class.timer_template_large-1 .br_label_timer_minutes,
                        .$parent_class.timer_template_large-1 .br_label_timer_seconds {
                            background-color: #444 !important;
                        }",
                ), 
                2 => array(
                    'base_styles' => 'background-color: transparent !important;',
                    'styles' => 
                        ".$parent_class.timer_template_large-2 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-2 .br_label_timer_item {
                            background-color: transparent !important;
                            display: inline-block;
                            margin: 2px;
                            text-align: center;
                        }
                        .$parent_class.timer_template_large-2 .br_label_timer_item_name {
                            background-color: transparent !important;
                            color: #AAA !important;
                            font-size: 12px;
                        }
                        .$parent_class.timer_template_large-2 .br_label_timer_item_value {
                            background-color: transparent !important;
                            border: 1px solid #AAA;
                            border-radius: 2px;
                            color: #000 !important;
                            font-size: 16px;
                            font-weight: bold;
                            height: 2.7em;
                            line-height: 2.7em;
                            width: 2.7em;
                        }",
                ),
                3 => array(
                    'base_styles' => "$background_color $color",
                    'styles' => 
                        ".$parent_class.timer_template_large-3 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-3 .br_label_timer_item {
                            display: inline-block;
                            padding: 5px;
                            position: relative;
                            text-align: center;
                            width: 3em;
                        }
                        .$parent_class.timer_template_large-3 .br_label_timer_item:not(:last-of-type):after {
                            content: '';
                            border: 1px solid;
                            height: 30px;
                            position: absolute;
                            right: 0;
                            bottom: 13px;
                            width: 0;
                        }
                        .$parent_class.timer_template_large-3 .br_label_timer_item_name {
                            font-size: 12px;
                        }
                        .$parent_class.timer_template_large-3 .br_label_timer_item_value {
                            font-size: 18px;
                            font-weight: bold;
                        }",
                ),
                4 => array(
                    'base_styles' => 'background-color: transparent !important;',
                    'styles' => 
                        ".$parent_class.timer_template_large-4 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-4 .br_label_timer_item {
                            $background_color 
                            $color
                            border-radius: 50%;
                            display: inline-block;
                            height: 3em;
                            margin: 2px;
                            padding: 9px;
                            text-align: center;
                            width: 3em;
                        }
                        .$parent_class.timer_template_large-4 .br_label_timer_item_name {
                            font-size: 11px;
                            line-height: 11px;
                            white-space: nowrap;
                        }
                        .$parent_class.timer_template_large-4 .br_label_timer_item_value {
                            font-size: 16px;
                            font-weight: bold;
                            line-height: 18px;
                        }",
                ),
                5 => array(
                    'base_styles' => 'background-color: transparent !important;',
                    'styles' => 
                        ".$parent_class.timer_template_large-5 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-5 .br_label_timer_item {
                            background-color: transparent !important;
                            display: inline-block;
                            margin: 2px;
                            text-align: center;
                        }
                        .$parent_class.timer_template_large-5 .br_label_timer_item_name {
                            background-color: transparent !important;
                            color: #777 !important;
                            font-size: 12px;
                        }
                        .$parent_class.timer_template_large-5 .br_label_timer_item_value {
                            border-radius: 50%;
                            font-size: 16px;
                            font-weight: bold;
                            height: 40px;
                            line-height: 40px;
                            width: 40px;
                        }
                        .$parent_class.timer_template_large-5 .br_label_timer_item .br_label_timer_item_value {
                            $background_color 
                            $color
                        }
                        .$parent_class.timer_template_large-5 .br_label_timer_hours .br_label_timer_item_value,
                        .$parent_class.timer_template_large-5 .br_label_timer_minutes .br_label_timer_item_value,
                        .$parent_class.timer_template_large-5 .br_label_timer_seconds .br_label_timer_item_value {
                            background-color: #DDD !important;
                            color: #000 !important;
                        }",
                ),
                6 => array(
                    'base_styles' => 
                        "$background_color 
                        $color
                        border-radius: 3px;
                        font-size: 12px;
                        height: 30px;
                        line-height: 30px;
                        max-width: 220px;
                        text-align: center;
                        width: 100%;",
                    'styles' => 
                        ".$parent_class.timer_template_large-6 .br_label_timer_title {
                            margin-right: 4px;
                        }
                        .$parent_class.timer_template_large-6 .br_label_timer_title,
                        .$parent_class.timer_template_large-6 .br_label_timer_item {
                            display: inline-block;
                        }
                        .$parent_class.timer_template_large-6 .br_label_timer_item:not(:last-of-type) .br_label_timer_item_value:after {
                            content: ':';
                            margin: 0 4px;
                        }
                        .$parent_class.timer_template_large-6 .br_label_timer_item_name {
                            display: none;
                        }",
                ),
                7 => array(
                    'base_styles' => 
                        "background-color: transparent !important; 
                        color: #444;
                        font-size: 14px;
                        width: 100%;",
                    'styles' => 
                        ".$parent_class.timer_template_large-7 .br_label_timer_title {
                            display: none;
                        }
                        .$parent_class.timer_template_large-7 .br_label_timer_item {
                            display: inline-block;
                            padding: 0 3px;
                        }
                        .$parent_class.timer_template_large-7 .br_label_timer_item:not(:last-of-type) .br_label_timer_item_value:after {
                            content: ':';
                            margin: 0 4px;
                        }
                        .$parent_class.timer_template_large-7 .br_label_timer_item_name {
                            color: #777;
                            font-size: 10px;
                            text-transform: uppercase;
                        }",
                ),
            ),
            'compact' => array(
                1 => array(
                    'base_styles' => 
                        "$background_color 
                        $color
                        border-radius: 3px;
                        font-size: 12px;
                        line-height: 1em;
                        min-width: 95px;
                        padding: 10px;
                        position: absolute;
                        text-align: center;",
                    'styles' => 
                        ".$parent_class.timer_template_compact-1 .br_label_timer_title {
                            display: block;
                            text-transform: uppercase;
                        }
                        .$parent_class.timer_template_compact-1 .br_label_timer_title:after {
                            clear: both;
                        }
                        .$parent_class.timer_template_compact-1 .br_label_timer_item {
                            display: inline-block;
                        }
                        .$parent_class.timer_template_compact-1 .br_label_timer_days .br_label_timer_item_value:after,
                        .$parent_class.timer_template_compact-1 .br_label_timer_hours .br_label_timer_item_value:after {
                            content: ':';
                            margin: 0 4px;
                        }
                        .$parent_class.timer_template_compact-1 .br_label_timer_seconds,
                        .$parent_class.timer_template_compact-1 .br_label_timer_item_name {
                            display: none;
                        }",
                ),
            ),
        );
        if ( empty( $key ) ) return array_keys( $timer_templates[$type] );
        $base_styles = 
            "margin: {$br_label['timer_margin_top']}{$br_label['timer_margin_units']} {$br_label['timer_margin_right']}{$br_label['timer_margin_units']} {$br_label['timer_margin_bottom']}{$br_label['timer_margin_units']} {$br_label['timer_margin_left']}{$br_label['timer_margin_units']};
            text-align: center;";
        if ( !empty( $br_label['font_family'] ) ) {
            $base_styles .= "font-family: {$br_label['font_family']}; ";
        }

        if ( $type == 'compact' ) {
            switch ( $br_label['position'] ) {
                case 'left':
                    $base_styles .= "left: {$br_label['timer_margin_left']}{$br_label['timer_margin_units']};";
                break;
                
                case 'right':
                    $base_styles .= "right: {$br_label['timer_margin_right']}{$br_label['timer_margin_units']};";
                break;
                
                case 'center':
                    $base_styles .= "left: 50%;
                        'transform: translateX(-50%);
                        -moz-transform: translateX(-50%);
                        -ms-transform: translateX(-50%);
                        -o-transform: translateX(-50%);
                        -webkit-transform: translateX(-50%);";
                break;
                
                default:
                break;
            }
        } 
        $base_styles .= $timer_templates[$type][ $key ]['base_styles'];

        if ( !empty( $br_label['timer_opacity'] ) ) {
            $base_styles .= "opacity: {$br_label['opacity']};";
        }

        $base_styles = apply_filters( 'brapl_timer_styles', $base_styles, $br_label );

        return ".$parent_class.timer_template_$type-$key.br_label_timer { $base_styles } " . $timer_templates[$type][ $key ]['styles'];
    }

    public function timer_label_data() {
        $products_data = array();
        if( is_array($_POST['products']) ) {
            foreach($_POST['products'] as $product_data) {
                $product_id = intval($product_data['product']);
                $label_id = intval($product_data['label']);
                if( ! empty($product_id) ) {
                    $products_data[] = array(
                        'id' => $product_id,
                        'label' => $label_id,
                        'data' => $this->get_timer_data_for_product_label($product_id, $label_id)
                    );
                }
            }
        }
        echo json_encode($products_data);
        wp_die();
    }

    public function get_time_for_product($product_id) {
        $sales_end = get_post_meta( $product_id, '_sale_price_dates_to', true );
        return $this->get_time_diff($sales_end);
    }
    public function get_timer_data_for_product_label($product_id, $label_id = false, $br_label = false) {
        $times_left = FALSE;
        if( $label_id != false || $br_label != false ) {
            if( $br_label == false ) {
                $br_label = apply_filters( 'berocket_label_custom_get_options', array(), $label_id );
            }
            $br_label['label_id'] = $label_id;
            $times_left_condition = $this->get_datetime_end_conditions($br_label);
            $times_left = $this->get_time_diff($times_left_condition);
        }
        if( $times_left === FALSE ) {
            $product = wc_get_product( $product_id );
            if( $product->get_type() == 'variable' ) {
                $variations = $product->get_children();
                if( is_array($variations) ) {
                    foreach($variations as $variation_id) {
                        $times_left = $this->get_time_for_product($variation_id);
                        if( $times_left !== FALSE ) {
                            break;
                        }
                    }
                }
            } else {
                $product_id = $product->get_id();
                $times_left = $this->get_time_for_product($product_id);
            }
        }
        return $times_left;
    }
    public function get_time_diff($sales_end = false) {
        if ( empty( $sales_end ) ) return false;
        $diff = $sales_end - time();
        if ( $diff < 0 ) return false;

        $sec_in_hour = 60*60;
        $sec_in_day  = $sec_in_hour*24;

        $days = floor( $diff / $sec_in_day ); 
        $days_in_sec = $days * $sec_in_day;
        
        $hours = floor( ( $diff - $days_in_sec ) / $sec_in_hour );
        $sec_in_hour = $hours * $sec_in_hour;
        
        $minutes = floor( ( $diff - $days_in_sec - $sec_in_hour ) / 60 );
        $seconds = floor( ( $diff - $days_in_sec - $sec_in_hour - $minutes*60 ) );
        return array('days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
    }

    public function get_datetime_end_conditions($br_label) {
        $times_left_condition = FALSE;
        if( isset($br_label['label_id']) && isset($this->labels_timer[$br_label['label_id']]) ) {
            $times_left_condition = $this->labels_timer[$br_label['label_id']];
        } else {
            $times_left_condition = FALSE;
            if( isset($br_label['data']) && is_array($br_label['data']) ) {
                foreach($br_label['data'] as $conditions) {
                    foreach($conditions as $condition) {
                        if( $condition['type'] == 'date_time' && ! empty($condition['fortimer']) ) {
                            try {
                                $times_left_condition = ( empty($condition['to']) || $condition['to'] == '____/__/__ __:__' ) ? false : new DateTime($condition['to']);
                            } catch (Exception $e) {
                                $times_left_condition = FALSE;
                            }
                        }
                    }
                }
                if( $times_left_condition !== FALSE ) {
                    $times_left_condition = $times_left_condition->getTimestamp();
                    if( isset($br_label['label_id']) ) {
                        $this->labels_timer[$br_label['label_id']] = $times_left_condition;
                    }
                }
            }
        }
        return $times_left_condition;
    }

    public function show_timer( $br_label, $product ) {
        $options = apply_filters( 'berocket_labels_get_base_options', false );
        if ( empty( $br_label['timer_use'] ) || empty( $br_label['timer_template'] ) ) return '';
        if ( !wp_script_is( 'br_labels_timers' ) ) {        
        }
        if ( $product == 'demo') {
            $days = '2';
            $hours = '12';
            $minutes = '42'; 
            $seconds = '42';
        } else {
            $times_left = $this->get_timer_data_for_product_label($product, false, $br_label);
            if($times_left === FALSE) {
                return '';
            }
            $days = $times_left['days'];
            $hours = $times_left['hours'];
            $minutes = $times_left['minutes']; 
            $seconds = $times_left['seconds'];
        }

        $time = array(
            'days'    => array( 'name' => __( 'Days', 'BeRocket_products_label_domain' ), 'value' => $days ),
            'hours'   => array( 'name' => __( 'Hrs', 'BeRocket_products_label_domain' ), 'value' => $hours ),
            'minutes' => array( 'name' => __( 'Min', 'BeRocket_products_label_domain' ), 'value' => $minutes ),
            'seconds' => array( 'name' => __( 'Sec', 'BeRocket_products_label_domain' ), 'value' => $seconds ),
        );
        if ( !empty( $br_label['timer_leading_zeros'] ) ) {
            foreach ( $time as $key => $item ) {
                $time[$key]['value'] = sprintf( '%02d', $item['value'] );
            }
        }

        $timer = $this->get_timer( $br_label );
        if ( !$timer ) return $html;

        $classes = array();
        $classes['parent_class'] = empty( $br_label['label_id'] ) ? 'berocket_alabel_id_demo' : "berocket_alabel_id_{$br_label['label_id']}";
        $classes[] = 'timer_template_'.$br_label['timer_template'];
        $data_values = array();
        $data_values['leading-zeros'] = empty( $br_label['timer_leading_zeros'] ) ? '' : $br_label['timer_leading_zeros'];
        $styles = $this->get_timer_template( $timer['type'], $timer['number'], $br_label, $classes['parent_class'] );

        if ( !empty( $br_label['hide_on_device'] ) ) {
            foreach ( $br_label['hide_on_device'] as $device ) {
                $classes[] = " berocket_hide_on_device_$device";
            }            
        }
        if( ! empty($options['load_timer_ajax']) && $product != 'demo' ) {
            $classes[] = 'br_timer_ajax_load';
            $data_values['productid'] = $product->get_id();
            $data_values['labelid'] = $br_label['label_id'];
        }

        $classes = implode(' ', $classes);
        $data_values_string = '';
        foreach($data_values as $data_name => $data_value) {
            $data_values_string .= ' data-'.$data_name.'=\''.$data_value.'\'';
        }
        $html ="<style class='br_label_timer_styles'>$styles</style>
            <div class='br_label_timer $classes'$data_values_string>
            <div class='br_label_timer_title'>" . __( 'Ends in', 'BeRocket_products_label_domain' ) . "</div>";
            foreach ( $time as $key => $item ) {
                $html .= 
                    "<div class='br_label_timer_item br_label_timer_$key'>
                        <div class='br_label_timer_item_value'>{$item['value']}</div>
                        <div class='br_label_timer_item_name'>{$item['name']}</div>
                    </div>";
            }
        $html .= "</div>";
        return $html;
    }

    public function get_timer( $br_label ) {
        if ( empty( $br_label['timer_template'] ) ) return false; 
        $timer = explode( '-', $br_label['timer_template'] );
        return array( 'type' => $timer[0], 'number' => $timer[1] );
    }
    public function type_condition_date_time($html, $name, $options) {
        $def_options = array('fortimer' => '');
        $options = array_merge($def_options, $options);
        $html .= '<div><label><input type="checkbox" class="br_apl_datetime_condition_timer" value="1"' . (empty($options['is_example']) ? '' : 'data-') . 'name="' . $name . '[fortimer]"' . ( empty($options['fortimer']) ? '' : ' checked' ) . '>' . __('Use for timer', 'BeRocket_products_label_domain') . '</label></div>'
        . '<script>jQuery(".br_conditions .br_apl_datetime_condition_timer").on("change", function() {
            if( jQuery(this).prop("checked") ) {
                jQuery(".br_conditions .br_apl_datetime_condition_timer:checked").not(jQuery(this)).prop("checked", false);
            }
        });</script>';
        
        return $html;
    }
}
new BeRocket_products_label_timers_class(); 
