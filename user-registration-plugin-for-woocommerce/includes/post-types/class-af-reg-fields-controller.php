<?php

defined( 'ABSPATH' ) || exit;

class AF_REG_Fields_Controller {

	public $ID;

	public $post;

	public function __construct( $id = 0 ) {
		$this->ID   = $id;
		$this->post = get_post( $this->ID );
	}

	public function get_id() {
		return $this->ID;
	}

	public function get_status() {
		return get_post_meta($this->ID, 'afreg_field_status', true );
	}

	public function get_indexed_meta_keys() {

		$indexed_meta_keys = array();

		$indexed_meta_keys['field_type']           = 'afreg_field_type';
		$indexed_meta_keys['options']              = 'afreg_field_option';
		$indexed_meta_keys['required']             = 'afreg_field_required';
		$indexed_meta_keys['show_in_registration'] = 'afreg_field_show_in_registration_form';
		$indexed_meta_keys['show_in_myaccount']    = 'afreg_field_show_in_my_account';
		$indexed_meta_keys['readonly']             = 'afreg_field_read_only';
		$indexed_meta_keys['show_in_order']        = 'afreg_field_order_details';
		$indexed_meta_keys['width']                = 'afreg_field_width';
		$indexed_meta_keys['placeholder']          = 'afreg_field_placeholder';
		$indexed_meta_keys['description']          = 'afreg_field_description';
		$indexed_meta_keys['css']                  = 'afreg_field_css';
		$indexed_meta_keys['file_size']            = 'afreg_field_file_size';
		$indexed_meta_keys['file_type']            = 'afreg_field_file_type';
		$indexed_meta_keys['user_roles']           = 'afreg_field_user_roles';
		$indexed_meta_keys['is_dependable']        = 'afreg_is_dependable';
		$indexed_meta_keys['heading_type']         = 'afreg_field_heading_type';
		$indexed_meta_keys['description']          = 'afreg_field_description_field';
		$indexed_meta_keys['field_status']         = 'afreg_field_status';

		return $indexed_meta_keys;
	}
}
