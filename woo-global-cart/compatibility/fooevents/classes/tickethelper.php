<?php 

    if(!defined('ABSPATH')) exit;
    class WooGC_FooEvents_Ticket_Helper extends FooEvents_Ticket_Helper{
        
        public function __construct($config) {
            
            global $WooGC;
            
            $config     =   new FooEvents_Config();
            
            $this->Config = $config;
            
            //BarcodeHelper
            require_once($this->Config->classPath.'barcodehelper.php');
            $this->BarcodeHelper = new FooEvents_Barcode_Helper($this->Config);
            
            //MailHelper
            require_once($this->Config->classPath.'mailhelper.php');
            $this->MailHelper = new FooEvents_Mail_Helper($this->Config);    
            
            
            $WooGC->functions->remove_class_filter( 'manage_event_magic_tickets_posts_custom_column',   'FooEvents_Ticket_Helper',     'add_admin_column_content' );
            add_action('manage_event_magic_tickets_posts_custom_column', array(&$this, 'add_admin_column_content'), 10, 1);
            
        }
        
        
        
        
        /**
         * Adds column content to the event ticket custom post type.
         * 
         * @param string $column
         * @global object $post
         * 
         */
        public function add_admin_column_content($column) {
            
            global $post;
            global $woocommerce;
            
            $order_id = get_post_meta($post->ID, 'WooCommerceEventsOrderID', true);
            $customer_id = get_post_meta($post->ID, 'WooCommerceEventsCustomerID', true);
            $order = array();
            
            try {
                
                $order = new WC_Order( $order_id );
                
            } catch (Exception $e) {
                
            }   

            switch($column) {
                case 'Event' :
                    
                    $WooCommerceEventsProductBlogID =   get_post_meta($post->ID, 'WooCommerceEventsProductBlogID', true);
                    $WooCommerceEventsProductID = get_post_meta($post->ID, 'WooCommerceEventsProductID', true);
                    if ( $WooCommerceEventsProductBlogID > 0 )
                        {
                            switch_to_blog($WooCommerceEventsProductBlogID);
                            
                            $post_data  =   get_post( $WooCommerceEventsProductID);
                            
                            $post_title =  $post_data->post_title;
                            echo '<a href="'.get_site_url().'/wp-admin/post.php?post='.$WooCommerceEventsProductID.'&action=edit">'.$post_title.'</a>';
                            
                            restore_current_blog();
                        }
                        else
                            {
                                $post_title =   get_the_title($WooCommerceEventsProductID);
                                echo '<a href="'.get_site_url().'/wp-admin/post.php?post='.$WooCommerceEventsProductID.'&action=edit">'.$post_title.'</a>';
                            }
                    
                    
                    
  
                    break;
                
                case 'Purchaser' :
                    
                    if(empty($order)) {
                        
                       echo "<i>Warning: WooCommerce order has been deleted.</i><br /><br />"; 
                        
                    }
                    
                    if(!empty($customer_id) && !($customer_id instanceof WP_Error)) {
        
                        $WooCommerceEventsPurchaserFirstName = get_post_meta($post->ID, 'WooCommerceEventsPurchaserFirstName', true);
                        $WooCommerceEventsPurchaserLastName = get_post_meta($post->ID, 'WooCommerceEventsPurchaserLastName', true);
                        $WooCommerceEventsPurchaserEmail = get_post_meta($post->ID, 'WooCommerceEventsPurchaserEmail', true);
                        echo '<a href="'.get_site_url().'/wp-admin/user-edit.php?user_id='.$customer_id.'">'.$WooCommerceEventsPurchaserFirstName.' '.$WooCommerceEventsPurchaserLastName.' - ( '.$WooCommerceEventsPurchaserEmail.' )</a>';
                        
                    } else {
                        
                        //guest account
                        try {
                            
                            if(!empty($order)) {
                                
                                echo $order->get_billing_first_name().' '.$order->get_billing_last_name().' - ( '.$order->get_billing_email().' )';

                            }
                             
                        } catch (Exception $e) {
                
                        }   
                    
                    }
                    
                    break;
                    
                case 'Attendee' : 
                    
                    $WooCommerceEventsAttendeeName = get_post_meta($post->ID, 'WooCommerceEventsAttendeeName', true);
                    $WooCommerceEventsAttendeeLastName = get_post_meta($post->ID, 'WooCommerceEventsAttendeeLastName', true);
                    $WooCommerceEventsAttendeeEmail = get_post_meta($post->ID, 'WooCommerceEventsAttendeeEmail', true);
                    echo $WooCommerceEventsAttendeeName.' '.$WooCommerceEventsAttendeeLastName.'- '.$WooCommerceEventsAttendeeEmail;
                    
                    break;
                
                case 'PurchaseDate' :
                    
                    echo $post->post_date;
                    
                    break;
                
                case 'Status' :
                    
                    $WooCommerceEventsProductID = get_post_meta($post->ID, 'WooCommerceEventsProductID', true);
                    $WooCommerceEventsNumDays = (int)get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsNumDays', true);
                    $WooCommerceEventsMultidayStatus = '';
                    $WooCommerceEventsStatus = get_post_meta($post->ID, 'WooCommerceEventsStatus', true);
                    
                    if (!function_exists('is_plugin_active_for_network')) {
                        
                        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
                        
                    }

                    if (($this->is_plugin_active('fooevents_multi_day/fooevents-multi-day.php') || is_plugin_active_for_network('fooevents_multi_day/fooevents-multi-day.php')) && $WooCommerceEventsNumDays > 1) {

                        $Fooevents_Multiday_Events = new Fooevents_Multiday_Events();
                        $WooCommerceEventsMultidayStatus = $Fooevents_Multiday_Events->display_multiday_status_ticket_meta_all($post->ID);

                    }
                    
                    if(empty($WooCommerceEventsMultidayStatus) || $WooCommerceEventsStatus == 'Unpaid' || $WooCommerceEventsStatus == 'Canceled' || $WooCommerceEventsStatus == 'Cancelled') {

                        echo $WooCommerceEventsStatus;

                    } else {
                        
                        echo $WooCommerceEventsMultidayStatus;
                        
                    }
                    
                    break;
                    
                case 'Options' :

                    break;
            }
            
        }
        
        
        
        
        
        /**
         * Retrieves ticket data from database.
         * 
         * @param int $ticketID
         * @return array
         */
        public function get_ticket_data($ticketID) {

            $ticket = array();
            $WooCommerceEventsProductID                 = get_post_meta($ticketID, 'WooCommerceEventsProductID', true);
            $WooCommerceEventsOrderID                   = get_post_meta($ticketID, 'WooCommerceEventsOrderID', true);
            $WooCommerceEventsTicketType                = get_post_meta($ticketID, 'WooCommerceEventsTicketType', true);
            $WooCommerceEventsTicketID                  = get_post_meta($ticketID, 'WooCommerceEventsTicketID', true);
            $WooCommerceEventsTicketHash                = get_post_meta($ticketID, 'WooCommerceEventsTicketHash', true);
            $WooCommerceEventsStatus                    = get_post_meta($ticketID, 'WooCommerceEventsStatus', true);
            $ticket['WooCommerceEventsVariations']      = get_post_meta($ticketID, 'WooCommerceEventsVariations', true);
            
            $ticket['WooCommerceEventsPrice']           = get_post_meta($ticketID, 'WooCommerceEventsPrice', true);
            $ticket['WooCommerceEventsPriceSymbol']     = get_post_meta($ticketID, 'WooCommerceEventsPriceSymbol', true);

            $ticket['type'] = "HTML";

            if(!empty($ticket['WooCommerceEventsVariations'] ) && !is_array($ticket['WooCommerceEventsVariations'] )) {

                $ticket['WooCommerceEventsVariations']  = json_decode($ticket['WooCommerceEventsVariations'] );

            }

            $ticket['WooCommerceEventsVariationID']     = get_post_meta($ticketID, 'WooCommerceEventsVariationID', true);

            $customer = get_post_meta($WooCommerceEventsOrderID, '_customer_user', true);
            
            $order = array();
            try {
                $order = new WC_Order( $WooCommerceEventsOrderID );
            } catch (Exception $e) {
                
            }  
            
            $customerDetails = array(
                            'customerID'        => $customer
            );

            if (!empty($order)) {

                $customerDetails['customerFirstName']   = $order->get_billing_first_name();
                $customerDetails['customerLastName']    = $order->get_billing_last_name();
                $customerDetails['customerEmail']       = $order->get_billing_email();

            } else {
                
                $customerDetails['customerFirstName']   = '';
                $customerDetails['customerLastName']    = '';
                $customerDetails['customerEmail']       = '';
                
            }
            
            $ticket['fooevents_custom_attendee_fields_options'] = '';
            $ticket['fooevents_seating_options'] = '';
            
            $customer = get_post_meta($WooCommerceEventsOrderID, '_customer_user', true);
            
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            if ($this->is_plugin_active( 'fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php') || is_plugin_active_for_network('fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php')) {

                $Fooevents_Custom_Attendee_Fields = new Fooevents_Custom_Attendee_Fields();
                $ticket['fooevents_custom_attendee_fields_options'] = $Fooevents_Custom_Attendee_Fields->display_tickets_meta_custom_options_output($ticketID);

            }
            
             if ($this->is_plugin_active( 'fooevents_seating/fooevents-seating.php') || is_plugin_active_for_network('fooevents_seating/fooevents-seating.php')) {

                $Fooevents_Seating = new Fooevents_Seating();
                $ticket['fooevents_seating_options'] = $Fooevents_Seating->display_tickets_meta_seat_options_output($ticketID);

            }
            
            $WooCommerceEventsEvent                     = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEvent', true);
            $WooCommerceEventsCaptureAttendeeDetails    = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsCaptureAttendeeDetails', true);
            $WooCommerceEventsSendEmailTickets          = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsSendEmailTickets', true);
            $WooCommerceEventsEmailSubjectSingle        = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEmailSubjectSingle', true);
            
            //update ticket as paid
            if($WooCommerceEventsStatus == 'Unpaid') {

                update_post_meta($ticketID, 'WooCommerceEventsStatus', 'Not Checked In');

            }
            
            $ticket['WooCommerceEventsEvent']                       = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEvent', true);
            $ticket['WooCommerceEventsDate']                        = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsDate', true);
            $ticket['WooCommerceEventsEndDate']                        = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEndDate', true);
            $ticket['WooCommerceEventsSelectDate']                  = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsSelectDate', true);
            $ticket['WooCommerceEventsMultiDayType']                = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsMultiDayType', true);
            $ticket['WooCommerceEventsHour']                        = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsHour', true);
            $ticket['WooCommerceEventsMinutes']                     = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsMinutes', true);
            $ticket['WooCommerceEventsPeriod']                      = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsPeriod', true);
            $ticket['WooCommerceEventsHourEnd']                     = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsHourEnd', true);
            $ticket['WooCommerceEventsMinutesEnd']                  = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsMinutesEnd', true);
            $ticket['WooCommerceEventsEndPeriod']                   = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEndPeriod', true);
            $ticket['WooCommerceEventsLocation']                    = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsLocation', true);
            $ticket['WooCommerceEventsTicketLogo']                  = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketLogo', true);
            $ticket['WooCommerceEventsTicketHeaderImage']           = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketHeaderImage', true);
            $ticket['WooCommerceEventsSupportContact']              = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsSupportContact', true);
            $ticket['WooCommerceEventsEmail']                       = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsEmail', true);
            $ticket['WooCommerceEventsTicketBackgroundColor']       = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketBackgroundColor', true);
            $ticket['WooCommerceEventsTicketButtonColor']           = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketButtonColor', true);
            $ticket['WooCommerceEventsTicketTextColor']             = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketTextColor', true);
            $ticket['WooCommerceEventsTicketPurchaserDetails']      = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketPurchaserDetails', true);
            $ticket['WooCommerceEventsTicketAddCalendar']           = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketAddCalendar', true);
            $ticket['WooCommerceEventsTicketDisplayDateTime']       = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketDisplayDateTime', true);
            $ticket['WooCommerceEventsTicketDisplayBarcode']        = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketDisplayBarcode', true);
            $ticket['WooCommerceEventsTicketText']                  = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketText', true);
            $ticket['WooCommerceEventsDirections']                  = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsDirections', true);
            $ticket['WooCommerceEventsTicketDisplayPrice']          = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketDisplayPrice', true);
            $ticket['WooCommerceEventsIncludeCustomAttendeeDetails'] = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsIncludeCustomAttendeeDetails', true);
            $ticket['WooCommerceEventsTicketLogoPath']              = get_post_meta($WooCommerceEventsProductID, 'WooCommerceEventsTicketLogoPath', true);
            $ticket['WooCommerceEventsTicketType']                  = $WooCommerceEventsTicketType;
            $ticket['WooCommerceEventsProductID']                   = $WooCommerceEventsProductID;
            $ticket['WooCommerceEventsTicketID']                    = $WooCommerceEventsTicketID;
            $ticket['WooCommerceEventsTicketHash']                  = $WooCommerceEventsTicketHash;
            $ticket['WooCommerceEventsOrderID']                     = $WooCommerceEventsOrderID;
            $ticket['WooCommerceEventsSendEmailTickets']            = $WooCommerceEventsSendEmailTickets;
            
            if ($this->is_plugin_active('fooevents_multi_day/fooevents-multi-day.php') || is_plugin_active_for_network('fooevents_multi_day/fooevents-multi-day.php')) { 

                if($ticket['WooCommerceEventsMultiDayType'] == 'select') {

                    $ticket['WooCommerceEventsDate'] = $ticket['WooCommerceEventsSelectDate'][0];
                    $ticket['WooCommerceEventsEndDate'] = $ticket['WooCommerceEventsSelectDate'][1];

                }
            
            }
            
            $barcodeFileName = '';
            
            if(!empty($WooCommerceEventsTicketHash)) {
                
                $barcodeFileName = $WooCommerceEventsTicketHash.'-'.$WooCommerceEventsTicketID;
                
            } else {
                
                $barcodeFileName = $WooCommerceEventsTicketID;
                
            }
            
            $ticket['barcodeFileName'] = $barcodeFileName;
            
            $ticketDetails = get_post($WooCommerceEventsProductID);
            
            $ticket['WooCommerceEventsTicketText'] = apply_filters('meta_content', $ticket['WooCommerceEventsTicketText']);

            if(!empty($ticket['WooCommerceEventsTicketLogo'])) {
                    
                    $logo_id = $this->get_logo_id($ticket['WooCommerceEventsTicketLogo']);
                    
                    if($logo_id) {
                        
                        $ticket['WooCommerceEventsTicketLogoID'] = $this->get_logo_id($ticket['WooCommerceEventsTicketLogo']);
                        $ticket['WooCommerceEventsTicketLogoPath'] = get_attached_file($ticket['WooCommerceEventsTicketLogoID']);
                        
                    } else {
                        
                        $ticket['WooCommerceEventsTicketLogoPath'] = $ticket['WooCommerceEventsTicketLogo'];
                        
                    }

                }
            
        if(!empty($ticket['WooCommerceEventsTicketHeaderImage'])) {
                    
                    $header_image_id = $this->get_logo_id($ticket['WooCommerceEventsTicketHeaderImage']);
                    
                    if($header_image_id) {
                        
                        $ticket['WooCommerceEventsTicketHeaderImageID'] = $this->get_logo_id($ticket['WooCommerceEventsTicketHeaderImage']);
                        $ticket['WooCommerceEventsTicketHeaderImagePath'] = get_attached_file($ticket['WooCommerceEventsTicketHeaderImageID']);
                        
                    } else {
                        
                        $ticket['WooCommerceEventsTicketHeaderImagePath'] = $ticket['WooCommerceEventsTicketHeaderImage'];
                        
                    }

                }
            
            $globalWooCommerceEventsTicketBackgroundColor   = get_option('globalWooCommerceEventsTicketBackgroundColor', true);
            $globalWooCommerceEventsTicketButtonColor       = get_option('globalWooCommerceEventsTicketButtonColor', true);
            $globalWooCommerceEventsTicketTextColor         = get_option('globalWooCommerceEventsTicketTextColor', true);
            $globalWooCommerceEventsTicketLogo              = get_option('globalWooCommerceEventsTicketLogo', true);

            if(empty($ticket['WooCommerceEventsTicketBackgroundColor'])) {

                $ticket['WooCommerceEventsTicketBackgroundColor'] = $globalWooCommerceEventsTicketBackgroundColor;

            }

            if(empty($ticket['WooCommerceEventsTicketButtonColor'])) {

                $ticket['WooCommerceEventsTicketButtonColor'] = $globalWooCommerceEventsTicketButtonColor;

            }

            if(empty($ticket['WooCommerceEventsTicketTextColor'])) {

                $ticket['WooCommerceEventsTicketTextColor'] = $globalWooCommerceEventsTicketTextColor;

            }

            if(empty($ticket['name'])) {

                 $ticket['name'] = $ticketDetails->post_title;

            } 
            
            $timestamp                                              = time();
            $key                                                    = md5($WooCommerceEventsTicketID + $timestamp + $this->Config->salt);                              
            $ticket['cancelLink']                                   = get_site_url().'/wp-admin/admin-ajax.php?action=woocommerce_events_cancel&id='.$WooCommerceEventsTicketID.'&t='.$timestamp.'&k='.$key;

            if($WooCommerceEventsCaptureAttendeeDetails === 'on') {

                
                $ticket['WooCommerceEventsAttendeeTelephone']   = get_post_meta($ticketID, 'WooCommerceEventsAttendeeTelephone', true);
                $ticket['WooCommerceEventsAttendeeCompany']     = get_post_meta($ticketID, 'WooCommerceEventsAttendeeCompany', true);
                $ticket['WooCommerceEventsAttendeeDesignation'] = get_post_meta($ticketID, 'WooCommerceEventsAttendeeDesignation', true);
                $ticket['WooCommerceEventsAttendeeEmail']       = get_post_meta($ticketID, 'WooCommerceEventsAttendeeEmail', true);
                $ticket['customerFirstName']                    = get_post_meta($ticketID, 'WooCommerceEventsAttendeeName', true);
                $ticket['customerLastName']                     = get_post_meta($ticketID, 'WooCommerceEventsAttendeeLastName', true);
                $ticket['customerEmail']                        = $ticket['WooCommerceEventsAttendeeEmail'];

            } else {

                $ticket['customerFirstName']                    = $customerDetails['customerFirstName']; 
                $ticket['customerLastName']                     = $customerDetails['customerLastName'];
                $ticket['customerEmail']                        = $customerDetails['customerEmail'];

                if(!empty($customerDetails['billing_phone'])) {

                    $ticket['WooCommerceEventsAttendeeTelephone']   = $customerDetails['billing_phone'];

                } else {

                    $ticket['WooCommerceEventsAttendeeTelephone']   = '';

                }

                $ticket['WooCommerceEventsAttendeeCompany']     = '';
                $ticket['WooCommerceEventsAttendeeDesignation'] = '';

            }
            
            //generate barcode
            if (!file_exists($this->Config->barcodePath.$ticket['WooCommerceEventsTicketID'].'.png')) {

                $this->BarcodeHelper->generate_barcode($ticket['WooCommerceEventsTicketID'], $WooCommerceEventsTicketHash);

            }

            $ticket['FooEventsTicketFooterText'] = get_post_meta($WooCommerceEventsProductID, 'FooEventsTicketFooterText', true);
            
            if(empty($ticket['WooCommerceEventsTicketBackgroundColor'])) {

                $ticket['WooCommerceEventsTicketBackgroundColor'] = '#55AF71';

            }

            if(empty($ticket['WooCommerceEventsTicketButtonColor'])) {

                $ticket['WooCommerceEventsTicketButtonColor'] = '#55AF71';

            }

            if(empty($ticket['WooCommerceEventsTicketTextColor'])) {

                $ticket['WooCommerceEventsTicketTextColor'] = '#FFFFFF';

            }

            return $ticket;
            
        }
        
        
        /**
         * Outputs notices to screen.
         * 
         * @param array $notices
         */
        private function output_notices($notices) {

            foreach ($notices as $notice) {

                    echo "<div class='updated'><p>$notice</p></div>";

            }

        }
        
        /**
         * Checks if a plugin is active.
         * 
         * @param string $plugin
         * @return boolean
         */
        private function is_plugin_active( $plugin ) {

            return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );

        }
        
        
    }
    
    
    
    
    
?>