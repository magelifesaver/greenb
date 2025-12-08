<?php
/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Activator {
	/**
	 * Set plugin option.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_set_options() {

			// Create a delivery driver manager role.
			add_role(
				'Delivery_manager',
				'Delivery manager',
				array(
					'read'         => true,
					'edit_posts'   => false,
					'delete_posts' => false,
				)
			);

			// Create manager page for the first activation.
			pwddm_create_manager_panel_page();
	}

	/**
	 * Activate plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {
			// Run the code for all sites in a Multisite network.
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->pwddm_set_options();
			}
				restore_current_blog();
		} else {
				$this->pwddm_set_options();
		}
	}

}
