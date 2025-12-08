<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SZBD_Google_Server_Requests')) {
class SZBD_Google_Server_Requests {

    const API_URL = 'https://maps.googleapis.com/maps/api/directions/json';
    
    const API_URL_GEOCODE = 'https://maps.googleapis.com/maps/api/geocode/json';

    const API_URL_ROUTES = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public $api_key;

    public $debug;

    private static $geocode_request_string;


    public function __construct($api_key) {
      $this->api_key = $api_key;

    }

    private function perform_request($params,$mode = 'directions') {
       try{
      $args = array(
        'timeout' => 4, // Default to 3 seconds.
        'redirection' => 0,
        'httpversion' => '1.0',
        'sslverify' => true,
        'blocking' => true,
        'user-agent' => 'PHP ' . PHP_VERSION . '/WooCommerce ' . get_option('woocommerce_db_version') ,
      );
      
      $api_url = $mode == 'geocode' ? self::API_URL_GEOCODE : self::API_URL; 


      //
      if($mode== 'directions'){

        // Routes will be the new API soon
        //return $this->perform_routes_request($params);
      }

      //

      $response = wp_remote_get($api_url  . '?' . (!empty($this->api_key) ? 'key=' . $this->api_key . '&' : '') . $params, $args);
      $is_error = is_wp_error($response);
      $isError = $is_error ? 'TRUE' : 'FALSE';
      $error_message = $is_error ?   'IS ERROR:'.$isError . ' Error message:'. $response->get_error_message(): '';
      

      if (get_option('szbd_log_server_requests', 'no') == 'yes' ) {
        self::szbd_log($params,$is_error);
      }
     
     
     
      if (get_option('szbd_debug', 'no') == 'yes' ) {
       
        parse_str($params, $params_debug);
      
        $date = current_datetime();
        $row0 = $date->format('H:i:s'). ' '. mt_rand(). ' '. $error_message . '  ';
        $row1 = 'SERVER to GOOGLE CALL:';
        $row2 = 'URL:' .$api_url;
        $request_string = 'REQUEST STRING:'.$params;
        
        $row3 = 'Request: ' . print_r($params_debug, true) ;
      
        $row4 = 'Response:' . print_r($response['body'], true) ;
 

        if(is_ajax()){
         wc_add_notice( print_r($row0. '  '.$row1.' '.$row2.' '.$request_string.' '.$row3.' '.$row4,true), 'notice');
        }
        else if( !is_checkout() && !is_cart()){

          WC()
          ->session
          ->set('szbd_server_request_debug',print_r($row0. '  '.$row1.' '.$row2.' '.$request_string.' '.$row3.' '.$row4,true));
        }else{

        self::console_debug($row0,$row1,$row2,$request_string,$row3 ,$row4 );
        }
        

       
       

      }

       if ($is_error) {
        
        throw new Exception('request error');
      }


      

      return $response;
    }catch (Exception $e) {
        return $e;
    }
    }

