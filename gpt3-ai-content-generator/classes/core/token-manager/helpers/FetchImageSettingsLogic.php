<?php
// File: classes/core/token-manager/helpers/FetchImageSettingsLogic.php

namespace WPAICG\Core\TokenManager\Helpers;

use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to fetch Image Generator token management settings.
 *
 * @return array The token management settings for the Image Generator.
 */
function FetchImageSettingsLogic(): array {
    if (!class_exists(AIPKit_Image_Settings_Ajax_Handler::class)) {
        return []; // Return empty array if handler not available
    }
    $img_settings_all = AIPKit_Image_Settings_Ajax_Handler::get_settings();
    return $img_settings_all['token_management'] ?? [];
}