<?php

if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
class SZBD_Admin
  {
  function __construct()
    {
    add_action( 'admin_enqueue_scripts', array(
       $this,
      'enqueue_scripts'
    ) );
    add_action( 'add_meta_boxes', array(
       $this,
      'add_meta_boxes'
    ) );
    add_action( 'save_post_szbdzones', array(
       $this,
      'save_post'
    ), 10, 3 );
     add_action( 'save_post_szbdorigins', array(
       $this,
      'save_post_origins'
    ), 10, 3 );
     add_action('wp_ajax_test_store_address', array(
       $this,
       'test_store_address'));
      add_filter( 'manage_szbdorigins_posts_columns', array($this, 'szbd_email_column'),1 );



      add_action( 'manage_szbdorigins_posts_custom_column' , array($this, 'szbd_email_column_content'), 10, 2 );
     
     
     
     
      if( get_option('szbd_origin_table','no') == 'yes'){
            
             
             
            //  echo "<script type='text/javascript'> alert('".json_encode( class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)  )."') </script>"; 

           if( class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled() ){
           

            add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_shipping_origin_column' ), 20 );
            add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_new_order_admin_list_column_content' ), 20, 2 );
           
             
            add_filter( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'hpos_display_admin_shop_order_filters' ), 20,1 );
            add_filter( 'szbd_get_origins', array($this, 'get_shipping_origins'));
            
            
            add_filter('woocommerce_order_list_table_prepare_items_query_args', array( $this, 'hpos_origin_filter' ), 20,1 );
              
          
           
            }else{
             add_filter('manage_edit-shop_order_columns', array(
                $this,
                'add_shipping_origin_column'
            ));
            add_action('manage_shop_order_posts_custom_column', array(
                $this,
                'add_new_order_admin_list_column_content'
            ),10,2);
            add_action( 'pre_get_posts', array($this, 'process_admin_shop_order_shipping_origin_filter' ));
            add_action( 'restrict_manage_posts', array( $this, 'display_admin_shop_order_shipping_origin_filter' ));
            add_filter( 'szbd_get_origins', array($this, 'get_shipping_origins'));
          
            }
            }
            
            
 
            
        }
         function szbd_email_column($columns) {
    
    $columns['email'] = __( 'New Order recipient', 'szbd' );
    

    return $columns;
}

