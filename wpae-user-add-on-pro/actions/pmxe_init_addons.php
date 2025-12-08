<?php
require_once __DIR__ .'/../libraries/XmlExportUser.php';
require_once __DIR__ .'/../libraries/XmlExportWooCommerceGuestCustomer.php';

function pmue_pmxe_init_addons() {
    XmlExportEngine::$user_export = new XmlExportUser();

    if(property_exists('XmlExportEngine', 'woo_customer_export')){
        XmlExportEngine::$woo_customer_export = new XmlExportWooCommerceCustomer();
    }

    if(property_exists('XmlExportEngine', 'woo_guest_customer_export')){
        XmlExportEngine::$woo_guest_customer_export = new XmlExportWooCommerceGuestCustomer();
    }
}