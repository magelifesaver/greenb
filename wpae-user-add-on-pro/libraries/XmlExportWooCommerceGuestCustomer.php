<?php

if (!class_exists('XmlExportWooCommerceGuestCustomer')) {

    final class XmlExportWooCommerceGuestCustomer
    {
        private $engine = false;

    	private $init_fields = array(
            array(
                'label' => 'customer_id',
                'name' => 'Customer ID',
                'type' => 'customer_id'
            ),
            array(
                'label' => 'email',
                'name' => 'Email',
                'type' => 'email'
            ),
            array(
                'label' => 'first_name',
                'name' => 'First Name',
                'type' => 'first_name'
            ),
            array(
                'label' => 'last_name',
                'name' => 'Last Name',
                'type' => 'last_name'
            )
        );

        private $default_fields = array(
            array(
                'label' => 'customer_id',
                'name' => 'Customer ID',
                'type' => 'customer_id'
            ),
            array(
                'label' => 'email',
                'name' => 'Email',
                'type' => 'email'
            ),
            array(
                'label' => 'first_name',
                'name' => 'First Name',
                'type' => 'first_name'
            ),
            array(
                'label' => 'last_name',
                'name' => 'Last Name',
                'type' => 'last_name'
            ),
            array(
                'label' => 'order_count',
                'name' => 'Orders Count',
                'type' => 'order_count'
            ),
            array(
                'label' => 'total_spend',
                'name' => 'Total Spend',
                'type' => 'total_spend'
            ),
            array(
                'label' => 'country',
                'name' => 'Country',
                'type' => 'country'
            ),
            array(
                'label' => 'city',
                'name' => 'City',
                'type' => 'city'
            ),
            array(
                'label' => 'state',
                'name' => 'State',
                'type' => 'state'
            ),
            array(
                'label' => 'postcode',
                'name' => 'Postcode',
                'type' => 'postcode'
            ),
            array(
                'label' => 'date_registered',
                'name' => 'Date Registered',
                'type' => 'date_registered'
            ),
            array(
                'label' => 'date_last_active',
                'name' => 'Date Last Active',
                'type' => 'date_last_active'
            )
        );

        private $other_fields = array();

        public function __construct($engine = false)
        {
            $this->engine = $engine;

            add_filter("wp_all_export_available_data", array(&$this, "filter_available_data"), 10, 1);
            add_filter("wp_all_export_available_sections", array(&$this, "filter_available_sections"), 10, 1);
            add_filter("wp_all_export_init_fields", array(&$this, "filter_init_fields"), 10, 1);
            add_filter("wp_all_export_default_fields", array(&$this, "filter_default_fields"), 10, 1);
            add_filter("wp_all_export_other_fields", array(&$this, "filter_other_fields"), 10, 1);
        }

        public function init($existing_meta_keys = array())
        {
            $this->other_fields = array();

            // Add custom fields from WooCommerce customer lookup table
            $custom_fields = array(
                'avg_order_value' => 'Average Order Value',
                'username' => 'Username'
            );

            foreach ($custom_fields as $field_key => $field_name) {
                $this->other_fields[] = array(
                    'label' => $field_key,
                    'name' => $field_name,
                    'type' => 'cf',
                    'options' => serialize(array('type' => 'text'))
                );
            }
        }

        public function filter_available_data($available_data)
        {
            if (XmlExportEngine::$is_woo_guest_customer_export) {
                $available_data['guest_customers'] = 'WooCommerce Guest Customers';
            }
            return $available_data;
        }

        public function filter_available_sections($sections)
        {
            if (XmlExportEngine::$is_woo_guest_customer_export) {
                // Remove irrelevant sections for guest customers
                unset($sections['cats']);        // Categories - not applicable to guest customers
                unset($sections['media']);       // Media - guest customers have no user account for media
                unset($sections['cf']);          // Custom Fields - guest customers have no user meta

                $sections['guest_customers'] = array(
                    'title' => 'Guest Customer Information',
                    'content' => 'guest_customer_data'
                );
            }
            return $sections;
        }

        public function filter_init_fields($fields)
        {
            if (XmlExportEngine::$is_woo_guest_customer_export) {
                return $this->init_fields;
            }
            return $fields;
        }

        public function filter_default_fields($fields)
        {
            if (XmlExportEngine::$is_woo_guest_customer_export) {
                return $this->default_fields;
            }
            return $fields;
        }

        public function filter_other_fields($fields)
        {
            if (XmlExportEngine::$is_woo_guest_customer_export) {
                return $this->other_fields;
            }
            return $fields;
        }

        public static function prepare_data($guest_customer, $exportOptions, $xmlWriter, &$acfs, $implode_delimiter, $preview)
        {
            $article = array();

            $is_xml_export = false;
            if (!empty($xmlWriter) and $exportOptions['export_to'] == 'xml' and !in_array($exportOptions['xml_template_type'], array('custom', 'XmlGoogleMerchants'))) {
                $is_xml_export = true;
            }

            foreach ($exportOptions['cc_name'] as $ID => $value) {
                $element_name = (!empty($exportOptions['cc_name'][$ID])) ? $exportOptions['cc_name'][$ID] : 'untitled_' . $ID;
                $fieldSnipped = (!empty($exportOptions['cc_php'][$ID]) and !empty($exportOptions['is_php_enabled'])) ? $exportOptions['cc_php'][$ID] : false;

                if ($is_xml_export) {
                    $element_name = (!empty($exportOptions['cc_name'][$ID])) ? preg_replace('/[^a-z0-9_:-]/i', '', $exportOptions['cc_name'][$ID]) : 'untitled_' . $ID;
                }

                switch ($exportOptions['cc_type'][$ID]) {
                    case 'customer_id':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->customer_id, $fieldSnipped), 'customer_id', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'email':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->email, $fieldSnipped), 'email', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'first_name':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->first_name, $fieldSnipped), 'first_name', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'last_name':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->last_name, $fieldSnipped), 'last_name', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'order_count':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->order_count, $fieldSnipped), 'order_count', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'total_spend':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->total_spend, $fieldSnipped), 'total_spend', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'country':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->country, $fieldSnipped), 'country', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'city':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->city, $fieldSnipped), 'city', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'state':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->state, $fieldSnipped), 'state', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'postcode':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->postcode, $fieldSnipped), 'postcode', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'date_registered':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->date_registered, $fieldSnipped), 'date_registered', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'date_last_active':
                        $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->date_last_active, $fieldSnipped), 'date_last_active', $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;

                    case 'cf':
                        $fieldLabel = trim($exportOptions['cc_label'][$ID]);
                        $fieldValue = trim($exportOptions['cc_value'][$ID]);

                        if (!empty($fieldValue)) {
                            // For guest customers, get the value from the customer object properties
                            if (property_exists($guest_customer, $fieldValue)) {
                                $val = apply_filters('pmxe_custom_field', pmxe_filter($guest_customer->$fieldValue, $fieldSnipped), $fieldValue, $guest_customer->customer_id);
                            } else {
                                $val = apply_filters('pmxe_custom_field', pmxe_filter('', $fieldSnipped), $fieldValue, $guest_customer->customer_id);
                            }
                            if ($is_xml_export) {
                                $xmlWriter->writeElement($element_name, $val);
                            } else {
                                wp_all_export_write_article($article, $element_name, $val);
                            }
                        }
                        break;

                    default:
                        // Handle any other field types
                        $val = apply_filters('pmxe_custom_field', pmxe_filter('', $fieldSnipped), $exportOptions['cc_type'][$ID], $guest_customer->customer_id);
                        if ($is_xml_export) {
                            $xmlWriter->writeElement($element_name, $val);
                        } else {
                            wp_all_export_write_article($article, $element_name, $val);
                        }
                        break;
                }
            }

            return $article;
        }

        public function get($key, $default = null)
        {
            return isset($this->{$key}) ? $this->{$key} : $default;
        }
    }
}