    // This will be the new standard function for calculating routes with new Routes API
    private function perform_routes_request($params) {
      try{

        parse_str($params, $output);
        $output['travelMode'] = 'DRIVE';
        $output['regionCode'] = $output['region'];
       $address = $output['destination'];
       unset($output['destination']);
       $output['destination']['address'] = $address;
      

       
       sscanf($output['origin'], '%f, %f', $lat, $lon);
       unset($output['origin']);
       $output['origin']['location']['latLng']['latitude'] = $lat;
       $output['origin']['location']['latLng']['longitude'] = $lon;


        unset($output['mode']);
        unset($output['avoid']);
        unset($output['region']);

        $body = json_encode($output);
        $api_key = !empty($this->api_key) ? $this->api_key :'';
        $headers = 
          array(
              'X-Goog-Api-Key' => $api_key,
              'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters',
              'Content-Type' => 'application/json'

        );

     $args = array(
       'timeout' => 4, // Default to 3 seconds.
       'redirection' => 0,
       'httpversion' => '1.0',
       'sslverify' => true,
       'blocking' => true,
       'user-agent' => 'PHP ' . PHP_VERSION . '/WooCommerce ' . get_option('woocommerce_db_version') ,
       'body' => $body,
       'headers' => $headers
     );
     
     $api_url =  self::API_URL_ROUTES; 

     $response = wp_remote_post($api_url  , $args);
     $is_error = is_wp_error($response);

     if (get_option('szbd_log_server_requests', 'no') == 'yes' ) {
       self::szbd_log($params,$is_error);
     }
    
    
     if ($is_error) {
       
       throw new Exception('request error');
     }

     if (get_option('szbd_debug', 'no') == 'yes' ) {
      
      $params_debug = $body;
     
       $date = current_datetime();
       $row0 = $date->format('H:i:s'). ' '. mt_rand(). ' ';
       $row1 = 'SERVER to GOOGLE CALL:';
       $row2 = 'URL:' .$api_url;
       //$request_string = 'REQUEST STRING:'.$params;
       
       $row3 = 'Request: ' . print_r($params_debug, true) ;
     
       $row4 = 'Response:' . print_r($response['body'], true) .'   '. print_r($response['response'], true)  ;
       if(is_ajax()){
        wc_add_notice( print_r($row0. '  '.$row1.' '.$row2.' . '.$row3.' '.$row4,true), 'notice');
       }
       else if( !is_checkout() && !is_cart()){

         WC()
         ->session
         ->set('szbd_server_request_debug',print_r($row0. '  '.$row1.' '.$row2.' . '.$row3.' '.$row4,true));
       }else{

       self::console_debug($row0,$row1,$row2,'',$row3 ,$row4 );
       }
       

      
      

     }

     

     return $response;
   }catch (Exception $e) {
       return $e;
   }
   }

    // Outputs a log file from server to server call to Google
    public function szbd_log($params,$is_error){

      parse_str($params, $params_debug);
      
        $date = current_datetime();
        $row0 =  $date->format('Y-m-d H:i:s'). ' TOKEN:'. mt_rand(). ' ';
        $row1 = 'SERVER to GOOGLE CALL:';
       
        $request_string = 'REQUEST STRING:'.$params;
        $is_error = $is_error ? 'true' : 'false';
       
        $message = $row0 .' '.$row1.' '.$request_string .' IS ERROR:' . $is_error; 
      
        $logfile = 'szbd_google_server_request_log.log';



        error_log($message."\n", 3, $logfile);
    }

    public function get_distance($origin, $destination_, $mode, $avoid = '', $units = 'metric', $region = false) {

      $params = array();

      $params['origin'] = $origin;
      if(is_array($destination_)){
        $destination = $destination_[0] .',' . $destination_[1];
        
      }else{
        $destination = $destination_;
      }
      $params['destination'] = $destination;
      $params['mode'] = $mode;
      if (!empty($avoid)) {
        $params['avoid'] = $avoid;
      }
      $params['units'] = $units;

      if (!empty($region)) {
        $params['region'] = $region;
      }

      $params = http_build_query($params);
      
      
      $response = $this->perform_request($params);
      $distance = json_decode($response['body']);
       
       

      return $distance;
    }
     public function get_location($location_address,$location_components, $region = false) {
       
      $params = array();
      $params['address'] = $location_address;
      if( !is_null($location_components )){
        $params['components'] = $location_components;
      }
      
     
      if (!empty($region)) {
        $params['region'] = $region;
      }

      $params = http_build_query($params);
      if(self::$geocode_request_string == $params){
          return null;
      }
      self::$geocode_request_string = $params;

      $response_ = $this->perform_request($params,'geocode');
      $response_ = is_a( $response_, 'Exception') ? array('body'=>'') : $response_;
      $response = json_decode($response_['body']);

      return $response;
    }

    static function console_debug($time,$output1,$output2,$request, $output3, $output4, $with_script_tags = true) {
      $output = $time.$output1.$output2.$request.$output3.$output4;
      $js_code = 'console.debug(' . json_encode($output, JSON_HEX_TAG) .
      ');';
      if ($with_script_tags) {
      $js_code = '<script>' . $js_code . '</script>';
      }
    
        echo $js_code;
    
     
      }
  }
}
