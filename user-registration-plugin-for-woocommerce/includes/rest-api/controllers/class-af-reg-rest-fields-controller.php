<?php
/**
 * REST API registration fields controller
 *
 * Handles requests to the /af_reg/ endpoint.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Request a Field Fields controller class.
 *
 * @extends WC_REST_CRUD_Controller
 */
class AF_REG_Rest_Fields_Controller extends WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'af_reg';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'afreg_fields';


	/**
	 * Role based pricing actions.
	 */
	public function __construct() {
	}

	/**
	 * Register the routes for role based pricing.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fields' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_field' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'addify_reg' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_field' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_field' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_field' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'addify_reg' ),
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_field_data( $object, $request ) {

		$field_data = array();

		$field_data['field_id']          = $object->ID;
		$field_data['title']             = $object->post->post_title;
		$field_data['date_created']      = $object->post->post_date ;
		$field_data['date_created_gmt']  = $object->post->post_date_gmt;
		$field_data['date_modified']     = $object->post->post_modified;
		$field_data['date_modified_gmt'] = $object->post->post_modified_gmt;
		$field_data['post_status']       = $object->post->post_status;
		$field_data['sort_order']        = $object->post->menu_order;

		$field_data['field_type']           = get_post_meta( $object->ID, 'afreg_field_type', true );
		$field_data['options']              = get_post_meta( $object->ID, 'afreg_field_option', true );
		$field_data['required']             = get_post_meta( $object->ID, 'afreg_field_required', true );
		$field_data['show_in_registration'] = get_post_meta( $object->ID, 'afreg_field_show_in_registration_form', true );
		$field_data['show_in_myaccount']    = get_post_meta( $object->ID, 'afreg_field_show_in_my_account', true );
		$field_data['readonly']             = get_post_meta( $object->ID, 'afreg_field_read_only', true );
		$field_data['show_in_order']        = get_post_meta( $object->ID, 'afreg_field_order_details', true );
		$field_data['width']                = get_post_meta( $object->ID, 'afreg_field_width', true );
		$field_data['placeholder']          = get_post_meta( $object->ID, 'afreg_field_placeholder', true );
		$field_data['description']          = get_post_meta( $object->ID, 'afreg_field_description', true );
		$field_data['css']                  = get_post_meta( $object->ID, 'afreg_field_css', true );
		$field_data['file_size']            = get_post_meta( $object->ID, 'afreg_field_file_size', true );
		$field_data['file_type']            = get_post_meta( $object->ID, 'afreg_field_file_type', true );
		$field_data['user_roles']           = get_post_meta( $object->ID, 'afreg_field_user_roles', true );
		$field_data['is_dependable']        = get_post_meta( $object->ID, 'afreg_is_dependable', true );
		$field_data['heading_type']         = get_post_meta( $object->ID, 'afreg_field_heading_type', true );
		$field_data['description']          = get_post_meta( $object->ID, 'afreg_field_description_field', true );
		$field_data['field_status']         = $object->get_status();

		$field_data['options']    = maybe_unserialize( $field_data['options'] );
		$field_data['user_roles'] = maybe_unserialize( $field_data['user_roles'] );
		
		return $field_data;
	}

	public function get_fields( $request ) {

		$field_ids = $this->get_field_ids($request);

		$fields_data = array();

		foreach ( $field_ids as $field_id ) {

			$object = $this->get_object( $field_id );

			if ( $object && 0 !== $object->get_id() && ! wc_rest_check_post_permissions( $this->post_type, 'read', $object->get_id() ) ) {
				continue;
			}

			$fields_data[ $field_id ] = $this->get_field_data( $object, $request );
		}

		return rest_ensure_response( $fields_data );
	}

	public function create_field( $request ) {

		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'addify_reg' ), $this->post_type ), array( 'status' => 400 ) );
		}
		
		$object = $this->save_field( $request );

		$request->set_param( 'context', 'edit' );
		$response_data = $this->get_field_data( $object, $request );
		$response      = rest_ensure_response( $response_data );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

	public function get_field( $request ) {

		$field_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

		if ( $field_id && 'afreg_fields' !== get_post_type( $field_id ) ) {

			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_invalid_field_id', sprintf( __( 'Invalid Field ID %s.', 'addify_reg' ), $field_id ), array( 'status' => 400 ) );
		}

		$object = $this->get_object( $field_id );

		if ( $object && is_a( $object->post, 'WP_Post' ) ) {
			$data     = $this->get_field_data( $object, $request);
			$response = rest_ensure_response( $data );

			return $response;
		}       
	}

	public function update_field( $request ) {

		$field_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

		if ( $field_id && 'afreg_fields' !== get_post_type( $field_id ) ) {

			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_invalid_field_id', sprintf( __( 'Invalid Field ID %s.', 'addify_reg' ), $field_id ), array( 'status' => 400 ) );
		}

		if ( $field_id ) {

			$object = $this->save_field( $request );

			$request->set_param( 'context', 'edit' );
			$response_data = $this->get_field_data( $object, $request );
			$response      = rest_ensure_response( $response_data );

			return $response;
		}
	}

	public function delete_field( $request ) {

		$field_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

		if ( $field_id && 'afreg_fields' !== get_post_type( $field_id ) ) {

			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_invalid_field_id', sprintf( __( 'Invalid Field ID %s.', 'addify_reg' ), $field_id ), array( 'status' => 400 ) );
		}

		if ( $field_id ) {

			$force  = isset( $request['force'] ) ? (bool) $request['force'] : false;
			$object = $this->get_object( (int) $request['id'] );
			$result = false;

			if ( ! $object || 0 === $object->get_id() ) {
				return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'addify_reg' ), array( 'status' => 404 ) );
			}

			$supports_trash = true;

			if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $object->get_id() ) ) {
				/* translators: %s: post type */
				return new WP_Error( "woocommerce_rest_user_cannot_delete_{$this->post_type}", sprintf( __( 'Sorry, you are not allowed to delete %s.', 'addify_reg' ), $this->post_type ), array( 'status' => rest_authorization_required_code() ) );
			}

			$request->set_param( 'context', 'edit' );

			$data = $this->get_field_data( $object, $request );

			$result = false;

			if ( $force ) {
				$result = wp_trash_post( $field_id );
			} else {
				$result = wp_delete_post( $field_id, $force );
			}
			
			if ( ! $result ) {
				/* translators: %s: post type */
				return new WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'addify_reg' ), $this->post_type ), array( 'status' => 500 ) );
			}

			return rest_ensure_response( $data );
		}
	}

	public function save_field( $request ) {

		try {

			if ( empty( $request['id'] ) ) {

				$args = array(
					'post_type'    => 'afreg_fields',
					'post_status'  => 'publish',
					'post_title'   => isset( $request['title'] ) ? sanitize_text_field( $request['title'] ) : '',
					'post_content' => isset( $request['content'] ) ? sanitize_text_field( $request['content'] ) : '',
					'menu_order'   => isset( $request['sort_order'] ) ? sanitize_text_field( $request['sort_order'] ) : '',
				);

				$post_id = wp_insert_post( $args );
				$object  = $this->get_object( $post_id );

			} else {

				$object = $this->get_object( $request['id'] );
			}

			$field_id = $object->get_id();

			$field_meta_keys = $object->get_indexed_meta_keys();

			if ( !empty( $field_meta_keys ) ) {

				foreach ( $field_meta_keys as $request_index => $meta_key ) {

					if ( isset( $request[ $request_index ] ) ) {

						$data = $request[ $request_index ];

						if ( is_array( $data ) ) {
							$data = serialize( $data );
						}

						update_post_meta( $field_id, $meta_key, $data );
					}
				}
			}

			return $object;

		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	public function get_field_ids( $request ) {

		$posts_per_page = isset( $request['posts_per_page'] ) ? intval( $request['posts_per_page'] ) : 10;

		$args = array(
			'post_type'      => 'afreg_fields',
			'post_status'    => 'publish',
			'paged'          => isset($request['paged']) ? $request['paged'] : 1,
			'posts_per_page' => $posts_per_page,
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}

	protected function get_object( $field_id ) {
		
		return new AF_REG_Fields_Controller($field_id);
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Product role based pricing rules',
			'type'       => 'object',
			'properties' => array(
				'id'                   => array(
					'description' => __( 'Unique identifier for the resource.', 'addify_reg' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'                => array(
					'description' => __( 'Rule title.', 'addify_reg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'         => array(
					'description' => __( "The date the field was created, in the site's timezone.", 'addify_reg' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'     => array(
					'description' => __( 'The date the field was created, as GMT.', 'addify_reg' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'        => array(
					'description' => __( "The date the field was last modified, in the site's timezone.", 'addify_reg' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'    => array(
					'description' => __( 'The date the field was last modified, as GMT.', 'addify_reg' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'post_status'          => array(
					'description' => __( 'Rule status (post status).', 'addify_reg' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'sort_order'           => array(
					'description' => __( 'Sort Order of field.', 'addify_reg' ),
					'type'        => 'number',
					'default'     => '0',
					'context'     => array( 'view', 'edit' ),
				),
				'field_type'           => array(
					'description' => __( 'Field Type.', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'text',
					'context'     => array( 'view', 'edit' ),
				),
				'options'              => array(
					'description' => __( 'Field options for select, multi select, radio, multi check boxes.', 'addify_reg' ),
					'type'        => 'array',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'required'             => array(
					'description' => __( 'Field is required or not. on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'show_in_registration' => array(
					'description' => __( 'Either to show field in registration. on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'show_in_myaccount'    => array(
					'description' => __( 'Either to show field in my account. on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'readonly'             => array(
					'description' => __( 'Field is read only once the user has been registered. on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'show_in_order'        => array(
					'description' => __( 'Show field data in order. on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'width'                => array(
					'description' => __( 'Width of field. half/full', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'full',
					'context'     => array( 'view', 'edit' ),
				),
				'placeholder'          => array(
					'description' => __( 'Placeholder of field.', 'addify_reg' ),
					'type'        => 'text',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'description'          => array(
					'description' => __( 'Description of field.', 'addify_reg' ),
					'type'        => 'text',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'css'                  => array(
					'description' => __( 'Custom CSS for field.', 'addify_reg' ),
					'type'        => 'text',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'file_size'            => array(
					'description' => __( 'Size of file allowed for file upload field.', 'addify_reg' ),
					'type'        => 'number',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'file_type'            => array(
					'description' => __( 'Type(s) of file allowed for file upload field.', 'addify_reg' ),
					'type'        => 'text',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'is_dependable'        => array(
					'description' => __( 'Is field dependable on user roles? on/off', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'off',
					'context'     => array( 'view', 'edit' ),
				),
				'user_roles'           => array(
					'description' => __( 'User roles of field.', 'addify_reg' ),
					'type'        => 'array',
					'default'     => array(),
					'context'     => array( 'view', 'edit' ),
				),
				'heading_type'         => array(
					'description' => __( 'Heading type for heading fields. H1, H2, H3, H4, H5, H6', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'h1',
					'context'     => array( 'view', 'edit' ),
				),
				'field_status'         => array(
					'description' => __( 'Status of field. enable/disable', 'addify_reg' ),
					'type'        => 'text',
					'default'     => 'enable',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
		return $this->add_additional_fields_schema( $schema );
	}
}
