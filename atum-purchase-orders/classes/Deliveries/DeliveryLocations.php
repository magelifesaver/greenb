<?php
/**
 * Delivery Locations class
 *
 * @package     AtumPO\Deliveries
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since 0.9.15
 */

namespace AtumPO\Deliveries;

defined( 'ABSPATH' ) || exit;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;


class DeliveryLocations {

	/**
	 * The singleton instance holder
	 *
	 * @var DeliveryLocations
	 */
	private static $instance;

	/**
	 * Locations list.
	 *
	 * @var array
	 */
	private static $locations = [];

	/**
	 * Locations extra fields.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * DuplicatePO singleton constructor.
	 *
	 * @since 0.9.15
	 */
	private function __construct() {

		$this->init_fields();

		// Read ATUM locations.
		add_action( 'atum/after_init', array( $this, 'read_locations' ) );

		// Add the fields to the ATUM locations taxonomy, using our callback function.
		add_action( AtumGlobals::PRODUCT_LOCATION_TAXONOMY . '_edit_form_fields', array( $this, 'display_extra_fields' ), 10, 2 );

		// Save the changes made on the ATUM locations taxonomy, using our callback function.
		add_action( 'edited_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY, array( $this, 'save_extra_fields' ), 10, 2 );

	}

	/**
	 * Initialize fields.
	 *
	 * @since 0.9.15
	 */
	private function init_fields() {

		$this->fields = [
			'address'     => [
				'name'        => __( 'Address', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'The address (street, number, floor, door, etc) of the location.', ATUM_PO_TEXT_DOMAIN ),
			],
			'address2'    => [
				'name'        => __( 'Address 2', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'Additional info for the address.', ATUM_PO_TEXT_DOMAIN ),
			],
			'city'        => [
				'name'        => __( 'City', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'The city/town for the location.', ATUM_PO_TEXT_DOMAIN ),
			],
			'state'       => [
				'name'        => __( 'State', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'The state/region for the location.', ATUM_PO_TEXT_DOMAIN ),
			],
			'postal_code' => [
				'name'        => __( 'Postal Code', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'The Zip code/postal code for the location.', ATUM_PO_TEXT_DOMAIN ),
			],
			'country'     => [
				'name'        => __( 'Country', ATUM_PO_TEXT_DOMAIN ),
				'description' => __( 'The country where the location is located.', ATUM_PO_TEXT_DOMAIN ),
			],
		];

	}

	/**
	 * Read ATUM locations.
	 *
	 * @since 0.9.15
	 */
	public function read_locations() {

		$locations = get_terms( array(
			'taxonomy'   => AtumGlobals::PRODUCT_LOCATION_TAXONOMY,
			'hide_empty' => FALSE,
		) );

		if ( ! is_wp_error( $locations ) && ! empty( $locations ) ) {
			foreach ( $locations as $location ) {

				$location_data = array(
					'id'   => $location->term_id,
					'name' => $location->name,
				);

				foreach ( $this->fields as $field_id => $field ) {
					$location_data[ $field_id ] = get_term_meta( $location->term_id, $field_id, TRUE );
				}
				self::$locations[] = $location_data;
			}
		}
	}

	/**
	 * Display extra fields for ATUM Locations.
	 *
	 * @since 0.9.15
	 *
	 * @param \WP_Term $location
	 */
	public function display_extra_fields( $location ) {

		foreach ( $this->fields as $field_id => $field ) {

			$field_value = get_term_meta( $location->term_id, $field_id, TRUE );
			?>

			<tr class="form-field">
				<th scope="row">
					<label for="term_meta_<?php echo esc_attr( $field_id ) ?>">
						<?php AtumHelpers::atum_field_label(); ?>
						<?php echo esc_html( $field['name'] ); ?>
					</label>
				</th>
				<td>
					<?php if ( 'country' === $field_id ) : ?>
						<select id="term_meta_<?php echo esc_attr( $field_id ) ?>" name="term_meta[<?php echo esc_attr( $field_id ) ?>]">
							<option value=""></option>
							<?php $country_obj = new \WC_Countries(); ?>
							<?php foreach ( $country_obj->get_countries() as $key => $value ) : ?>
								<option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, $field_value ) ?>><?php echo esc_html( $value ) ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="text" name="term_meta[<?php echo esc_attr( $field_id ) ?>]" id="term_meta[<?php echo esc_attr( $field_id ) ?>]" size="40" value="<?php echo esc_attr( $field_value ) ?>">
					<?php endif; ?>
					<p class="description"><?php echo esc_html( $field['description'] ) ?></p>
				</td>
			</tr>

			<?php
		}
	}

	/**
	 * Save extra fields for ATUM Locations.
	 *
	 * @since 0.9.15
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	public function save_extra_fields( $term_id, $tt_id ) {

		foreach ( $this->fields as $field_id => $field ) {

			if ( isset( $_POST, $_POST['term_meta'], $_POST['term_meta'][ $field_id ] ) ) {
				$field_value = esc_attr( stripslashes( $_POST['term_meta'][ $field_id ] ) );
				update_term_meta( $term_id, $field_id, $field_value );
			}

		}
	}

	/**
	 * Getter for the $locations attribute.
	 *
	 * @since 0.9.15
	 *
	 * @return array
	 */
	public static function get_locations() {
		return self::$locations;
	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return DeliveryLocations instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
