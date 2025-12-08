<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://powerfulwp.com
 * @since      1.0.0
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/admin
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Scheduling_Deliveries_For_Delivery_Drivers_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Scheduling_Deliveries_For_Delivery_Drivers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Scheduling_Deliveries_For_Delivery_Drivers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen();
		if ( 'edit-shop_order' === $screen->id || 'shop_order' === $screen->post_type ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/scheduling-deliveries-for-delivery-drivers-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Scheduling_Deliveries_For_Delivery_Drivers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Scheduling_Deliveries_For_Delivery_Drivers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen();
		 
		// For HPOS, you can check if 'shop_order' is the post type.
		if ( 'edit-shop_order' === $screen->id || 'shop_order' === $screen->post_type ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scheduling-deliveries-for-delivery-drivers-admin.js', array( 'jquery', 'jquery-ui-datepicker' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'sdfdd_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( $this->plugin_name, 'sdfdd_nonce', array( 'nonce' => esc_js( wp_create_nonce( 'sdfdd-nonce' ) ) ) );
		}
	}


	/**
	 * Updates the delivery date and time for an order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order     The WooCommerce order object.
	 * @param string   $date      The delivery date.
	 * @param string   $from_time The start time for delivery.
	 * @param string   $to_time   The end time for delivery.
	 */
	public function update_order_delivery_date_time( $order, $date, $time ) {
		$order->update_meta_data( '_lddfw_delivery_time', $time );
		$order->update_meta_data( '_lddfw_delivery_date', $date );
	}

	/**
	 * Handles bulk actions to update the delivery date and time for selected orders.
	 *
	 * @param string $redirect_to The URL to redirect to after completing the action.
	 * @param string $action The specific bulk action being performed.
	 * @param array  $post_ids An array of post IDs (order IDs) to which the action applies.
	 * @return string The URL to redirect to after processing the bulk action.
	 */
	public function order_bluk_actions_handle( $redirect_to, $action, $post_ids ) {

		if ( 'sdfdd_update_delivery_time' === $action ) {
			// Verify the nonce for security.
			$nonce_key = 'sdfdd_nonce';
		
			if ( ! isset( $_REQUEST[ $nonce_key ] ) ) {
				die( 'Failed security check' );
			}
 
			$retrieved_nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_key ] ) );
			if ( ! wp_verify_nonce( $retrieved_nonce, basename( __FILE__ ) ) ) {
				die( 'Failed security check' );
			}
			  
			// Retrieve delivery date and time parameters from the request.
			$date       = ( isset( $_GET['_lddfw_delivery_date_sdfdd_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_date_sdfdd_action'] ) ) : '';
			$date2      = ( isset( $_GET['_lddfw_delivery_date_sdfdd_action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_date_sdfdd_action2'] ) ) : '';
			$from_time  = ( isset( $_GET['_lddfw_delivery_from_time_sdfdd_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_from_time_sdfdd_action'] ) ) : '';
			$from_time2 = ( isset( $_GET['_lddfw_delivery_from_time_sdfdd_action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_from_time_sdfdd_action2'] ) ) : '';
			$to_time    = ( isset( $_GET['_lddfw_delivery_to_time_sdfdd_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_to_time_sdfdd_action'] ) ) : '';
			$to_time2   = ( isset( $_GET['_lddfw_delivery_to_time_sdfdd_action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_lddfw_delivery_to_time_sdfdd_action2'] ) ) : '';

			$action_get  = ( isset( $_GET['action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			$action2_get = ( isset( $_GET['action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : '';

			// Determine which set of date and time values to use.
			if ( $action === $action2_get && ( '' !== $date2 || '' !== $from_time2 || '' !== $to_time2 ) ) {
				$from_time = $from_time2;
				$to_time   = $to_time2;
				$date      = $date2;
			}

			$time = $from_time . ' - ' . $to_time;

			// Loop through each selected order ID and update the delivery time.
			foreach ( $post_ids as $post_id ) {
				$order = wc_get_order( $post_id );

				$this->update_order_delivery_date_time( $order, $date, $time );
				$order->save();

				// Update redirect URL with processed orders.
				$processed_ids[] = $post_id;
				$redirect_to     = add_query_arg(
					array(
						'processed_count' => count( $processed_ids ),
						'processed_ids'   => implode( ',', $processed_ids ),
					),
					$redirect_to
				);
			}
		}
		return $redirect_to;
	}


	/**
	 * Adds a custom bulk action for updating the delivery date and time.
	 *
	 * @since 1.0.0
	 * @param array $actions The existing bulk actions.
	 * @return array The modified bulk actions.
	 */
	public function order_bluk_actions_edit( $actions ) {
		$actions['sdfdd_update_delivery_time'] = __( 'Update delivery date & time', 'scheduling-deliveries-for-delivery-drivers' );
		return $actions;
	}



	/**
	 * The function that handles ajax requests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sdfdd_ajax() {

		$sdfdd_service = ( isset( $_POST['sdfdd_service'] ) ) ? sanitize_text_field( wp_unslash( $_POST['sdfdd_service'] ) ) : '';
		$sdfdd_obj_id  = ( isset( $_POST['sdfdd_obj_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['sdfdd_obj_id'] ) ) : '';

		/**
		 * Security check.
		 */
		if ( ! isset( $_POST['sdfdd_wpnonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sdfdd_wpnonce'] ) ), 'sdfdd-nonce' ) )   {
			echo esc_html( __( 'Security Check Failure.', 'scheduling-deliveries-for-delivery-drivers' ) );
			exit;
		}

		/*
		Edit driver service.
		*/
		if ( 'sdfdd_get_bulk_delivery_time' === $sdfdd_service ) {
			echo $this->delivery_date_time_bulk_inputs( $sdfdd_obj_id );
		}

		die();
	}

	public function delivery_date_time_bulk_inputs( $id ) {
		echo sdfdd_get_delivery_date_input( '_lddfw_delivery_date_' . $id, '', __( 'Delivery date', 'scheduling-deliveries-for-delivery-drivers' ) );
		echo sdfdd_get_delivery_time_select( '_lddfw_delivery_from_time_' . $id, '', __( 'From hour', 'scheduling-deliveries-for-delivery-drivers' ) );
		echo sdfdd_get_delivery_time_select( '_lddfw_delivery_to_time_' . $id, '', __( 'To hour', 'scheduling-deliveries-for-delivery-drivers' ) );
	}

	public function setting_tabs( $tabs ) {
		$tabs[] = array(
			'slug'  => 'sdfdd_delivery_date_time',
			'label' => esc_html( __( 'Delivery date & time', 'scheduling-deliveries-for-delivery-drivers' ) ),
			'title' => esc_html( __( 'Delivery date & time', 'scheduling-deliveries-for-delivery-drivers' ) ),
			'url'   => '?page=lddfw-settings&tab=sdfdd_delivery_date_time',
		);

		return $tabs;

	}

	/**
	 * Register the settings for the plugin in WordPress admin.
	 *
	 * @return void
	 */
	public function settings_init() {
		// Get settings tab.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

			 // Register settings for order creation and update syncing.
			 register_setting( 'sdfdd_delivery_date_time', 'sdfdd_sync_third_party_plugin_create' );
			 register_setting( 'sdfdd_delivery_date_time', 'sdfdd_delivery_date' );
			 register_setting( 'sdfdd_delivery_date_time', 'sdfdd_delivery_time' );

		if ( 'sdfdd_delivery_date_time' === $tab ) {

			  // Add the settings section.
			add_settings_section(
				'sdfdd_delivery_date_time_section',
				__( 'Third-Party Delivery Date and Time Settings', 'scheduling-deliveries-for-delivery-drivers' ),
				array( $this, 'sdfdd_delivery_date_time_section_description' ),
				'sdfdd_delivery_date_time' // Page slug
			);

			  // Add settings fields for syncing on order creation and update.
			add_settings_field(
				'sdfdd_sync_third_party_plugin_create',
				__( 'Sync Delivery Date & Time from Third-Party Plugin', 'scheduling-deliveries-for-delivery-drivers' ),
				array( $this, 'sdfdd_sync_third_party_plugin' ),
				'sdfdd_delivery_date_time',
				'sdfdd_delivery_date_time_section'
			);

			// Add the input field for Delivery Date.
			add_settings_field(
				'sdfdd_delivery_date',
				__( 'Meta Key for Third-party Delivery Date', 'scheduling-deliveries-for-delivery-drivers' ),
				array( $this, 'sdfdd_delivery_date_field' ),
				'sdfdd_delivery_date_time',
				'sdfdd_delivery_date_time_section'
			);

			// Add the input field for Delivery Time.
			add_settings_field(
				'sdfdd_delivery_time',
				__( 'Meta Key for Third-party Delivery Time', 'scheduling-deliveries-for-delivery-drivers' ),
				array( $this, 'sdfdd_delivery_time_field' ),
				'sdfdd_delivery_date_time',
				'sdfdd_delivery_date_time_section'
			);

		}
	}

	/**
	 * Description for the Third-Party Delivery Date and Time section.
	 */
	public function sdfdd_delivery_date_time_section_description() {
		echo '<p>' . esc_html__( 'Specify the meta keys for the delivery date and time used by your third-party plugin. If you are unsure of the field names, please contact the author of the third-party plugin for assistance.', 'scheduling-deliveries-for-delivery-drivers' ) . '</p>';
	}
	   // Callback for the Delivery Date field.
	public function sdfdd_delivery_date_field() {
		$delivery_date = get_option( 'sdfdd_delivery_date', '' );
		?>
		<label for="sdfdd_delivery_date">
			<input type="text" id="sdfdd_delivery_date" name="sdfdd_delivery_date" value="<?php echo esc_attr( $delivery_date ); ?>" class="regular-text">
			<br>
		 <?php echo esc_html( __( 'Enter the meta key used by the third-party plugin to store the delivery date. This value will be copied to your plugin’s delivery date field for each order.', 'scheduling-deliveries-for-delivery-drivers' ) ); ?>
		</label>
		<?php
	}

	// Callback for the Delivery Time field.
	public function sdfdd_delivery_time_field() {
		$delivery_time = get_option( 'sdfdd_delivery_time', '' );
		?>
		<label for="sdfdd_delivery_time">
			<input type="text" id="sdfdd_delivery_time" name="sdfdd_delivery_time" value="<?php echo esc_attr( $delivery_time ); ?>" class="regular-text">
			<br>
			<?php echo esc_html( __( 'Enter the meta key used by the third-party plugin to store the delivery time. This value will be copied to your plugin’s delivery time field for each order.', 'scheduling-deliveries-for-delivery-drivers' ) ); ?>
		</label>
		<?php
	}

	 /**
	  * Render checkboxes for syncing with third-party plugin.
	  * Includes options to sync on order creation and order updates.
	  *
	  * @return void
	  */
	public function sdfdd_sync_third_party_plugin() {
		$sync_create = get_option( 'sdfdd_sync_third_party_plugin_create', false );

		?>
		<label for="sdfdd_sync_third_party_plugin_create">
			<input <?php echo checked( $sync_create, '1', false ); ?>
				type="checkbox"
				name="sdfdd_sync_third_party_plugin_create"
				id="sdfdd_sync_third_party_plugin_create"
				value="1">
			<?php echo esc_html( __( 'Enable to automatically sync and copy the delivery date and time from a third-party plugin into this plugin’s fields whenever a new order is created.', 'scheduling-deliveries-for-delivery-drivers' ) ); ?>
		</label>
	   
		<?php
	}

	 /**
	  * Sync third-party delivery meta fields when an order is created.
	  *
	  * @param WC_Order $order WooCommerce order object.
	  * @param array    $data  Array of checkout data.
	  *
	  * @return void
	  */
	public function sdfdd_copy_third_party_meta_on_checkout_order_created( $order ) {
		// Check if syncing on order creation is enabled.
		$sync_create = get_option( 'sdfdd_sync_third_party_plugin_create', false );

		if ( $sync_create ) {
			$this->sdfdd_copy_third_party_meta( $order );
		}
	}

	 /**
	  * Sync third-party delivery meta fields when an order is created.
	  *
	  * @param WC_Order $order WooCommerce order object.
	  * @param array    $data  Array of checkout data.
	  *
	  * @return void
	  */
	public function sdfdd_copy_third_party_meta_on_new_order( $order_id, $order ) {
		// Check if syncing on order creation is enabled.
		$sync_create = get_option( 'sdfdd_sync_third_party_plugin_create', false );

		if ( $sync_create ) {
			$this->sdfdd_copy_third_party_meta( $order );
		}
	}



	/**
	 * Copy third-party delivery meta fields to the plugin's meta fields for WooCommerce orders.
	 * Compatible with HPOS (High-Performance Order Storage).
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return void
	 */
	public function sdfdd_copy_third_party_meta( $order ) {

		// Get the meta keys for delivery date and time from the plugin settings.
		$third_party_delivery_date_key = get_option( 'sdfdd_delivery_date', '' );
		$third_party_delivery_time_key = get_option( 'sdfdd_delivery_time', '' );

		// Fetch the third-party plugin's meta values for delivery date and time.
		$third_party_delivery_date = $order->get_meta( $third_party_delivery_date_key );
		$third_party_delivery_time = $order->get_meta( $third_party_delivery_time_key );

		if ( ! empty( $third_party_delivery_date ) ) {
			// Convert the timestamp to the format 'YYYY-MM-DD'.
			$third_party_delivery_date = date( 'Y-m-d', $third_party_delivery_date );
		}

		// Check if the delivery date has changed before updating.
		$existing_delivery_date = $order->get_meta( '_lddfw_delivery_date' );

		if ( ! empty( $third_party_delivery_date ) && $third_party_delivery_date !== $existing_delivery_date ) {

			$order->update_meta_data( '_lddfw_delivery_date', sanitize_text_field( $third_party_delivery_date ) );

		}

		// Check if the delivery time has changed before updating.
		$existing_delivery_time = $order->get_meta( '_lddfw_delivery_time' );
		if ( ! empty( $third_party_delivery_time ) && $third_party_delivery_time !== $existing_delivery_time ) {
			$order->update_meta_data( '_lddfw_delivery_time', sanitize_text_field( $third_party_delivery_time ) );
		}

		// Do NOT call $order->save() here to avoid triggering the loop.
		// The WooCommerce order will handle the saving automatically during the process.
		// $order->save();
	}




	/**
	 * Columns order
	 *
	 * @param array $columns columns array.
	 * @since 1.0.0
	 * @return array
	 */
	public function sdfdd_orders_list_columns_order( $columns ) {
		$reordered_columns = array();

		// Inserting columns to a specific location.
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				// Inserting after "Status" column.
				$reordered_columns['_lddfw_delivery_date'] = __( 'Scheduled Delivery Date', 'scheduling-deliveries-for-delivery-drivers' );
			}
		}
		return $reordered_columns;
	}

	/**
	 * Print delivery date & time column
	 *
	 * @param string $column column name.
	 * @param int    $post_id post number.
	 * @since 1.0.0
	 */
	public function sdfdd_orders_list_columns( $column, $post_id ) {

		switch ( $column ) {
			case '_lddfw_delivery_date':
				$order = wc_get_order( $post_id );
				echo esc_html( sdfdd_get_formatted_order_delivery_date_time( $order ) );
				break;
		}
	}

	/**
	 * Make the delivery date column sortable.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array Updated sortable columns.
	 */
	public function sdfdd_orders_list_sortable_columns( $columns ) {
		$columns['_lddfw_delivery_date'] = '_lddfw_delivery_date'; // Use meta key for sorting.
		return $columns;
	}

	/**
	 * Modify query arguments for WooCommerce Orders list table to sort by delivery date.
	 *
	 * @param array $args Query arguments for fetching orders.
	 * @return array Modified query arguments.
	 */
	public function sdfdd_sort_by_delivery_date( $args ) {
		// Check if sorting by delivery date is requested.
		if ( isset( $_GET['orderby'] ) && '_lddfw_delivery_date' === $_GET['orderby'] ) {
			  // Determine the sort order direction.
			  $order = isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), array( 'asc', 'desc' ), true ) ? strtoupper( $_GET['order'] ) : 'DESC';

			  // Modify the query arguments to sort by delivery date.
			  $args['meta_key']  = '_lddfw_delivery_date';
			  $args['orderby']   = array(
				  'meta_value' => $order, // Sort by the meta value.
				  'ID'         => 'DESC', // Fallback sorting by order ID.
			  );
			  $args['meta_type'] = 'DATE'; // Ensure the meta type is treated as a date.
		}

		 return $args;
	}
 
}
