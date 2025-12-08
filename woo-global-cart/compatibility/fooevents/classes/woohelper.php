<?php 


    if (!defined('ABSPATH')) exit;
    
    
    class WooGC_FooEvents_Woo_Helper extends FooEvents_Woo_Helper 
        {
	        
            public  $Config;
            public  $TicketHelper;
            public  $BarcodeHelper;
            public  $MailHelper;
            
            
            function __construct()
                {
                    
                    $config     =   new FooEvents_Config();
                    
                    $this->Config   =   $config;
                    
                    require_once WOOGC_PATH . '/compatibility/fooevents/classes/tickethelper.php';
                    
                    $this->TicketHelper = new WooGC_FooEvents_Ticket_Helper( $config );
                    
                    $this->BarcodeHelper = new FooEvents_Barcode_Helper( $config );
                    
                    $this->MailHelper   = new FooEvents_Mail_Helper( $config );
                    
                    add_action('woocommerce_order_status_completed', array(&$this, 'process_order_tickets'), 20);   
                    
                    //$WooGC->functions->remove_class_filter( 'woocommerce_product_write_panel_tabs',   'FooEvents_Woo_Helper',     'process_order_tickets', 20 );
                    
                }

          
            /**
             * Creates an orders tickets
             * 
             * @param int $order_id
             */
            public function create_tickets($order_id) 
                {

                    $WooCommerceEventsOrderTickets = get_post_meta($order_id, 'WooCommerceEventsOrderTickets', true);
                    $WooCommerceEventsSentTicket =  get_post_meta($order_id, 'WooCommerceEventsTicketsGenerated', true);

                    if($WooCommerceEventsSentTicket != 'yes' && !empty($WooCommerceEventsOrderTickets)) {

                        $x = 1;
                        foreach($WooCommerceEventsOrderTickets as $event => $tickets) {

                            $y = 1;
                            foreach($tickets as $ticket) {
                                
                                if(!empty($ticket['WooCommerceEventsOrderID'])) {
                                    
                                    switch_to_blog( $ticket['product_blog_id'] );
                                    $product = get_post($ticket['WooCommerceEventsProductID']);
                                    restore_current_blog();

                                    $rand = rand(111111,999999);

                                    $post = array(

                                            'post_author' => $ticket['WooCommerceEventsCustomerID'],
                                            'post_content' => "Ticket",
                                            'post_status' => "publish",
                                            'post_title' => 'Assigned Ticket',
                                            'post_type' => "event_magic_tickets"

                                    );

                                    $post['ID'] = wp_insert_post( $post );
                                    $ticketID = $post['ID'].$rand;
                                    $post['post_title'] = '#'.$ticketID;
                                    $postID = wp_update_post( $post );

                                    $ticketHash = $this->generate_random_string(8);

                                    update_post_meta($postID, 'WooCommerceEventsTicketID', $ticketID);
                                    update_post_meta($postID, 'WooCommerceEventsTicketHash', $ticketHash);
                                    update_post_meta($postID, 'WooCommerceEventsProductID', $ticket['WooCommerceEventsProductID']);
                                    update_post_meta($postID, 'WooCommerceEventsOrderID', $ticket['WooCommerceEventsOrderID']);
                                    update_post_meta($postID, 'WooCommerceEventsTicketType', $ticket['WooCommerceEventsTicketType']);
                                    update_post_meta($postID, 'WooCommerceEventsStatus', 'Unpaid');
                                    update_post_meta($postID, 'WooCommerceEventsCustomerID', $ticket['WooCommerceEventsCustomerID']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeName', $ticket['WooCommerceEventsAttendeeName']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeLastName', $ticket['WooCommerceEventsAttendeeLastName']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeEmail', $ticket['WooCommerceEventsAttendeeEmail']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeTelephone', $ticket['WooCommerceEventsAttendeeTelephone']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeCompany', $ticket['WooCommerceEventsAttendeeCompany']);
                                    update_post_meta($postID, 'WooCommerceEventsAttendeeDesignation', $ticket['WooCommerceEventsAttendeeDesignation']);
                                    update_post_meta($postID, 'WooCommerceEventsVariations', $ticket['WooCommerceEventsVariations']);
                                    update_post_meta($postID, 'WooCommerceEventsVariationID', $ticket['WooCommerceEventsVariationID']);

                                    update_post_meta($postID, 'WooCommerceEventsPurchaserFirstName', $ticket['WooCommerceEventsPurchaserFirstName']);
                                    update_post_meta($postID, 'WooCommerceEventsPurchaserLastName', $ticket['WooCommerceEventsPurchaserLastName']);
                                    update_post_meta($postID, 'WooCommerceEventsPurchaserEmail', $ticket['WooCommerceEventsPurchaserEmail']);

                                    update_post_meta($postID, 'WooCommerceEventsPrice', $ticket['WooCommerceEventsPrice']);

                                    if (!function_exists('is_plugin_active_for_network')) {
                                        
                                        require_once(ABSPATH.'/wp-admin/includes/plugin.php' );
                                            
                                    }

                                    if ($this->is_plugin_active( 'fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php') || is_plugin_active_for_network('fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php')) {

                                        $Fooevents_Custom_Attendee_Fields = new Fooevents_Custom_Attendee_Fields();
                                        $WooCommerceEventsCustomAttendeeFields = $Fooevents_Custom_Attendee_Fields->process_capture_custom_attendee_options($postID, $ticket['WooCommerceEventsCustomAttendeeFields']);

                                    }

                                    if ($this->is_plugin_active( 'fooevents_seating/fooevents-seating.php' ) || is_plugin_active_for_network('fooevents_seating/fooevents-seating.php')) {

                                        $Fooevents_Seating = new Fooevents_Seating();
                                        $WooCommerceEventsSeatingFields = $Fooevents_Seating->process_capture_seating_options($postID, $ticket['WooCommerceEventsSeatingFields']);

                                    }

                                    

                                    update_post_meta($postID, 'WooCommerceEventsProductName', $product->post_title);
                                    
                                    update_post_meta($postID, 'WooCommerceEventsProductBlogID', $ticket['product_blog_id']);

                                    $y++;
                                
                                }
                                    
                            }

                            $x++;

                        }
                        
                        update_post_meta($order_id, 'WooCommerceEventsTicketsGenerated', 'yes');
                        
                    }

                }
                
            
            
            
            /**
             * Builds tickets to be emailed
             * 
             * @param int $order_id
             */
            public function build_send_tickets($order_id) {

                $order = array();
                try {
                    
                    $order = new WC_Order($order_id);
                    
                } catch (Exception $e) {
                    
                }  
                
                $tickets_query = new WP_Query( array('post_type' => array('event_magic_tickets'), 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'WooCommerceEventsOrderID', 'value' => $order_id ) )) );
                $orderTickets = $tickets_query->get_posts();
                
                $emailHTML = '';
                
                $sortedOrderTickets = array();
                
                //Sort tickets into events
                foreach($orderTickets as $orderTicket) {
                    
                    $ticket = $this->TicketHelper->get_ticket_data($orderTicket->ID);
                    $sortedOrderTickets[$ticket['WooCommerceEventsProductID']][] = $ticket;
                    
                }
                
                foreach($sortedOrderTickets as $productID => $tickets) {

                    $WooCommerceEventsEmailAttendee = get_post_meta($productID, 'WooCommerceEventsEmailAttendee', true);
                    $WooCommerceEventsEmailSubjectSingle = get_post_meta($productID, 'WooCommerceEventsEmailSubjectSingle', true);
                    
                    if(empty($WooCommerceEventsEmailSubjectSingle)) {

                        $WooCommerceEventsEmailSubjectSingle  = __('{OrderNumber} Ticket', 'woocommerce-events');

                    }
                    
                    $subject = str_replace('{OrderNumber}', '[#'.$order_id.']', $WooCommerceEventsEmailSubjectSingle);
                    
                    $WooCommerceEventsTicketTheme = get_post_meta($productID, 'WooCommerceEventsTicketTheme', true);
                    if(empty($WooCommerceEventsTicketTheme)) {
                        
                        $WooCommerceEventsTicketTheme = $this->Config->emailTemplatePath;
                        
                    }
                    
                    $header = $this->MailHelper->parse_email_template($WooCommerceEventsTicketTheme.'/header.php', array(), $tickets[0]); 
                    $footer = $this->MailHelper->parse_email_template($WooCommerceEventsTicketTheme.'/footer.php', array(), $tickets[0]);
                    
                    $ticketBody = '';
                    
                    $emailAttendee = false;
                    $ticketCount = 1;
                    
                    foreach($tickets as $ticket) {

                        if ($WooCommerceEventsEmailAttendee == 'on') {
                            
                            $ticket['ticketNumber'] = 1;
                            
                        } else {
                            
                            $ticket['ticketNumber'] = $ticketCount;
                            
                        }    

                        $body = $this->MailHelper->parse_ticket_template($WooCommerceEventsTicketTheme.'/ticket.php', $ticket);
                        $ticketBody .= $body;

                        //Send to attendee
                        if ($WooCommerceEventsEmailAttendee == 'on' && isset($ticket['WooCommerceEventsAttendeeEmail'])) {
                            
                            $attachment = '';
                            if (!function_exists('is_plugin_active_for_network')) {
                                
                                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
                                
                            }
                            if ( $this->is_plugin_active('fooevents_pdf_tickets/fooevents-pdf-tickets.php') || is_plugin_active_for_network('fooevents_pdf_tickets/fooevents-pdf-tickets.php')) {
                                
                                $globalFooEventsPDFTicketsEnable = get_option( 'globalFooEventsPDFTicketsEnable' );
                                $globalFooEventsPDFTicketsAttachHTMLTicket = get_option( 'globalFooEventsPDFTicketsAttachHTMLTicket' );

                                if($globalFooEventsPDFTicketsEnable == 'yes') {

                                    $FooEvents_PDF_Tickets = new FooEvents_PDF_Tickets();
                                    
                                    $attachment = $FooEvents_PDF_Tickets->generate_ticket($productID, array($ticket), $this->Config->barcodePath, $this->Config->path);
                                    $FooEventsPDFTicketsEmailText = get_post_meta($productID, 'FooEventsPDFTicketsEmailText', true);
                                    
                                    if($globalFooEventsPDFTicketsAttachHTMLTicket !== 'yes') {
                                        
                                        $header = $FooEvents_PDF_Tickets->parse_email_template( WP_PLUGIN_DIR . '/fooevents/template/email-header.php');
                                        $footer = $FooEvents_PDF_Tickets->parse_email_template( WP_PLUGIN_DIR . '/fooevents/template/email-footer.php');

                                        $body = $header.$FooEventsPDFTicketsEmailText.$footer;
                                    
                                    }
                                    
                                    if(empty($body)) {

                                        $body = __('Your tickets are attached. Please print them and bring them to the event.', 'fooevents-pdf-tickets');

                                    }
                                    
                                }
                                
                            }
                            
                            if($ticket['WooCommerceEventsSendEmailTickets'] === 'on') {
                            
                                $mailStatus = $this->MailHelper->send_ticket($ticket['WooCommerceEventsAttendeeEmail'], $subject, $header.$body.$footer, $attachment);
                            
                            }
                            
                            $emailAttendee = true;

                        }

                        $ticketCount++;
                        
                    }
                    
                    //Send to purchaser
                    if ($WooCommerceEventsEmailAttendee != 'on' && $emailAttendee === false) {
                        
                        $attachment = '';
                        
                        if (!function_exists('is_plugin_active_for_network')) {
                            
                            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
                            
                        }
                        if ($this->is_plugin_active('fooevents_pdf_tickets/fooevents-pdf-tickets.php') || is_plugin_active_for_network('fooevents_pdf_tickets/fooevents-pdf-tickets.php')) {
                            
                            $globalFooEventsPDFTicketsEnable = get_option('globalFooEventsPDFTicketsEnable');
                            $globalFooEventsPDFTicketsLayout = get_option('globalFooEventsPDFTicketsLayout');
                            $globalFooEventsPDFTicketsAttachHTMLTicket = get_option('globalFooEventsPDFTicketsAttachHTMLTicket');
                            
                            if(empty($globalFooEventsPDFTicketsLayout)) {

                                $globalFooEventsPDFTicketsLayout = 'single';

                            }
                            
                            if($globalFooEventsPDFTicketsEnable == 'yes') {

                                $FooEvents_PDF_Tickets = new FooEvents_PDF_Tickets();

                                $attachment = $FooEvents_PDF_Tickets->generate_ticket($productID, $tickets, $this->Config->barcodePath, $this->Config->path);

                                if($globalFooEventsPDFTicketsAttachHTMLTicket === 'yes') {

                                    $attachedText = get_post_meta($productID, 'FooEventsPDFTicketsEmailText', true);

                                    if (empty($attachedText)) {
                                        
                                        $attachedText = __('Your tickets are attached. Please print them and bring them to the event.', 'fooevents-pdf-tickets');
                                        
                                    }

                                    $header = $attachedText.$header;

                                } else {
              
                                    $ticketBody = get_post_meta($productID, 'FooEventsPDFTicketsEmailText', true);
                                    
                                    if(empty($ticketBody)||$ticketBody == '') {
                                        
                                        $ticketBody = __('Your tickets are attached. Please print them and bring them to the event.', 'fooevents-pdf-tickets');
                                        
                                    }    
                                       
                                    $header = $FooEvents_PDF_Tickets->parse_email_template(WP_PLUGIN_DIR . '/fooevents/template/email-header.php');
                                    $footer = $FooEvents_PDF_Tickets->parse_email_template(WP_PLUGIN_DIR . '/fooevents/template/email-footer.php');
                                    
                                } 

                            }
                            
                        }
                        
                        $orderEmailAddress = $order->get_billing_email();

                        if($ticket['WooCommerceEventsSendEmailTickets'] === 'on') {

                            $mailStatus = $this->MailHelper->send_ticket($orderEmailAddress, $subject, $header.$ticketBody.$footer, $attachment);
                            
                        }
                    }
                    
                }

            }
            
            
            
            /**
             * Sends a ticket email once an order is completed.
             * 
             * @param int $order_id
             * @global $woocommerce, $evotx;
             */
             public function send_ticket_email($order_id) {

                $this->create_tickets($order_id);
             
                set_time_limit(0);

                global $woocommerce;

                $order = new WC_Order( $order_id );
                $tickets = $order->get_items();

                $WooCommerceEventsTicketsPurchased = get_post_meta($order_id, 'WooCommerceEventsTicketsPurchased', true);
                
                $customer = get_post_meta($order_id, '_customer_user', true);
                $usermeta = get_user_meta($customer);

                $WooCommerceEventsSentTicket =  get_post_meta($order_id, 'WooCommerceEventsSentTicket', true);

                if ($this->is_plugin_active( 'fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php') || is_plugin_active_for_network('fooevents_custom_attendee_fields/fooevents-custom-attendee-fields.php')) {

                    $Fooevents_Custom_Attendee_Fields = new Fooevents_Custom_Attendee_Fields();
                    $WooCommerceEventsCustomAttendeeFields = $Fooevents_Custom_Attendee_Fields->process_capture_custom_attendee_options($postID, $ticket['WooCommerceEventsCustomAttendeeFields']);

                }
                
                if ($this->is_plugin_active( 'fooevents_seating/fooevents-seating.php' ) || is_plugin_active_for_network('fooevents_seating/fooevents-seating.php')) {

                    $Fooevents_Seating = new Fooevents_Seating();
                    $WooCommerceEventsSeatingFields = $Fooevents_Seating->process_capture_seating_options($postID, $ticket['WooCommerceEventsSeatingFields']);

                }

                $product = get_post($ticket['WooCommerceEventsProductID']);

                update_post_meta($postID, 'WooCommerceEventsProductName', $product->post_title);

                $x++;

            } 
            
                
            /**
            * Sends a ticket email once an order is completed.
            * 
            * @param int $order_id
            * @global $woocommerce, $evotx;
            */
            public function process_order_tickets($order_id) {

                set_time_limit(0);
                
                $this->create_tickets($order_id);
                $this->build_send_tickets($order_id);

            }
            
            
            
            /**
             * Generates random string used for ticket hash
             * 
             * @param int $length
             * @return string
             */
            function generate_random_string($length = 10) {
                
                return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);

            } 
            
            
            /**
            * Checks if a plugin is active.
            * 
            * @param string $plugin
            * @return boolean
            */
            function is_plugin_active($plugin) {

                return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
           

                }
                
        }
                
        
?>