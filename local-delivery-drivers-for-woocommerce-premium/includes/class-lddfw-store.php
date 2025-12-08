<?php
/**
 * Website store class
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

/**
 * Website store class.
 *
 * All store functions.
 *
 * @link  http://www.powerfulwp.com
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Store {

	/**
	 * Function that return vendor role.
	 *
	 * @param int $vendor vendor user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function lddfw_vendor_role__premium_only( $vendor ) {
		$result = '';
		switch ( $vendor ) {
			case 'dokan':
				$result = 'seller';
				break;
			case 'wcmp':
				$result = 'dc_vendor';
				break;
			case 'wcfm':
				$result = 'wcfm_vendor';
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Function that return vendor order meta.
	 *
	 * @param int $vendor vendor user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function lddfw_vendor_order_meta__premium_only( $vendor ) {
		$result = '';
		switch ( $vendor ) {
			case 'dokan':
				$result = '_dokan_vendor_id';
				break;
			case 'wcmp':
				$result = '_vendor_id';
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Function that return driver seller id.
	 *
	 * @param int $driver_id driver user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function lddfw_get_driver_seller( $driver_id ) {
		$seller_id = '';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( has_filter( 'lddfw_get_driver_seller' ) ) {
					$seller_id = apply_filters( 'lddfw_get_driver_seller', $driver_id );
				}
			}
		}
		return $seller_id;
	}

	/**
	 * Function that return order seller id.
	 *
	 * @since 1.6.0
	 * @param object $order order.
	 * @return string
	 */
	public function lddfw_order_seller( $order, $all_sellers = false ) {
		$result = '';
		$array  = array();
		global $wpdb;
		$order_id = $order->get_id();
		switch ( LDDFW_MULTIVENDOR ) {
			case 'dokan':
				if ( $all_sellers && $order->get_meta( 'has_sub_order' ) ) {
					$sub_orders = dokan_get_suborder_ids_by( $order_id );
					if ( ! empty( $sub_orders ) ) {
						foreach ( $sub_orders as $sub_order ) {
							$child_order = wc_get_order( $sub_order );
							$vendor_id   = $child_order->get_meta( '_dokan_vendor_id' );
							if ( ! in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
								$array[ $vendor_id ] = $vendor_id;
							}
						}
						$result = $array;
					}
				} else {
					// Return seller id.
					$result = $order->get_meta( '_dokan_vendor_id' );
				}
				break;
			case 'wcmp':
				if ( $all_sellers && $order->get_meta( 'has_wcmp_sub_order' ) ) {
					$sub_orders = get_wcmp_suborders( $order_id, false, false );
					if ( $sub_orders ) {
						foreach ( $sub_orders as $sub_order ) {
							$child_order = wc_get_order( $sub_order );
							$vendor_id   = $child_order->get_meta( '_vendor_id' );
							if ( ! in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
								$array[ $vendor_id ] = $vendor_id;
							}
						}
						$result = $array;
					}
				} else {
					// Return seller id.
					$result = $order->get_meta( '_vendor_id' );
				}
				break;
			case 'wcfm':
				$sellers = $wpdb->get_results(
					$wpdb->prepare(
						'select vendor_id from ' . $wpdb->prefix . 'wcfm_marketplace_orders where order_id=%s',
						array( $order_id )
					)
				);
				if ( ! empty( $sellers ) ) {
					if ( $all_sellers ) {
						foreach ( $sellers as $seller ) {
							$seller_id = $seller->vendor_id;
							if ( ! in_array( $seller_id, $array ) ) {
								$array[ $seller_id ] = $seller_id;
							}
						}
						// Return sellers array.
						$result = $array;
					} else {
						// Return first seller id.
						$result = $sellers[0]->vendor_id;
					}
				}
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Pickup option.
	 *
	 * @param object $order order object.
	 * @return statement
	 */
	public function get_pickup_type( $order ) {
		/**
		 * Pickup option types:
		 * store - store/vendor pickup location.
		 * customer - customer pickup location.
		 * post - saved pickup location.
		 */
		$result = 'store';
		// Pickup Filter.
		if ( has_filter( 'lddfw_pickup_type' ) ) {
			$result = apply_filters( 'lddfw_pickup_type', $result, $order );
		}
		return $result;
	}

	/**
	 * Pickup phone.
	 *
	 * @param object $order order object.
	 * @param object $seller_id seller number.
	 * @return statement
	 */
	public function get_pickup_phone( $order, $seller_id ) {
		$phone = $this->lddfw_store_phone( $order, $seller_id );
		// Pickup phone filter.
		if ( has_filter( 'lddfw_pickup_phone' ) ) {
			$phone = apply_filters( 'lddfw_pickup_phone', $phone, $order );
		}
		return $phone;
	}

	/**
	 * Pickup address.
	 *
	 * @since 1.0.0
	 * @param string $format address format.
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function lddfw_pickup_address( $format, $order, $seller_id ) {
		$address = $this->lddfw_store_address( $format );
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '' !== LDDFW_MULTIVENDOR ) {
					// store address.
					if ( '' !== $seller_id ) {
						if ( 'dokan' === LDDFW_MULTIVENDOR ) {
							if ( function_exists( 'dokan_get_seller_address' ) ) {
								$array = dokan_get_seller_address( $seller_id, true );
								if ( is_array( $array ) ) {
									$store_address_1 = $array['street_1'];
									$store_address_2 = $array['street_2'];
									$store_city      = $array['city'];
									$store_postcode  = $array['zip'];
									$store_country   = $array['country'];
									$store_state     = $array['state'];
									if ( '' !== $array['street_1'] ) {
										$address = lddfw_format_address( $format, $array );
									}
								}
							}
						}
						if ( 'wcmp' === LDDFW_MULTIVENDOR ) {
							$array['street_1'] = get_user_meta( $seller_id, '_vendor_address_1', true );
							$array['street_2'] = get_user_meta( $seller_id, '_vendor_address_2', true );
							$array['city']     = get_user_meta( $seller_id, '_vendor_city', true );
							$array['zip']      = get_user_meta( $seller_id, '_vendor_postcode', true );
							$array['country']  = get_user_meta( $seller_id, '_vendor_country', true );
							$array['state']    = get_user_meta( $seller_id, '_vendor_state', true );
							if ( '' !== $array['street_1'] ) {
								$address = lddfw_format_address( $format, $array );
							}
						}
						if ( 'wcfm' === LDDFW_MULTIVENDOR ) {
							$branch_address = '';
							if ( ! empty( $order ) ) {
								$branch_address = $order->get_meta('_wcfmmp_vendor_' . $seller_id . '_ship_from');
								if ( ! $branch_address ) {
									$branch_address = $order->get_meta('_wcfmmp_vendor_order_branch_address');
								}
								if ( ! $branch_address ) {
									$branch_address = $order->get_meta('_wcfm_pickup_location');
								}
							}
							
							if ( ! empty( $branch_address ) ) {
								$array['street_1'] = $branch_address;
								$address = lddfw_format_address( $format, $array );
							} else {
								// Address.
								$vendor_data       = get_user_meta( $seller_id, 'wcfmmp_profile_settings', true );
								$array['street_1'] = ! empty( $vendor_data['address']['street_1'] ) ? $vendor_data['address']['street_1'] : '';
								$array['street_2'] = ! empty( $vendor_data['address']['street_2'] ) ? $vendor_data['address']['street_2'] : '';
								$array['city']     = ! empty( $vendor_data['address']['city'] ) ? $vendor_data['address']['city'] : '';
								$array['zip']      = ! empty( $vendor_data['address']['zip'] ) ? $vendor_data['address']['zip'] : '';
								$array['country']  = ! empty( $vendor_data['address']['country'] ) ? WC()->countries->countries[ $vendor_data['address']['country'] ] : '';
								$array['state']    = ! empty( $vendor_data['address']['state'] ) ? $vendor_data['address']['state'] : '';
								if ( '' !== $array['street_1'] ) {
									$address = lddfw_format_address( $format, $array );
								}
							}
						}
					}
				}

				// Pickup Filter.
				if ( has_filter( 'lddfw_pickup_location' ) ) {
					return apply_filters( 'lddfw_pickup_location', $address, $format, $order, $seller_id );
				}
			}
		}
		return $address;
	}

	/**
	 * Store phone.
	 *
	 * @since 1.6.0
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function lddfw_store_phone( $order, $seller_id ) {
		$store_phone = get_option( 'lddfw_dispatch_phone_number', '' );
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '' !== LDDFW_MULTIVENDOR ) {
					if ( '' !== $seller_id ) {
						if ( 'dokan' === LDDFW_MULTIVENDOR ) {
							$vendor     = dokan()->vendor->get( $seller_id );
							$shop_phone = $vendor->get_phone();
							if ( '' !== $shop_phone ) {
								return $shop_phone;
							}
						}
						if ( 'wcmp' === LDDFW_MULTIVENDOR ) {
							$shop_phone = get_user_meta( $seller_id, '_vendor_phone', true );
							if ( '' !== $shop_phone ) {
								return $shop_phone;
							}
						}
						if ( 'wcfm' === LDDFW_MULTIVENDOR ) {
							// Address.
							$vendor_data = get_user_meta( $seller_id, 'wcfmmp_profile_settings', true );
							$shop_phone  = isset( $vendor_data['phone'] ) ? $vendor_data['phone'] : '';
							if ( '' !== $shop_phone ) {
								return $shop_phone;
							}
						}
					}
				}
			}
		}
		return $store_phone;
	}


	/**
	 * Store name.
	 *
	 * @since 1.6.0
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function lddfw_store_name__premium_only( $order, $seller_id ) {
		$store_name = get_bloginfo( 'name' );
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '' !== LDDFW_MULTIVENDOR ) {
					if ( '' !== $seller_id ) {
						if ( 'dokan' === LDDFW_MULTIVENDOR ) {
							$vendor    = dokan()->vendor->get( $seller_id );
							$shop_name = $vendor->get_shop_name();
							if ( '' !== $shop_name ) {
								return $shop_name;
							}
						}
						if ( 'wcmp' === LDDFW_MULTIVENDOR ) {
							$shop_name = get_user_meta( $seller_id, '_vendor_page_title', true );
							if ( '' !== $shop_name ) {
								return $shop_name;
							}
						}
						if ( 'wcfm' === LDDFW_MULTIVENDOR ) {
							// Address.
							$vendor_data = get_user_meta( $seller_id, 'wcfmmp_profile_settings', true );
							$shop_name   = isset( $vendor_data['store_name'] ) ? $vendor_data['store_name'] : '';
							if ( '' !== $shop_name ) {
								return $shop_name;
							}
						}
					}
				}
			}
		}
		return $store_name;
	}

	/**
	 * Store email.
	 *
	 * @since 1.7.4
	 * @param int $seller_id seller id.
	 * @return string
	 */
	public function lddfw_store_email__premium_only( $seller_id ) {

		$store_email = '';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '' !== LDDFW_MULTIVENDOR ) {

					if ( '' !== $seller_id ) {

						// Get user email.
						$user_info   = get_userdata( $seller_id );
						$store_email = ( ! empty( $user_info->user_email ) ) ? $user_info->user_email : '';

						if ( 'dokan' === LDDFW_MULTIVENDOR ) {
							if ( '' !== $store_email ) {
								return $store_email;
							}
						}

						if ( 'wcmp' === LDDFW_MULTIVENDOR ) {
							if ( '' !== $store_email ) {
								return $store_email;
							}
						}

						if ( 'wcfm' === LDDFW_MULTIVENDOR ) {
							// Store email.
							$vendor_data = get_user_meta( $seller_id, 'wcfmmp_profile_settings', true );
							$store_email = ! empty( $vendor_data['store_email'] ) ? esc_attr( $vendor_data['store_email'] ) : $store_email;
							if ( '' !== $store_email ) {
								return $store_email;
							}
						}
					}
				}
			}
		}
		return $store_email;
	}

	/**
	 * Store address.
	 *
	 * @since 1.0.0
	 * @param string $format address format.
	 * @return string
	 */
	public function lddfw_store_address( $format ) {

		// main store address.
		$store_address     = get_option( 'woocommerce_store_address', '' );
		$store_address_2   = get_option( 'woocommerce_store_address_2', '' );
		$store_city        = get_option( 'woocommerce_store_city', '' );
		$store_postcode    = get_option( 'woocommerce_store_postcode', '' );
		$store_raw_country = get_option( 'woocommerce_default_country', '' );

		$split_country = explode( ':', $store_raw_country );
		if ( false === strpos( $store_raw_country, ':' ) ) {
			$store_country = $split_country[0];
			$store_state   = '';
		} else {
			$store_country = $split_country[0];
			$store_state   = $split_country[1];
		}
		if ( '' !== $store_country ) {
			$store_country = WC()->countries->countries[ $store_country ];
		}
		$array = array(
			'street_1' => $store_address,
			'street_2' => $store_address_2,
			'city'     => $store_city,
			'zip'      => $store_postcode,
			'country'  => $store_country,
			'state'    => $store_state,
		);
		return lddfw_format_address( $format, $array );
	}

	/**
	 * Get unit system by country.
	 *
	 * @since 1.1.2
	 * @return string
	 */
	public function lddfw_country_unit_system__premium_only() {
			$store_raw_country = get_option( 'woocommerce_default_country', '' );
			$split_country     = explode( ':', $store_raw_country );
		if ( false === strpos( $store_raw_country, ':' ) ) {
			$store_country = $split_country[0];
		} else {
			$store_country = $split_country[0];
		}
			$array = array( 'gb', 'us', 'lr', 'mm' );
		if ( in_array( strtolower( $store_country ), $array, true ) ) {
			return 'imperial';
		} else {
			return 'metric';
		}
	}
}
