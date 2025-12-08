<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/manager/utils/send_wp_error.php
// Status: NEW FILE

namespace WPAICG\Images\Manager\Utils;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

function send_wp_error_logic(WP_Error $error): void
{
    $status = is_array($error->get_error_data()) && isset($error->get_error_data()['status']) ? $error->get_error_data()['status'] : 400;
    wp_send_json_error(['message' => $error->get_error_message(), 'code' => $error->get_error_code()], $status);
}
