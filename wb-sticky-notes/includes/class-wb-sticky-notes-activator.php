<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wordpress.org/plugins/wb-sticky-notes
 * @since      1.0.0
 *
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/includes
 * @author     Web Builder 143 
 */
class Wb_Sticky_Notes_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );       
        if(is_multisite()) 
        {
            // Get all blogs in the network and activate plugin on each one
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            foreach($blog_ids as $blog_id ) 
            {
                switch_to_blog( $blog_id );
                self::install_tables();
                restore_current_blog();
            }
        }
        else 
        {
            self::install_tables();
        }
	}

	public static function install_tables()
	{
		global $wpdb;
		//install necessary tables
		//creating table for saving notes data================
        $search_query = "SHOW TABLES LIKE %s";
        $charset_collate = $wpdb->get_charset_collate();
        $tb='wb_stn_notes';
        $like = '%' . $wpdb->prefix.$tb.'%';
        $table_name = $wpdb->prefix.$tb;
        if(!$wpdb->get_results($wpdb->prepare($search_query, $like), ARRAY_N))
        {
            // When creating the table for the first time include the new `is_global` column.
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `id_wb_stn_notes` INT NOT NULL AUTO_INCREMENT,
                `content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                `id_user` INT NOT NULL DEFAULT '0',
                `is_global` TINYINT(1) NOT NULL DEFAULT '0',
                `status` INT NOT NULL DEFAULT '0',
                `state` INT NOT NULL DEFAULT '0',
                `z_index` INT NOT NULL DEFAULT '1000',
                `theme` INT NOT NULL DEFAULT '0',
                `font_size` INT NOT NULL DEFAULT '16',
                `font_family` INT NOT NULL DEFAULT '0',
                `width` INT NOT NULL DEFAULT '250',
                `height` INT NOT NULL DEFAULT '250',
                `postop` INT NOT NULL DEFAULT '60',
                `posleft` INT NOT NULL DEFAULT '200',
                PRIMARY KEY (`id_wb_stn_notes`)
            ) DEFAULT CHARSET=utf8;";
            dbDelta($sql);
        }
        // If the table already exists ensure the new `is_global` column has been added. Using
        // SHOW COLUMNS to detect the column avoids errors on installations that have already
        // been migrated.
        $has_global_col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s", 'is_global' ), ARRAY_A );
        if ( empty( $has_global_col ) ) {
            $wpdb->query( "ALTER TABLE `$table_name` ADD COLUMN `is_global` TINYINT(1) NOT NULL DEFAULT '0' AFTER `id_user`" );
        }
        //creating table for saving notes data================
	}
}
