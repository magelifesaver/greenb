<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-parser.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Parser {

    public static function sanitize_phone( $phone ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone );
        return substr( $phone, -10 );
    }

    public static function parse_html_and_build_preview( $post_data ) {
        // === Process Start: Parsing Order HTML ===
        AAA_V4_Logger::log( '=== Process Start: Parsing Order HTML ===' );

        $order_html = isset( $post_data['order_html'] ) ? wp_unslash( $post_data['order_html'] ) : '';

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $order_html );
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);

        // Parse customer and address info
        $external_order_number = '';
        $customer_first_name   = '';
        $customer_last_name    = '';
        $customer_phone        = '';
        $customer_email        = '';
        $billing_address_1     = '';
        $billing_address_2     = '';
        $billing_city          = '';
        $billing_state         = '';
        $billing_postcode      = '';
        $billing_country       = '';
        $order_notes           = '';

        // === External Order Number ===
        $order_number_node = $xpath->query('//span[contains(@class, "OrderId")]')->item(0);
        if ( $order_number_node && trim($order_number_node->nodeValue) ) {
            $raw = trim($order_number_node->nodeValue);
            if ( preg_match('/Order\s+#?(\d+)/i', $raw, $matches) ) {
                $external_order_number = sanitize_text_field($matches[1]);
                AAA_V4_Logger::log( "Parsed external order number: {$external_order_number}" );
            } else {
                AAA_V4_Logger::log( "⚠️ Found span but could not parse order number from: {$raw}" );
            }
        } else {
            AAA_V4_Logger::log( '⚠️ No external order number span found in HTML.' );
        }

        $name_node = $xpath->query('//h4[contains(@class, "DetailRecipientName")]')->item(0);
        if ( $name_node ) {
            $parts = explode(' ', trim($name_node->nodeValue), 2);
            $customer_first_name = $parts[0] ?? '';
            $customer_last_name  = $parts[1] ?? '';
        }

        $phone_node = $xpath->query('//div[contains(@class, "DetailContainer")]')->item(0);
        if ( $phone_node ) {
            $customer_phone = self::sanitize_phone($phone_node->nodeValue);
        }

        $email_node = $xpath->query('//p[contains(@class, "DetailContent")]')->item(1);
        if ( $email_node && strpos($email_node->nodeValue, '@') !== false ) {
            $customer_email = trim($email_node->nodeValue);
        }

        $address_node = $xpath->query('//p[contains(@class, "DetailContent")]')->item(0);
        if ( $address_node ) {
            $raw = trim( $address_node->nodeValue );
            $parts = array_map( 'trim', explode( ',', $raw ) );
            $billing_address_1 = $parts[0] ?? '';
            if ( isset( $parts[1] ) ) {
                $billing_city = $parts[1];
            }
            // Improved ZIP detection from anywhere in address string
            if ( preg_match('/\b(\d{5})(?:[-\s]\d{4})?\b/', $raw, $m) ) {
                $billing_postcode = $m[1];
            }
            $opts = get_option( 'aaa_v4_order_creator_settings', [] );
            $billing_state   = isset( $opts['default_state'] ) ? sanitize_text_field( $opts['default_state'] ) : '';
            $billing_country = isset( $opts['default_country'] ) ? sanitize_text_field( $opts['default_country'] ) : '';
        }

        $notes_node = $xpath->query('//div[contains(@class, "CustomerNoteBox")]//p[contains(@class, "Note")]')->item(0);
        if ( $notes_node ) {
            $order_notes = trim($notes_node->nodeValue);
        }

        $products = [];
        $product_nodes = $xpath->query('//p[contains(@class, "Name")]');
        $qty_nodes     = $xpath->query('//div[contains(@class, "Quantity")]');

        for ( $i = 0; $i < $product_nodes->length; $i++ ) {
            $name = trim( $product_nodes->item( $i )->nodeValue );
            $qty  = (int) trim( $qty_nodes->item( $i )->nodeValue );
            if ( $name ) {
                $products[] = [
                    'line_number'  => $i + 1,
                    'product_name' => $name,
                    'quantity'     => $qty,
                ];
            }
        }

        $customer_status = 'New Customer (Will be created)';
        $match = AAA_V4_Customer_Handler::find_existing_customer($customer_email, $customer_phone);
        if ( $match ) {
            $customer_status = 'Existing Customer (Matched by ' . ucfirst($match['matched_by']) . ')';
        }

        AAA_V4_Logger::log( 'Parsed ' . count($products) . ' products and extracted customer email: ' . $customer_email );

        $trigger_relookup = false;
        if ( ! empty( $customer_phone ) ) {
            $all_with_phone = get_users([
                'meta_key'   => 'billing_phone',
                'meta_value' => $customer_phone,
                'fields'     => 'ID',
            ]);
            $unique_ids = array_unique( $all_with_phone );
            if ( count( $unique_ids ) > 1 ) {
                $trigger_relookup = true;
            }
        }

        $payload = [
            'external_order_number'    => $external_order_number,
            'customer_first_name'      => $customer_first_name,
            'customer_last_name'       => $customer_last_name,
            'customer_phone'           => $customer_phone,
            'customer_email'           => $customer_email,
            'billing_address_1'        => $billing_address_1,
            'billing_address_2'        => $billing_address_2,
            'billing_city'             => $billing_city,
            'billing_state'            => $billing_state,
            'billing_postcode'         => $billing_postcode,
            'billing_country'          => $billing_country,
            'order_notes'              => $order_notes,
            'products'                 => $products,
            'customer_status'          => $customer_status,
            'trigger_phone_relookup'   => $trigger_relookup,
        ];

        // Only mark Weedmaps as the source if we actually received HTML to parse
        if ( ! empty( trim( $order_html ) ) ) {
            $payload['order_source_type'] = 'weedmaps';
        }

        AAA_V4_Logger::log( '=== Process End: Parsing Order HTML ===' );

        return $payload;
    }
}
