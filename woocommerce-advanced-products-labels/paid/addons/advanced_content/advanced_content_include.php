<?php
class BeRocket_products_label_advanced_content_class {
    function __construct() {
        $this->load_scripts();
        add_action( 'berocket_apl_load_admin_edit_scripts', array( $this, 'load_admin_scripts' ), 15 );

        add_filter( 'brfr_data_berocket_advanced_label_editor', array( __CLASS__, 'berocket_advanced_content_fields' ) );
        add_filter( 'berocket_apl_label_show_span_class', array( $this, 'span_class' ), 10, 2 );
        add_filter( 'berocket_apl_label_show_span_extra', array( $this, 'span_extra' ), 10, 2 );
    }

    public function load_scripts() {
        wp_enqueue_style( 'berocket_advanced_content_css', plugins_url( 'css/frontend.css', __FILE__ ) );

        wp_enqueue_script( 'berocket_advanced_content', plugins_url( 'js/frontend.js', __FILE__ ), array( 'jquery' ) );
    }

    public function load_admin_scripts() {
        wp_enqueue_style( 'berocket_advanced_content_admin_css', plugins_url( 'css/admin.css', __FILE__ ) );

        wp_enqueue_script( 'berocket_advanced_content_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
    }

    public static function berocket_advanced_content_fields( $data ) {
        $data['General']['label_link'] = array(
            "label"    => __('Link', 'BeRocket_products_label_domain'),
            "items"    => array(
                'label_link' => array(
                    "type"     => "text",
                    "class"    => 'berocket_label_text berocket_label_url',
                    "name"     => "label_link",
                    "value"    => '',
                ),
                'label_target' => array(
                    "type"     => "checkbox",
                    "label_for" => __( 'Open in new tab', 'BeRocket_products_label_domain' ),
                    "name"     => "label_target",
                    "value"    => "1",
                    "selected" => false,
                ),
            ),
        );

        return $data;
    }

    public function span_class( $class, $br_label ) {
        if ( ! empty( $br_label['label_link'] ) ) {
            return $class ? $class . ' br_alabel_linked' : 'br_alabel_linked';
        }

        return $class;
    }

    public function span_extra( $href_data, $br_label ) {
        if ( ! empty( $br_label['label_link'] ) ) {
            $label_link   = $br_label['label_link'];
            $label_target = empty( $br_label['label_target'] ) ? '_self' : '_blank';
            $href_data    = ' data-link="' . esc_url( $label_link ) . '" data-target="' . $label_target .'" ';
        }

        return $href_data;
    }

    public function get_option() {
        $BeRocket_products_label = BeRocket_products_label::getInstance();
        return $BeRocket_products_label->get_option();
    }
}
new BeRocket_products_label_advanced_content_class(); 