function szbd_email_column_content( $column, $post_id ) {
    switch ( $column ) {

        
            

        case 'email' :
          
           $settings     = get_post_meta( $post_id, 'szbdorigins_metakey', true );
            $mail = isset($settings['email']) ? $settings['email'] :'';
            echo $mail; 
            break;

    }
}
     
     function hpos_origin_filter($request){
        
         if (  isset( $_GET['filter_shop_order_origin_szbd'] ) 
        && $_GET['filter_shop_order_origin_szbd'] != '' ) {

         $meta_query = isset($request['meta_query']) ? $request['meta_query'] : array() ; 

  
            
           $meta_query[] =  array( 
            'key' => 'szbd_shipping_origin',
            'value'    =>  esc_attr($_GET['filter_shop_order_origin_szbd']),
            'compare' => 'LIKE',
         
           
                    );
        
       
      
        $request['meta_query'] = $meta_query ; 
        
        
    }
        return $request; 
       }
       
          function process_admin_shop_order_shipping_origin_filter( $query ) {
    global $pagenow;

    if (  $query->is_main_query() && $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['filter_shop_order_origin_szbd'] ) 
        && $_GET['filter_shop_order_origin_szbd'] != '' && $_GET['post_type'] == 'shop_order' ) {

        $meta_query = is_array($query->get( 'meta_query' )) ? $query->get( 'meta_query' ) : array() ; 

        $meta_query[] =  array( 
            'key' => 'szbd_shipping_origin',
            'value'    => esc_attr( $_GET['filter_shop_order_origin_szbd'] ),
        );
        $query->set( 'meta_query', $meta_query ); 

      

        $query->set( 'paged', ( get_query_var('paged') ? get_query_var('paged') : 1 ) ); 
    }
}


     function add_shipping_origin_column($columns) {
           
                $columns['szbd_shipping_origin'] = __('Shipping Origin', 'szbd');
               
            
            return $columns;
        }
        function add_new_order_admin_list_column_content($column, $order_id) {
           
               
                if ('szbd_shipping_origin' === $column) {
                    $order = wc_get_order($order_id);
                      if( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ){
                        if (  ! OrderUtil::is_order( $order->get_id(), wc_get_order_types() ) ) {
                            return;
                    }}else{
				
                        if('shop_order' !== get_post_type( $order->get_id() )){
					return;
                    }
                    }
                    $meta3 = $order->get_meta( 'szbd_shipping_origin', true);
                    if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {
                        
                        
                        echo $meta3;
                    }
                }
               
            
        }
     
  function display_admin_shop_order_shipping_origin_filter(){
    global $pagenow, $post_type;
    
  

    if( 'shop_order' === $post_type && 'edit.php' === $pagenow &&  is_main_query()) {
       
      
        
        
         $origins = apply_filters('szbd_get_origins',array());
        
        
        $current   = isset($_GET['filter_shop_order_origin_szbd'])? $_GET['filter_shop_order_origin_szbd'] : '';

        echo '<select name="filter_shop_order_origin_szbd">
        <option value="">' . __('Filter by Shipping Origin ', 'szbd') . '</option>';

        foreach ( $origins as $value ) {
            printf( '<option value="%s" %s>%s</option>', $value, 
                $value === $current ? 'selected' : '', $value );
        }
        echo '</select>';
    }
}
function hpos_display_admin_shop_order_filters(){
            
           

   

    if(get_option('szbd_origin_table','no') == 'yes'){
       
          $origins = apply_filters('szbd_get_origins',array());
        
        
        
                $current   = isset($_GET['filter_shop_order_origin_szbd'])? $_GET['filter_shop_order_origin_szbd'] : '';

        echo '<select name="filter_shop_order_origin_szbd">
        <option value="">' . __('Filter by Shipping Origin ', 'szbd') . '</option>';

        foreach ( $origins as $value ) {
            printf( '<option value="%s" %s>%s</option>', $value, 
                $value === $current ? 'selected' : '', $value );
        }
        echo '</select>';
               
               
               
        
      
    
            
        }
}
function get_shipping_origins(){
       
            
           $origins = array(  __("Main Location", 'szbd'));
        
        $args_ori = array(
            'numberposts' => -1,
            'posts_per_page' => -1,
            'post_type' => 'szbdorigins',
            'post_status' => 'publish',
            'orderby' => 'title',
            
          
        'category'         => 0,
        
        'order'            => 'DESC',
        'include'          => array(),
        'exclude'          => array(),
        'meta_key'         => '',
        'meta_value'       => '',
       
        'suppress_filters' => true,
          );
          $origin_posts = get_posts($args_ori);
         
          $attr_option_ = array();
          if (is_array($origin_posts) || is_object($origin_posts)) {
          
            $calc_1_ = array();
            foreach ($origin_posts as $calc_2_) {
               setup_postdata($calc_2_);
              $calc_3_ = get_the_title($calc_2_);
              $calc_1_[] =  $calc_3_;
              
              
            }
            $attr_option_ = array_merge($attr_option_,$calc_1_);
            
            
          }
         
          $origins = array_merge($origins,$attr_option_ );
            wp_reset_postdata();
           return $origins;
       }
        
      function test_store_address(){

$store_address     = get_option( 'woocommerce_store_address' ,'');
$store_address_2   = get_option( 'woocommerce_store_address_2','' );
$store_city        = get_option( 'woocommerce_store_city','' );
$store_postcode    = get_option( 'woocommerce_store_postcode','' );
$store_raw_country = get_option( 'woocommerce_default_country','' );
$split_country = explode( ":", $store_raw_country );
// Country and state
$store_country = $split_country[0];
// Convert country code to full name if available
				if ( isset( WC()->countries->countries[ $store_country ] ) ) {
					$store_country = WC()->countries->countries[ $store_country ];
				}
				$store_state   = isset($split_country[1]) ?  $split_country[1] : '';
        $store_loc = array(
                      'store_address' => $store_address,
											'store_address_2' => $store_address_2,
                      'store_postcode' => $store_postcode,
											'store_city'	=> $store_city,

                       'store_state'	=> $store_state,
												'store_country'	=> $store_country,

                      );
		wp_send_json(
                 array(
                       'store_address' =>  $store_loc,




                       ));
    }
  public function enqueue_scripts()
    {
      if ( isset ($_GET['tab']) && $_GET['tab'] == 'szbdtab' ) {
		          wp_enqueue_style( 'szbd-style-admin', SZBD_PREM_PLUGINDIRURL. '/assets/style-admin.css' ,array(), SZBD_PREM_VERSION );

					 wp_enqueue_script( 'shipping-del-aro-admin-settings', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-admin-settings.js', array(
         'jquery'
      ),SZBD_PREM_VERSION, true );
						wp_localize_script( 'shipping-del-aro-admin-settings', 'szbd_settings',
								array(
										'ajax_url' => admin_url('admin-ajax.php'),
										'store_location' => json_decode(get_option('SZbD_settings_test',''),true),
										 ) );


                     $google_api_key = get_option( 'szbd_google_api_key', '' );
                     wp_add_inline_script('shipping-del-aro-admin-settings', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. SZBD ADMIN Script, Ignoring...",):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                         key: "' . $google_api_key . '",
                         v: "quarterly",
                        
                        
                       });', 'before');

	}else if(isset ($_GET['tab']) && $_GET['tab'] == 'shipping'){
         wp_enqueue_script( 'shipping-del-aro-admin-method', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-admin-method.js', array(
         'jquery'
      ),SZBD_PREM_VERSION, true );
    }
    global $pagenow, $post;
    if ( isset( get_current_screen()->id ) && ( get_current_screen()->id == 'edit-' . SZBD::POST_TITLE || get_current_screen()->id == SZBD::POST_TITLE ) )
      {
	 wp_enqueue_style( 'szbd-style-admin', SZBD_PREM_PLUGINDIRURL. '/assets/style-admin.css' ,array(), SZBD_PREM_VERSION );

      wp_enqueue_script( 'shipping-del-aro-admin', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-admin.js', array(
         'jquery'
      ),SZBD_PREM_VERSION, true );
      $args = array(
         'screen' => null !== get_current_screen() ? get_current_screen() : false
      );
      wp_localize_script( 'shipping-del-aro-admin', 'szbd', $args );
      }
    if ( !( get_post_type() == SZBD::POST_TITLE || get_post_type() == SZBD::POST_TITLE2)  || !in_array( $pagenow, array(
       'post-new.php',
      'edit.php',
      'post.php'
    ) ) )
      {
      return;
      }
    $google_api_key = get_option( 'szbd_google_api_key', '' );
    if ( $google_api_key != '' && get_current_screen()->id == SZBD::POST_TITLE )
      {
   
      wp_register_script( 'szbd-script-2', SZBD_PREM_PLUGINDIRURL. '/assets/szbd-admin-map.js', array(
        
        'jquery'
      ), SZBD_PREM_VERSION, true );
      $this->szbdzones_js( $post->ID );
      wp_enqueue_script( 'szbd-script-2' );
      wp_add_inline_script( 'szbd-script-2', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. SZBD Admin, Ignoring...",):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "'.$google_api_key.'",
        v: "quarterly",});','before' );
      }
      else if ( $google_api_key != '' && get_current_screen()->id == SZBD::POST_TITLE2  )
      {
    
      wp_register_script( 'szbd-script-2', SZBD_PREM_PLUGINDIRURL. '/assets/szbd-admin-map-origins.js', array(
        
        'jquery'
      ), SZBD_PREM_VERSION, true );
      $this->szbdorigins_js( $post->ID );
      wp_enqueue_script( 'szbd-script-2' );
      wp_add_inline_script( 'szbd-script-2', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. SZBD Admin, Ignoring...",):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "'.$google_api_key.'",
        v: "quarterly",});','before' );
      
      }
    }
  public function add_meta_boxes()
    {
    add_meta_box( 'szbdzones_mapmeta', 'Map', array(
       $this,
      'input_map'
    ), 'szbdzones', 'normal', 'high' );

    add_meta_box( 'szbdzones_mapmeta2', __('Import KML Polygon','szbd'), array(
      $this,
     'upload'
   ), 'szbdzones', 'side', 'high' );

    add_meta_box( 'szbdorigins_mapmeta', 'Map', array(
       $this,
      'input_map_origin'
    ), 'szbdorigins', 'normal', 'high' );
    
     add_meta_box( 'szbdorigins_mapmetaemail', __('New Order Email','szbd'), array(
       $this,
      'input_map_origin_email'
    ), 'szbdorigins', 'normal', 'high' );
    }

    public function upload(){

      echo ' <input type="file" accept=".kml,.kmz" onchange="szbdfileChanged(event)">';
        
      
     }
    
     public function input_map_origin_email(){
      global $post;
       $settings     = get_post_meta( $post->ID, 'szbdorigins_metakey', true );
       $mail = isset($settings['email']) ? $settings['email'] :'';
       
      woocommerce_form_field( 'origin_email', array(
		'type'        => 'email',
		'required'    => false,
		'label'       => __('Email address','szbd'),
		'description' => __('New order emails are sent to this address when a new order is received. This will override the default recipient(s).','szbd'),
    
	), $mail );
      
     }
      public function input_map_origin(){
    global $post;
    $google_api_key = get_option( 'szbd_google_api_key', '' );
    if ( $google_api_key != ''  )
      {
        
      include SZBD_PREM_PLUGINDIRPATH . '/includes/admin-map-template.php';
      }
    else
     { echo sprintf( __( 'Please enter a Google Maps API Key in the <a href="%s" title="settings page">settings page.</a>', SZBD::TEXT_DOMAIN ), admin_url( 'admin.php?page=wc-settings&tab=szbdtab' ) );
    }
    }
  public function input_map()
    {
    global $post;
    $google_api_key = get_option( 'szbd_google_api_key', '' );
    if ( $google_api_key != ''  )
      {
      include SZBD_PREM_PLUGINDIRPATH . '/includes/admin-map-template.php';
      }
    else
     { echo sprintf( __( 'Please enter a Google Maps API Key in the <a href="%s" title="settings page">settings page.</a>', SZBD::TEXT_DOMAIN ), admin_url( 'admin.php?page=wc-settings&tab=szbdtab' ) );
    }
		 echo '<div class="notice notice-success is-dismissible">

            <div class="fdoe_premium">

            	<table>

                	<tbody><tr>

                    	<td width="100%">

                        	<p style="font-size:1.3em"><strong><i>Show a delivery map to customers </i></strong>with [szbd] shortcode</p>

                            <ul class="fa-ul" id="fdoe_premium_ad">

								<li ><span class="fa-li" ><i class="fas fa-check" style="color:green"></i></span>	Add drawn maps by post ids, like ids="id1, id2, id3"</li>
								 	<li ><span class="fa-li" ><i class="fas fa-check" style="color:green"></i></span>	Add a title to the map by title="Title"</li>
											<li ><span class="fa-li" ><i class="fas fa-check" style="color:green"></i></span>	Set the color of delivery areas by color="blue"</li>

                            	<li ><span class="fa-li" ><i class="fas fa-check" style="color:green"></i></span>	Example [szbd ids="id1,id2" title="Delivery Zones" color="#c87f93"]</li>

                            </ul>

                        </td>



                    </tr>

                </tbody></table>

            </div>

         </div>';
		}
  public function szbdzones_js( $post_id )
    {
    $settings     = get_post_meta( $post_id, 'szbdzones_metakey', true );
    $lat          = isset( $settings['lat'] ) ? $settings['lat'] : '';
    $lng          = isset( $settings['lng'] ) ? $settings['lng'] : '';
    $zoom         = isset( $settings['zoom'] ) ? $settings['zoom'] : '1.3';
   
    $geo_coordinates_array = is_array( $settings ) && is_array( $settings['geo_coordinates'] ) ? $settings['geo_coordinates'] : array();
    if ( count( $geo_coordinates_array ) > 0 )
      {
      foreach ( $geo_coordinates_array as $geo_coordinates )
        {
        if ( $geo_coordinates[0] != '' && $geo_coordinates[1] != '' )
          $array_latlng[] = array(
             $geo_coordinates[0],
            $geo_coordinates[1]
          );
        }
      }
    else
      {
      $array_latlng = array();
      }
      
    $args = array(
       'lat' => $lat,
      'lng' => $lng,
      'zoom' => intval( $zoom ),
      'array_latlng' => $array_latlng,
      
    );
    wp_localize_script( 'szbd-script-2', 'szbd_map', $args );
    //    }
    }
    public function szbdorigins_js( $post_id )
    {
    $settings     = get_post_meta( $post_id, 'szbdorigins_metakey', true );
    $lat          = isset( $settings['lat'] ) ? $settings['lat'] : '';
    $lng          = isset( $settings['lng'] ) ? $settings['lng'] : '';
    $zoom         = isset( $settings['zoom'] ) ? $settings['zoom'] : '1.3';
  
    $geo_coordinates_array = is_array( $settings ) && is_array( $settings['geo_coordinates'] ) ? $settings['geo_coordinates'] : array();
    if ( count( $geo_coordinates_array ) > 0 )
      {
      foreach ( $geo_coordinates_array as $geo_coordinates )
        {
        if ( $geo_coordinates[0] != '' && $geo_coordinates[1] != '' )
          $array_latlng[] = array(
             $geo_coordinates[0],
            $geo_coordinates[1]
          );
        }
      }
    else
      {
      $array_latlng = array();
      }
        
    $args = array(
       'lat' => $lat,
      'lng' => $lng,
      'zoom' => intval( $zoom ),
      'array_latlng' => $array_latlng,
      'position'=> self::isJson($settings['geo_coordinates']) ? json_decode($settings['geo_coordinates']) : $array_latlng,
    );
    wp_localize_script( 'szbd-script-2', 'szbd_map', $args );
  
    }
  public function save_post( $post_id, $post, $update )
    {
        if ( is_multisite() && ms_is_switched() ){
    return false;
        }
    if ( $post->post_type != 'szbdzones' ){
     return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
      return;
    }
    if ( wp_is_post_revision( $post_id ) ){
      return;
    }
    if ( !current_user_can( 'edit_post', $post_id ) ){
      return;
    }
    if ( isset( $_POST['szbdzones_geo_coordinates'] ) && !empty( $_POST['szbdzones_geo_coordinates'] ) )
      {
      $array_geo_coordinates = explode( '),(', $_POST['szbdzones_geo_coordinates'] );
      if ( is_array( $array_geo_coordinates ) && count( $array_geo_coordinates ) > 0 )
        {
        foreach ( $array_geo_coordinates as $value_geo_coordinates )
          {
          $latlng         = str_replace( array(
             "(",
            ")"
          ), array(
             "",
            ""
          ), $value_geo_coordinates );
          $array_latlng[] = array_map( 'sanitize_text_field', explode( ',', $latlng ) );
          }
        }
      else
        $array_latlng = array();
      $array_save_post = array(
         'lcolor' => !empty( $_POST['szbdzones_lcolor'] ) ? sanitize_text_field( $_POST['szbdzones_lcolor'] ) : '#0c6e9e',
        'lat' => !empty( $_POST['szbdzones_lat'] ) ? sanitize_text_field( $_POST['szbdzones_lat'] ) : 0,
        'lng' => !empty( $_POST['szbdzones_lng'] ) ? sanitize_text_field( $_POST['szbdzones_lng'] ) : 65,
        'geo_coordinates' =>  $array_latlng,
        'zoom' => !empty( $_POST['szbdzones_zoom'] ) ? sanitize_text_field( $_POST['szbdzones_zoom'] ) : 1.3
      );
      update_post_meta( $post_id, 'szbdzones_metakey', $array_save_post );
      
      }elseif(isset( $_POST['szbdzones_geo_coordinates'] ) && empty( $_POST['szbdzones_geo_coordinates'] )){
        $array_save_post = array(
          'lcolor' => !empty( $_POST['szbdzones_lcolor'] ) ? sanitize_text_field( $_POST['szbdzones_lcolor'] ) : '#0c6e9e',
         'lat' =>  '',
         'lng' =>  '',
         'geo_coordinates' => array(),
         'zoom' =>  1
       );
       update_post_meta( $post_id, 'szbdzones_metakey', $array_save_post );

      }
    return $post_id;
    }
    
     public function save_post_origins( $post_id, $post, $update )
    {
        if ( is_multisite() && ms_is_switched() ){
    return false;
        }
    if ( $post->post_type != 'szbdorigins' ){
     return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
      return;
    }
    if ( wp_is_post_revision( $post_id ) ){
      return;
    }
    if ( !current_user_can( 'edit_post', $post_id ) ){
      return;
    }
    if ( isset( $_POST['szbdzones_geo_coordinates'] ) && !empty( $_POST['szbdzones_geo_coordinates'] ) )
      {
      $array_latlng  = maybe_serialize($_POST['szbdzones_geo_coordinates']) ;
      }
      else{
        $array_latlng = array();
      }
      $array_save_post = array(
         'lcolor' => !empty( $_POST['szbdzones_lcolor'] ) ? sanitize_text_field( $_POST['szbdzones_lcolor'] ) : '#0c6e9e',
        'lat' => !empty( $_POST['szbdzones_lat'] ) ? sanitize_text_field( $_POST['szbdzones_lat'] ) : 0,
        'lng' => !empty( $_POST['szbdzones_lng'] ) ? sanitize_text_field( $_POST['szbdzones_lng'] ) : 65,
        'geo_coordinates' => $array_latlng,
        'zoom' => !empty( $_POST['szbdzones_zoom'] ) ? sanitize_text_field( $_POST['szbdzones_zoom'] ) : 1.3,
        'email' => !empty( $_POST['origin_email'] ) ? sanitize_text_field( $_POST['origin_email'] ) : '',
      );
      update_post_meta( $post_id, 'szbdorigins_metakey', $array_save_post );
      
    return $post_id;
    }
  static function isJson($json, $depth = 512, $flags = 0) {
        if (!is_string($json)) {
            return false;
        }

        try {
            json_decode($json, false, $depth, $flags | JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }
  }
