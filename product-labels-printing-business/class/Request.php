<?php

namespace UkrSolution\ProductLabelsPrinting;

class Request
{
    public static function ajaxRequestAccess()
    {
        $action = isset($_POST["action"]) ? sanitize_key($_POST["action"]) : "";
        $key = 'a4barcode_';

        if(!$action || !preg_match("/^{$key}.*$/", $action)) {
            return true;
        }

        if (function_exists("wp_doing_ajax") && wp_doing_ajax() && function_exists("wp_verify_nonce")) {

            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
                exit;
            }
        }

        return true;
    }
}
