<?php
class BeRocket_products_label_gradients_class {
    public $default_settings = array();
    public $templates_without_gradients = array();
    public $element_gradient_apply = array();
    public $disabled_elements = array();
    function __construct() {
        $this->default_settings = array(
            'gradient_start_color' => BeRocket_advanced_labels_custom_post::$base_color, 
            'gradient_end_color'   => '#FFFFFF', 
            'gradient_angle'       => 90,
            'gradient_orientation' => 'linear',
            'gradient_position'    => 'at top left',
            // 'gradient_repeat'   => 'repeating-',
            'gradient_size'        => 'farthest-side',
            'gradient_use'         => '',
        );

        $this->templates_without_gradients = array( 
            'css'      => array(  ),//11, 12, 13, 16, 21, 24, 25
            'advanced' => array(  ),//3, 8
        );
        $this->element_gradient_apply = array(
            'css' => array(
                24 => array(
                    'i2_styles',
                    'i3_styles',
                    'i4_styles'
                ),
                11 => array(
                    'i3_styles'
                ),
                12 => array(
                    'i3_styles'
                ),
                13 => array(
                    'i2_styles',
                    'i3_styles',
                    'i4_styles'
                )
            ),
            'advanced' => array(
                7 => array(
                    'i2_styles',
                    'i3_styles',
                    'i4_styles'
                ),
                8 => array(
                    'i2_styles'
                ),
            )
        );
        $this->disabled_elements = array('i2_styles', 'i3_styles', 'i4_styles');

        $this->init();
        add_action( 'berocket_apl_load_admin_edit_scripts', array( $this, 'admin_init' ), 15 );
        add_filter( 'brfr_data_berocket_advanced_label_editor', array( $this, 'berocket_gradients_fields' ) );

        add_filter( 'berocket_labels_templates_hide', array( $this, 'gradients_templates_hide' ), 100 );
    }

    public function init() {
        add_filter( 'berocket_apl_label_show_label_style', array( $this, 'span_styles' ), 1, 2 );
        add_filter( 'brapl_i1_styles', array( $this, 'i1_styles' ), 1, 2 );
        add_filter( 'brapl_i2_styles', array( $this, 'i2_styles' ), 1, 2 );
        add_filter( 'brapl_i3_styles', array( $this, 'i3_styles' ), 1, 2 );
        add_filter( 'brapl_i4_styles', array( $this, 'i4_styles' ), 1, 2 );
        add_filter( 'brapl_i1_styles_front', array( $this, 'i1_styles' ), 1, 2 );
        add_filter( 'brapl_i2_styles_front', array( $this, 'i2_styles' ), 1, 2 );
        add_filter( 'brapl_i3_styles_front', array( $this, 'i3_styles' ), 1, 2 );
        add_filter( 'brapl_i4_styles_front', array( $this, 'i4_styles' ), 1, 2 );
    }

