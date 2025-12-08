<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class PrintPage
{
    public static function displayPrint()
    {
        global $wpdb;

              header("Content-Type: text/html");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header('Expires: Sun, 01 Jan 2020 00:00:00 GMT');
        header("Pragma: no-cache");

        self::render($wpdb->prefix . "barcode_v3_templates", $wpdb->prefix . "barcode_v3_template_to_user", "printpage");
    }

    private static function render($tableTemplates, $tableTemplateToUser, $fileName)
    {
        global $wpdb;
        global $current_user;

        $userId = get_current_user_id();

        $userTemplate = self::getUserTemplate($tableTemplateToUser, $userId);
        $chosenTemplateRow = self::getChosenTemplateRow($userTemplate, $tableTemplates);

        $generalSettings = UserSettings::getGeneral();
        if (!$generalSettings) {
            $generalSettings = array();
        }


        $userSettings = UserSettings::get();

        $websiteUrl = get_bloginfo("url");
        $uid = get_current_user_id();

        $Dimensions = new Dimensions();
        $dimensions = $Dimensions->get(false);

        $jsL10n = require Variables::$A4B_PLUGIN_BASE_PATH . 'config/jsL10n.php';

        include_once Variables::$A4B_PLUGIN_BASE_PATH . 'templates/' . $fileName . '.php';
        die();
    }

    private static function getUserTemplate($tableTemplateToUser, $userId)
    {
        global $wpdb;

        $userTemplate = null;

        $userTemplate = $wpdb->get_row("SELECT * FROM `{$tableTemplateToUser}` WHERE `userId` = {$userId}");

        return $userTemplate;
    }

    private static function getChosenTemplateRow($userTemplate, $tableTemplates)
    {
        global $wpdb;

        if ($userTemplate) {
            $chosenTemplateRow = $wpdb->get_row("SELECT * FROM `{$tableTemplates}` WHERE `id` = {$userTemplate->templateId}");
        } else {
            $chosenTemplateRow = $wpdb->get_row("SELECT * FROM `{$tableTemplates}` WHERE `slug` = 'default-1'");
        }

        $dimensions = new Dimensions();
        $activeDimension = $dimensions->getActive();

        if ($chosenTemplateRow->is_base && $chosenTemplateRow->is_default && $activeDimension) {
            $chosenTemplateRow->uol_id = $activeDimension;
        }

        return $chosenTemplateRow;
    }
}
