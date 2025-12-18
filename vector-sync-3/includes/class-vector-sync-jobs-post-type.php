<?php
/**
 * Jobs Post Type
 *
 * Registers the custom post type used to define individual vector sync
 * jobs.  Each job represents a configuration that synchronises a single
 * content type into a selected vector space on a schedule.  The post
 * type is non-public and only visible in the admin UI.
 */
class Vector_Sync_Jobs_Post_Type {
    /**
     * Register hooks to create the post type.  Called on init.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    /**
     * Registers the custom post type.  The type supports a title but no
     * editor since job settings are handled via meta boxes.  We set
     * show_in_menu to true and specify an icon.  The post type is
     * configured to be excluded from search and public queries.
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => _x( 'Vector Sync Jobs', 'post type general name', 'vector-sync' ),
            'singular_name'      => _x( 'Vector Sync Job', 'post type singular name', 'vector-sync' ),
            'add_new'            => _x( 'Add New', 'vector sync job', 'vector-sync' ),
            'add_new_item'       => __( 'Add New Vector Sync Job', 'vector-sync' ),
            'edit_item'          => __( 'Edit Vector Sync Job', 'vector-sync' ),
            'new_item'           => __( 'New Vector Sync Job', 'vector-sync' ),
            'view_item'          => __( 'View Vector Sync Job', 'vector-sync' ),
            'view_items'         => __( 'View Vector Sync Jobs', 'vector-sync' ),
            'search_items'       => __( 'Search Vector Sync Jobs', 'vector-sync' ),
            'not_found'          => __( 'No vector sync jobs found.', 'vector-sync' ),
            'not_found_in_trash' => __( 'No vector sync jobs found in Trash.', 'vector-sync' ),
            'menu_name'          => __( 'Vector Sync Jobs', 'vector-sync' ),
        );
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            // Hide the post type from the default admin menu.  Jobs are
            // accessed via the Vector Sync top‑level menu instead.
            'show_in_menu'       => false,
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => false,
            'supports'           => array( 'title' ),
            'capability_type'    => 'post',
            'capabilities'       => array(),
            'map_meta_cap'       => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-schedule',
            'has_archive'        => false,
            'exclude_from_search'=> true,
            'publicly_queryable' => false,
            'query_var'          => false,
        );
        register_post_type( 'vector_sync_job', $args );
    }
}