    public function admin_init() {
        wp_enqueue_script( 'berocket_gradients_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
    }

    public function gradients_templates_hide( $templates_hide ) {
        $templates_without_gradients = $this->templates_without_gradients;
        $templates_without_gradients['image'] = array_keys( $templates_hide['image'] );
        $template_attributes = array_keys( $this->default_settings );

        foreach ( $templates_without_gradients as $template_group_key => $template_group ) {
            foreach ( $template_group as $template_number ) {
                $templates_hide[$template_group_key][$template_number] = array_merge( $templates_hide[$template_group_key][$template_number], $template_attributes );
            }
        }
        return $templates_hide;
    }

    public function berocket_gradients_fields( $data ) {
        $usebackground = array(
            'css-25',
            'advanced-3',
            'advanced-7',
        );
        $data['Style'] += array(
            'gradient_use' => array(
                "type"  => "checkbox",
                "label" => __('Use gradient', 'BeRocket_products_label_domain'),
                "name"  => "gradient_use",
                "value" => 'use',
                "extra" => "id='br_gradient_use' data-for='.br_alabel > span' data-style='use:background' data-ext='' data-usebg='".json_encode($usebackground)."'",
                'class' => 'br_use_options',
                "selected" => false,
            ),
            'gradient_orientation' => array(
                "label" => __('Gradient orientation', 'BeRocket_products_label_domain'),
                "items" => array(
                    array(
                        "type"    => "selectbox",
                        "options" => array(
                            array('value' => 'linear', 'text' => __('Linear', 'BeRocket_products_label_domain')),
                            array('value' => 'radial', 'text' => __('Radial', 'BeRocket_products_label_domain')),
                            array('value' => 'elliptical', 'text' => __('Elliptical', 'BeRocket_products_label_domain')),
                        ),
                        "name"  => "gradient_orientation",
                        "value" => $this->default_settings['gradient_orientation'],
                        'extra' => ' id="br_gradient_orientation"',
                        "class" => 'br_label_style_option br_gradient_option',
                    ),
                    // array(
                    //     "type"      => "checkbox",
                    //     "label_for" => __('Repeat', 'BeRocket_products_label_domain'),
                    //     "name"      => "gradient_repeat",
                    //     "value"     => $this->default_settings['gradient_repeat'],
                    //     "class"     => 'br_label_style_option br_gradient_option',
                    //     "selected"  => false,
                    // ),
                ),
            ),
            'gradient_angle' => array(
                "type"      => "number",
                "label"     => __('Gradient angle', 'BeRocket_products_label_domain'),
                "label_for" => __('degrees', 'BeRocket_products_label_domain'),
                "name"      => "gradient_angle",
                "value"     => $this->default_settings['gradient_angle'],
                "class"     => 'br_label_style_option br_gradient_option br_gradient_linear_option',
            ),
            'gradient_size' => array(
                "type"         => "switch",
                "label"        => __('Gradient size', 'BeRocket_products_label_domain'),
                "label_for"    => __('farthest-side', 'BeRocket_products_label_domain'),
                "label_be_for" => __('farthest-corner', 'BeRocket_products_label_domain'),
                "name"         => "gradient_size",
                "value"        => $this->default_settings['gradient_size'],
                "class"        => 'br_label_style_option br_gradient_option br_gradient_radial_option',
            ),
            'gradient_position' => array(
                "type"    => "selectbox",
                "options" => array(
                    array('value' => 'at top left',      'text' => __('Top left', 'BeRocket_products_label_domain')),
                    array('value' => 'at top center',    'text' => __('Top center', 'BeRocket_products_label_domain')),
                    array('value' => 'at top right',     'text' => __('Top right', 'BeRocket_products_label_domain')),
                    array('value' => 'at left center',   'text' => __('Left center', 'BeRocket_products_label_domain')),
                    array('value' => 'at center center', 'text' => __('Center center', 'BeRocket_products_label_domain')),
                    array('value' => 'at right center',  'text' => __('Right center', 'BeRocket_products_label_domain')),
                    array('value' => 'at bottom left',   'text' => __('Bottom left', 'BeRocket_products_label_domain')),
                    array('value' => 'at bottom center', 'text' => __('Bottom center', 'BeRocket_products_label_domain')),
                    array('value' => 'at bottom right',  'text' => __('Bottom right', 'BeRocket_products_label_domain')),
                ),
                "label" => __('Gradient position', 'BeRocket_products_label_domain'),
                "name"  => "gradient_position",
                "value" => $this->default_settings['gradient_position'],
                "class" => 'br_label_style_option br_gradient_option br_gradient_radial_option',
            ),
            'gradient_start' => array(
                "label" => __('Gradient start color', 'BeRocket_products_label_domain'),
                "items" => array(
                    array(
                        "type"  => "color",
                        "name"  => "gradient_start_color",
                        "class" => 'br_label_style_option br_gradient_option',
                        "value" => $this->default_settings['gradient_start_color'],
                    ),
                    array(
                        "type"  => "range",
                        "name"  => "gradient_start_position",
                        "extra" => " id='gradient_start_position' min='0' max='100' data-ext='%'",
                        "class" => 'br_label_style_option br_gradient_option br_range',
                        "value" => '0',
                    ),
                    array(
                        "type"      => "number",
                        "name"      => "gradient_start_position_num",
                        "extra"     => " id='gradient_start_position_num' min='0' max='100' data-ext='%'",
                        "class"     => 'br_label_style_option br_gradient_option br_range_num',
                        "value"     => '100',
                        "label_for" => '%',
                    ),
                ),
            ),
            'gradient_end' => array(
                "label" => __('Gradient end color', 'BeRocket_products_label_domain'),
                "items" => array(
                    array(
                        "type"  => "color",
                        "name"  => "gradient_end_color",
                        "class" => 'br_label_style_option br_gradient_option',
                        "value" => $this->default_settings['gradient_end_color'],
                    ),
                    array(
                        "type"  => "range",
                        "name"  => "gradient_end_position",
                        "extra" => " id='gradient_end_position' min='0' max='100' data-ext='%'",
                        "class" => 'br_label_style_option br_gradient_option br_range',
                        "value" => '100',
                    ),
                    array(
                        "type"      => "number",
                        "name"      => "gradient_end_position_num",
                        "extra"     => " id='gradient_end_position_num' min='0' max='100' data-ext='%'",
                        "class"     => 'br_label_style_option br_gradient_option br_range_num',
                        "value"     => '100',
                        "label_for" => '%',
                    ),
                ),
            ),
        );
        return $data;
    }

    public function span_styles($styles, $br_label) {
        return $this->check_gradient($styles, $br_label, 'span_styles');
    }

    public function i1_styles($styles, $br_label) {
        return $this->check_gradient($styles, $br_label, 'i1_styles');
    }

    public function i2_styles($styles, $br_label) {
        return $this->check_gradient($styles, $br_label, 'i2_styles');
    }

    public function i3_styles($styles, $br_label) {
        return $this->check_gradient($styles, $br_label, 'i3_styles');
    }

    public function i4_styles($styles, $br_label) {
        return $this->check_gradient($styles, $br_label, 'i4_styles');
    }

    public function check_gradient( $styles, $br_label, $element = 'span_styles' ) {
        if( empty( $br_label['gradient_use'] ) ) return $styles;
        if( ! empty($br_label['template']) ) {
            list( $template_type, $template_key ) = explode( '-', $br_label['template'] );
            if ( $template_type == 'image' 
                || ! array_key_exists( $template_key, $this->element_gradient_apply[$template_type] ) && in_array($element, $this->disabled_elements)
                || array_key_exists( $template_key, $this->element_gradient_apply[$template_type] ) && ! in_array($element, $this->element_gradient_apply[$template_type][$template_key]) 
            ) return $styles;
        }
        return $this->background_gradient( $styles, $br_label );
    }

    public function background_gradient( $styles, $br_label ) {
        $gradient_size = empty( $br_label['gradient_size'] ) ? 'farthest-corner' : 'farthest-side';
        $orientation_options = array(
            'linear' => array( 
                'prefix'  => 'linear', 
                'options' => "{$br_label['gradient_angle']}deg", 
            ),
            'radial' => array( 
                'prefix'  => 'radial', 
                'options' => "circle $gradient_size {$br_label['gradient_position']}",
            ),
            'elliptical' => array( 
                'prefix'  => 'radial', 
                'options' => "ellipse $gradient_size {$br_label['gradient_position']}",
            ),
        );
        $orientation = $orientation_options[ $br_label['gradient_orientation'] ];
        $options     = $orientation['options'];
        $prefix      = $orientation['prefix'];

        $colors = "{$br_label['gradient_start_color']} {$br_label['gradient_start_position']}%, {$br_label['gradient_end_color']} {$br_label['gradient_end_position']}%";

        return $styles . 
            " background: -webkit-{$prefix}-gradient($options, $colors);
            background: -moz-{$prefix}-gradient($options, $colors);
            background: {$prefix}-gradient($options, $colors);";
    }
}
new BeRocket_products_label_gradients_class(); 
