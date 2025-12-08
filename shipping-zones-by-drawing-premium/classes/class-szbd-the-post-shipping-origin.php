<?php
if (!defined('ABSPATH'))
  {
  exit;
  }
class Sbdorigins_Post
  {
  private static $stop = false;
  public function __construct()
    {
    add_action('init', array(
      $this,
      'register_post_szbdorigins'
    ));
    add_action('registered_post_type', array(
      $this,
      'eval_caps2'
    ), 99, 2);
    
   

    }
   
  public function register_post_szbdorigins()
    {
    $labels2 = array(
      'name' => __('Shipping Zones by Drawing Origins', 'szbd'),
      'menu_name' => __('Shipping Zones by Drawing Origins', 'szbd'),
      'name_admin_bar' => __('Shipping Zone Origins', 'szbd'),
      'all_items' => __('Shipping Zones by Drawing Origins', 'szbd'),
      'singular_name' => __('Origin List', 'szbd'),
      'add_new' => __('New Shipping Origin', 'szbd'),
      'add_new_item' => __('Add New Origin', 'szbd'),
      'edit_item' => __('Edit Origin', 'szbd'),
      'new_item' => __('New Origin', 'szbd'),
      'view_item' => __('View Origin', 'szbd'),
      'search_items' => __('Search Origin', 'szbd'),
      'not_found' => __('Nothing found', 'szbd'),
      'not_found_in_trash' => __('Nothing found in Trash', 'szbd'),
      'parent_item_colon' => ''
    );
    $caps2   = array(
      'edit_post' => 'edit_szbdorigin',
      'read_post' => 'read_szbdorigin',
      'delete_post' => 'delete_szbdorigin',
      'edit_posts' => 'edit_szbdorigins',
      'edit_others_posts' => 'edit_others_szbdorigins',
      'publish_posts' => 'publish_szbdorigins',
      'read_private_posts' => 'read_private_szbdorigins',
      'delete_posts' => 'delete_szbdorigins',
      'delete_private_posts' => 'delete_private_szbdorigins',
      'delete_published_posts' => 'delete_published_szbdorigins',
      'delete_others_posts' => 'delete_others_szbdorigins',
      'edit_private_posts' => 'edit_private_szbdorigins',
      'edit_published_posts' => 'edit_published_szbdorigins',
      'create_posts' => 'edit_szbdorigins'
    );
    $args2   = array(
      'labels' => $labels2,
      'public' => true,
      'publicly_queryable' => false,
      'show_ui' => true,
      'query_var' => true,
      'rewrite' => false,
      'hierarchical' => false,
      'supports' => array(
        'title',
        'author'
      ),
      'exclude_from_search' => true,
      'show_in_nav_menus' => false,
      'show_in_menu' => false,
      'can_export' => true,
      'map_meta_cap' => true,
      'capability_type' => 'szbdorigin',
      'capabilities' => $caps2
    );
    register_post_type( SZBD::POST_TITLE2, $args2);
    }
  function eval_caps2($post_type, $args2)
    {
    if ( SZBD::POST_TITLE2 === $post_type && self::$stop == false)
      {
         if(plugin_basename(__FILE__) == "shipping-zones-by-drawing-premium/classes/class-szbd-the-post-shipping-origin.php"){
          include(plugin_dir_path(__DIR__) . 'includes/start-args-prem.php');
            }else{
                 include(plugin_dir_path(__DIR__) . 'includes/start-args.php');
            }

      self::$stop = true;
      register_post_type( SZBD::POST_TITLE2, $args2);
      }
    }
    
   

  }

